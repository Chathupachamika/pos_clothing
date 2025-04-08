<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem; // Ensure this line is present and correct
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Product_Sales;
use App\Models\Promotion;
use App\Models\ReturnItem;
use App\Models\Sales;
use App\Models\SalesReturnItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlaceOrderController extends Controller
{
    public function store(Request $request)
    {

        $request->validate([
            'time' => 'required|date',
            'status' => 'required|boolean',
            'payment_type' => 'required|in:CASH,CREDIT_CARD,DEBIT_CARD',
            'amount' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0|max:100',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.bar_code' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Convert 'time' to MySQL datetime format
            $formattedTime = date('Y-m-d H:i:s', strtotime($request->time));

            // Calculate total and apply discount
            $total = 0;
            foreach ($request->items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            $discountAmount = ($total * $request->discount) / 100;
            $finalTotal = $total - $discountAmount;

            // Create order record
            $order = Order::create([
                'time' => $formattedTime,
                'status' => $request->status,
                'payment_type' => $request->payment_type,
                'amount' => $finalTotal,
                'discount' => $request->discount,
                'customer_id' => $request->customer_id,
            ]);

            // Process each item in the order
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'bar_code' => $item['bar_code'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully',
                'data' => $order
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to place order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'time' => 'required|date',
            'status' => 'required|boolean',
            'payment_type' => 'required|in:CASH,CREDIT_CARD,DEBIT_CARD',
            'amount' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0|max:100',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.bar_code' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Calculate total and apply discount
            $total = 0;
            foreach ($request->items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            $discountAmount = ($total * $request->discount) / 100;
            $finalTotal = $total - $discountAmount;

            // Update order record
            $order->update([
                'time' => $request->time,
                'status' => $request->status,
                'payment_type' => $request->payment_type,
                'amount' => $finalTotal,
                'discount' => $request->discount,
                'customer_id' => $request->customer_id,
            ]);

            // Update order items
            OrderItem::where('order_id', $order->id)->delete();
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'bar_code' => $item['bar_code'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order updated successfully',
                'data' => $order
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function return(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::find($id);
            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found'
                ], 404);
            }

            $returnedAt = now();

            foreach ($request->items as $item) {
                $orderItem = OrderItem::find($item['order_item_id']);
                if (!$orderItem) {
                    throw new \Exception("Order item not found for ID: {$item['order_item_id']}");
                }

                if ($item['quantity'] > $orderItem->quantity) {
                    throw new \Exception("Return quantity exceeds the ordered quantity for Order Item ID: {$item['order_item_id']}");
                }

                // Create return item record
                $returnItem = ReturnItem::create([
                    'order_item_id' => $item['order_item_id'],
                    'quantity' => $item['quantity'],
                    'reason' => $item['reason'],
                ]);

                // Create sales return item record
                SalesReturnItem::create([
                    'order_id' => $order->id,
                    'product_variation_id' => $orderItem->bar_code, // Assuming bar_code maps to product_variation_id
                    'returned_at' => $returnedAt,
                ]);

                // Update product quantity
                $product = Product::where('bar_code', $orderItem->bar_code)->first();
                if ($product) {
                    $product->increment('quantity', $item['quantity']);

                    // Update product status
                    $status = 'In Stock';
                    if ($product->quantity == 0) {
                        $status = 'Out Of Stock';
                    } elseif ($product->quantity < 20) {
                        $status = 'Low Stock';
                    }
                    $product->status = $status;
                    $product->save();
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Items returned successfully',
                'data' => [
                    'order_id' => $order->id,
                    'returned_at' => $returnedAt,
                    'items' => $request->items,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process return',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function updateSales(Request $request, $sales, $salesRecord)
    {
        // Update the sales record
        $sales->update($salesRecord);

        // Get all existing products in the sales record
        $product_sales = Product_sales::where('sales_id', $sales['id'])->get()->keyBy('product_id');

        // Prepare arrays for bulk update
        $updateData = [];
        $newItems = [];
        $deleteIds = [];
        $productUpdates = [];
        $currentTimeStamp = now();

        // Collect all product IDs from the new request
        $productIds = collect($request->get('items'))->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        // Loop through the items in the request
        foreach ($request->get('items') as $newItem) {
            $product = $products->get($newItem['product_id']);

            if (!$product) {
                throw new \Exception("Product with ID {$newItem['product_id']} not found.");
            }

            // If item exists in current sales
            if (isset($product_sales[$newItem['product_id']])) {
                $existingItem = $product_sales[$newItem['product_id']];
                $quantityDiff = $newItem['quantity'] - $existingItem->quantity;

                // Only update product if quantity has changed
                if ($quantityDiff !== 0) {
                    // Check if we have enough stock for an increase
                    if ($quantityDiff > 0 && $product->quantity < $quantityDiff) {
                        throw new \Exception("Not enough stock for Product ID {$newItem['product_id']}");
                    }

                    // Calculate new product quantity
                    $newQuantity = $product->quantity - $quantityDiff;
                    $productUpdates[] = [
                        'product_id' => $product->id,
                        'new_quantity' => $newQuantity
                    ];
                }

                // Update sales item data
                $updateData[] = [
                    'product_id' => $newItem['product_id'],
                    'sales_id' => $sales['id'],
                    'quantity' => $newItem['quantity'],
                    'price' => $newItem['price']
                ];

                unset($product_sales[$newItem['product_id']]);
            } else {
                // For new items
                if ($product->quantity < $newItem['quantity']) {
                    throw new \Exception("Not enough stock for Product ID {$newItem['product_id']}");
                }

                $newItems[] = [
                    'product_id' => $newItem['product_id'],
                    'sales_id' => $sales['id'],
                    'quantity' => $newItem['quantity'],
                    'price' => $newItem['price'],
                    'created_at' => $currentTimeStamp,
                    'updated_at' => $currentTimeStamp
                ];

                // Update product for new items
                $productUpdates[] = [
                    'product_id' => $product->id,
                    'new_quantity' => $product->quantity - $newItem['quantity']
                ];
            }
        }

        // Handle deletions
        $deleteIds = $product_sales->keys()->toArray();
        if (!empty($deleteIds)) {
            Product_sales::whereIn('product_id', $deleteIds)
                ->where('sales_id', $sales['id'])
                ->delete();

            // Return stock for deleted items
            foreach ($product_sales as $deletedItem) {
                $product = Product::find($deletedItem->product_id);
                if ($product) {
                    $productUpdates[] = [
                        'product_id' => $product->id,
                        'new_quantity' => $product->quantity + $deletedItem->quantity
                    ];
                }
            }
        }

        // Apply updates to product_sales
        foreach ($updateData as $data) {
            Product_sales::where('product_id', $data['product_id'])
                ->where('sales_id', $data['sales_id'])
                ->update([
                    'quantity' => $data['quantity'],
                    'price' => $data['price']
                ]);
        }

        if (!empty($newItems)) {
            Product_sales::insert($newItems);
        }

        // Update product quantities and status
        foreach ($productUpdates as $update) {
            $product = Product::find($update['product_id']);
            if ($product) {
                $product->quantity = $update['new_quantity'];

                // Update status based on new quantity
                if ($product->quantity == 0) {
                    $product->status = 'Out Of Stock';
                } elseif ($product->quantity < 20) {
                    $product->status = 'Low Stock';
                } else {
                    $product->status = 'In Stock';
                }

                $product->save();
            }
        }
    }

    private function updateInventoryStatus($inventory)
    {
        $status = 'In Stock';
        if ($inventory->quantity == 0) {
            $status = 'Out Of Stock';
        } elseif ($inventory->quantity < 20) {
            $status = 'Low Stock';
        }

        $inventory->status = $status;
        $inventory->save();
    }

    public function salesReportToday()
    {
        try {
            $sales = Sales::whereDate('created_at', today())->get();
            $totalIncome = $sales->sum('amount');
            return response()->json([
                'total_sales' => $sales->count(),
                'total_income' => $totalIncome,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch today\'s sales report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function salesReports(Request $request)
    {
        $startDate = $request->query('from');
        $endDate = $request->query('to');

        if (!$startDate || !$endDate) {
            return response()->json([
                'message' => 'Invalid date range. Please provide both start and end dates.'
            ], 400);
        }

        // Ensure the dates are in the correct format
        try {
            $startDate = new \DateTime($startDate);
            $endDate = new \DateTime($endDate);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid date format. Please provide dates in YYYY-MM-DD format.'
            ], 400);
        }

        // Convert the dates to the correct format for the query
        $startDateFormatted = $startDate->format('Y-m-d 00:00:00');
        $endDateFormatted = $endDate->format('Y-m-d 23:59:59');

        $sales = Sales::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$startDateFormatted, $endDateFormatted])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get();

        return response()->json([
            "total_sales" => $sales,
            "start" => $startDate,
            "end" => $endDate,
        ]);
    }

    public function bestSelling()
    {
        $bestSellingProducts = Product_Sales::select('product_id', DB::raw('count(*) as total'))
            ->groupBy('product_id')
            ->orderBy('total', 'desc')
            ->first();

        if (!$bestSellingProducts) {
            return response()->json([
                'message' => 'No sales data available'
            ], 404);
        }

        return response()->json([
            'products' => $bestSellingProducts,
            'total_sales' => $bestSellingProducts->total
        ]);
    }

    public function turnOverProducts()
    {
        $turnOverProducts = ReturnItem::select('product_id', DB::raw('count(*) as total'))
            ->groupBy('product_id')
            ->orderBy('total', 'desc')
            ->first();

        if (!$turnOverProducts) {
            return response()->json([
                'message' => 'No sales data available'
            ], 404);
        }

        return response()->json([
            'products' => $turnOverProducts,
            'total_returns' => $turnOverProducts->total
        ]);
    }

    public function paymentDistribution()
    {
        try {
            $paymentDistribution = Sales::select('payment_type', DB::raw('count(*) as total'))
                ->groupBy('payment_type')
                ->get();
            return response()->json([
                'payment_distribution' => $paymentDistribution,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch payment distribution',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        try {
            $orders = Order::with(['customer', 'items'])->get(); // Ensure 'items' relationship is loaded

            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'customer' => $order->customer ? [
                        'id' => $order->customer->id,
                        'name' => $order->customer->name,
                        'email' => $order->customer->email,
                        'phone' => $order->customer->phone,
                    ] : null,
                    'time' => $order->time,
                    'amount' => $order->amount,
                    'discount' => $order->discount,
                    'status' => $order->status,
                    'payment_type' => $order->payment_type,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'bar_code' => $item->bar_code,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total' => $item->quantity * $item->price,
                        ];
                    }),
                ];
            });

            return response()->json($formattedOrders, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
