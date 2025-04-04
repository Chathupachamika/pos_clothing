<?php

namespace App\Http\Controllers;

use App\Models\ProductVariations;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductVariationController extends Controller
{
    public function index()
    {
        try {
            $variations = ProductVariations::all();
            return $this->successResponse('Product variations retrieved successfully', $variations, 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve product variations: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve product variations', 500);
        }
    }

    public function show($id)
    {
        try {
            $variation = ProductVariations::findOrFail($id);
            return $this->successResponse('Product variation found', $variation, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Product variation not found', 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving product variation: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve product variation', 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                '*.product_id' => 'required|exists:products,id',
                '*.size' => 'required|string|max:255',
                '*.color' => 'required|string|max:255',
                '*.price' => 'required|numeric|min:0',
                '*.selling_price' => 'required|numeric|min:0',
                '*.quantity' => 'required|integer|min:0',
                '*.barcode' => 'nullable|string|max:255',
                '*.discount' => 'nullable|numeric|min:0|max:100',
                '*.status' => 'nullable|string|in:In Stock,Low Stock,Out of Stock',
            ]);

            $variations = [];
            foreach ($validatedData as $data) {
                $variation = ProductVariations::create([
                    'product_id' => $data['product_id'],
                    'size' => $data['size'],
                    'color' => $data['color'],
                    'price' => $data['price'],
                    'selling_price' => $data['selling_price'],
                    'quantity' => $data['quantity'],
                    'barcode' => $data['barcode'] ?? null,
                    'discount' => $data['discount'] ?? 0,
                    'status' => $data['status'] ?? 'In Stock'
                ]);

                $variations[] = $variation;
            }

            return $this->successResponse('Product variations created successfully', $variations, 201);
        } catch (\Exception $e) {
            Log::error('Product variation creation failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to create product variations: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('Updating product variation', ['id' => $id, 'payload' => $request->all()]);

            $validatedData = $request->validate([
                'quantity' => 'required|integer|min:0',
                'color' => 'required|string|max:255',
                'size' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'selling_price' => 'required|numeric|min:0',
                'barcode' => 'nullable|string|max:255',
                'discount' => 'nullable|numeric|min:0|max:100',
                'status' => 'nullable|string|in:In Stock,Low Stock,Out of Stock',
                'restock_date_time' => 'nullable|date',
                'added_stock_amount' => 'nullable|integer|min:0',
            ]);

            $variation = ProductVariations::findOrFail($id);
            $variation->update($validatedData);

            Log::info('Product variation updated successfully', ['id' => $id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product variation updated successfully',
                'data' => $variation
            ], 200);
        } catch (ValidationException $e) {
            Log::error('Validation failed for updating product variation', ['errors' => $e->errors()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            Log::error('Product variation not found', ['id' => $id]);
            return response()->json([
                'status' => 'error',
                'message' => 'Product variation not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update product variation', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product variation'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $variation = ProductVariations::findOrFail($id);
            $variation->delete();

            return $this->successResponse('Product variation deleted successfully', null, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Product variation not found', 404);
        } catch (\Exception $e) {
            Log::error('Product variation deletion failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete product variation', 500);
        }
    }

    public function showByProduct($id)
    {
        try {
            $variations = ProductVariations::where('product_id', $id)->get();

            return $this->successResponse('Product variations retrieved successfully', $variations, 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve product variations: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve product variations', 500);
        }
    }

    public function successResponse($message, $data, $status)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public function errorResponse($message, $status)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], $status);
    }
}
