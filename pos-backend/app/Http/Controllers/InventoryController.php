<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\SupplierProduct;
use Illuminate\Validation\ValidationException;
use Exception;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class InventoryController extends Controller
{
    public function index()
    {
        try {
            $products = Product::select(
                'id',
                'name',
                'quantity',
                'location',
                'status',
                'added_stock_amount',
                'price',
                'profit',
                'brand_name',
                'updated_at as restock_date_time'
            )->get();

            $formattedProducts = $products->map(function ($product) {
                $status = $this->determineStatus($product->quantity);
                return [
                    'id' => $product->id,
                    'quantity' => $product->quantity,
                    'restock_date_time' => $product->restock_date_time,
                    'added_stock_amount' => $product->added_stock_amount,
                    'location' => $product->location,
                    'status' => $status,
                    'name' => $product->name,
                    'price' => $product->price,
                    'profit' => $product->profit,
                    'brand_name' => $product->brand_name,
                    'total_value' => $product->quantity * $product->price,
                    'total_profit' => $product->quantity * $product->profit
                ];
            });

            return response()->json($formattedProducts);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve inventory',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $request->validate([
                'quantity' => 'required|numeric',
                'location' => 'required|string',
                'status' => 'required|string|in:In Stock,Low Stock,Out Of Stock',
            ]);

            $inventory = new Inventory();
            $inventory->quantity = $request->input('quantity');
            $inventory->restock_date_time = now();
            $inventory->added_stock_amount = $request->input('added_stock_amount', $inventory->quantity);
            $inventory->location = $request->input('location');
            $inventory->status = $request->input('status');
            $inventory->save();
            $this->updateStatus();
            return response()->json($inventory);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create inventory', 'message' => $e->getMessage()], 500);
        }
    }
    public function show($id)
    {
        try {
            $inventory = Inventory::findOrFail($id);
            return response()->json($inventory);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Inventory not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve inventory', 'message' => $e->getMessage()], 500);
        }
    }
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);

            $validated = $request->validate([
                'quantity' => 'required|numeric|min:0',
                'location' => 'required|string',
                'status' => 'required|string|in:In Stock,Low Stock,Out Of Stock',
                'added_stock_amount' => 'numeric|min:0',
                'restock_date_time' => 'required|date',
            ]);

            $product->quantity = $validated['quantity'];
            $product->location = $validated['location'];
            $product->status = $this->determineStatus($validated['quantity']);
            $product->updated_at = date('Y-m-d H:i:s', strtotime($validated['restock_date_time']));

            if (!empty($validated['added_stock_amount']) && $validated['added_stock_amount'] > 0) {
                $product->added_stock_amount = $validated['added_stock_amount'];
            }

            $product->save();

            // Format the response data with additional details needed for GRN
            $response = [
                'id' => $product->id,
                'name' => $product->name,
                'quantity' => $product->quantity,
                'location' => $product->location,
                'status' => $product->status,
                'added_stock_amount' => $product->added_stock_amount,
                'restock_date_time' => $product->updated_at,
                'description' => $product->description,
                'brand_name' => $product->brand_name,
                'size' => $product->size,
                'color' => $product->color,
                'category' => $product->category,
                'price' => $product->price,
                'supplier_id' => $product->supplier_id,
                'admin_id' => $product->admin_id,
                'total_value' => $product->quantity * $product->price,
                'total_profit' => $product->quantity * $product->profit
            ];

            DB::commit();
            return response()->json($response);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Product not found'], 404);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update inventory: ' . $e->getMessage()], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $inventory = Inventory::findOrFail($id);
            $inventory->delete();
            return response()->json(['message' => 'Inventory deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Inventory not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete inventory', 'message' => $e->getMessage()], 500);
        }
    }

    public function lowStock()
    {
        try {
            $lowStockProducts = Product::with(['supplier'])
                ->where('quantity', '<', 20)
                ->get()
                ->map(function ($product) {
                    return [
                        'supplier_id' => $product->supplier_id,
                        'supplier_name' => $product->supplier->name ?? 'N/A',
                        'supplier_email' => $product->supplier->email ?? 'N/A',
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $product->quantity,
                        'location' => $product->location,
                        'status' => $this->determineStatus($product->quantity)
                    ];
                });

            return response()->json($lowStockProducts);
        } catch (Exception | Throwable $e) {
            return response()->json([
                'error' => 'Failed to retrieve low stock products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function outOfStock()
    {
        try {
            $inventories = Inventory::where('quantity', 0)->get();
            return response()->json($inventories);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve out of stock inventory', 'message' => $e->getMessage()], 500);
        }
    }

    public function inStock()
    {
        try {
            $inventories = Inventory::where('quantity', '>', 0)->get();
            return response()->json($inventories);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve in stock inventory', 'message' => $e->getMessage()], 500);
        }
    }
    public function updateStatus(){
        $inventories = Inventory::all();
        $inventories->map(function ($inventory) {
            if ($inventory->quantity == 0) {
                $inventory->status = 'Out Of Stock';
            } elseif ($inventory->quantity < 20) { // Changed from 5 to 20
                $inventory->status = 'Low Stock';
            } else {
                $inventory->status = 'In Stock';
            }
            $inventory->save();
        });
    }
    private function determineStatus($quantity)
    {
        if ($quantity == 0) {
            return 'Out Of Stock';
        } elseif ($quantity < 20) { // Already set to 20
            return 'Low Stock';
        }
        return 'In Stock';
    }

    public function exportData()
    {
        try {
            $products = Product::with(['supplier', 'admin'])->get();

            $totalValue = 0;
            $totalProfit = 0;

            $formattedData = $products->map(function ($product) use (&$totalValue, &$totalProfit) {
                $status = $this->determineStatus($product->quantity);
                $itemValue = $product->price * $product->quantity;
                $itemProfit = $product->profit * $product->quantity;

                $totalValue += $itemValue;
                $totalProfit += $itemProfit;

                return [
                    'id' => $product->id,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'brand_name' => $product->brand_name,
                        'description' => $product->description,
                        'size' => $product->size,
                        'color' => $product->color,
                        'price' => $product->price,
                        'seller_price' => $product->seller_price,
                        'discount' => $product->discount,
                        'profit' => $product->profit,
                        'supplier_id' => $product->supplier_id,
                        'admin_id' => $product->admin_id,
                    ],
                    'quantity' => $product->quantity,
                    'location' => $product->location,
                    'status' => $status,
                    'added_stock_amount' => $product->added_stock_amount,
                    'restock_date_time' => $product->updated_at,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'total_value' => $itemValue,
                    'total_profit' => $itemProfit
                ];
            });

            return response()->json([
                'data' => $formattedData,
                'summary' => [
                    'total_value' => $totalValue,
                    'total_profit' => $totalProfit
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to export inventory data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
class LowStockDetail
{
    public $supplier_id;
    public $product_id;
    public $supplier_name;
    public $supplier_email;
    public $product_name;
    public $quantity;
    public $location;
    public $status;
    public function __construct($supplier_id, $product_id, $supplier_name, $supplier_email, $product_name, $quantity, $location, $status)
    {
        $this->supplier_id = $supplier_id;
        $this->product_id = $product_id;
        $this->supplier_name = $supplier_name;
        $this->supplier_email = $supplier_email;
        $this->product_name = $product_name;
        $this->quantity = $quantity;
        $this->location = $location;
        $this->status = $status;
    }
}
