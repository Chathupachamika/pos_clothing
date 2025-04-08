<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductVariations;
use App\Models\ReturnedItems;
use App\Models\Order;
use App\Models\Product_Sales;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Sales_ProductController extends Controller
{
    public function processReturn(Request $request)
    {
        try {
            Log::info('Processing return request', ['data' => $request->all()]);

            $validatedData = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'items' => 'required|array',
                'items.*.id' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.reason' => 'required|string|min:3'
            ]);

            $order = Order::findOrFail($request->order_id);
            $orderItems = json_decode($order->items, true) ?? [];
            $totalReturnAmount = 0;

            DB::beginTransaction();

            try {
                foreach ($request->items as $returnItem) {
                    // Find matching order item
                    $orderItem = collect($orderItems)->firstWhere('bar_code', $returnItem['id']);

                    if (!$orderItem) {
                        throw new \Exception("Product {$returnItem['id']} not found in order");
                    }

                    if ($returnItem['quantity'] > $orderItem['quantity']) {
                        throw new \Exception("Cannot return more items than purchased for product {$returnItem['id']}");
                    }

                    // Find the product variation
                    $variation = ProductVariations::where('barcode', $returnItem['id'])->firstOrFail();

                    // Update product variation quantity (add returned items back to stock)
                    $variation->update([
                        'quantity' => $variation->quantity + $returnItem['quantity']
                    ]);

                    // Calculate return amount
                    $returnAmount = $orderItem['price'] * $returnItem['quantity'];
                    $totalReturnAmount += $returnAmount;

                    // Create return record
                    ReturnedItems::create([
                        'order_id' => $order->id,
                        'product_variation_id' => $variation->id,
                        'quantity' => $returnItem['quantity'],
                        'reason' => $returnItem['reason'],
                        'returned_amount' => $returnAmount,
                        'return_date' => now()
                    ]);

                    // Update order item quantity
                    $orderItem['quantity'] -= $returnItem['quantity'];

                    // Update the order items array
                    $orderItems = array_map(function($item) use ($returnItem, $orderItem) {
                        if ($item['bar_code'] === $returnItem['id']) {
                            return array_merge($item, ['quantity' => $orderItem['quantity']]);
                        }
                        return $item;
                    }, $orderItems);
                }

                // Remove items with zero quantity
                $orderItems = array_values(array_filter($orderItems, fn($item) => $item['quantity'] > 0));

                // Update order
                $order->update([
                    'items' => json_encode($orderItems),
                    'amount' => $order->amount - $totalReturnAmount
                ]);

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Return processed successfully',
                    'data' => [
                        'order_id' => $order->id,
                        'returned_amount' => $totalReturnAmount,
                        'new_total' => $order->amount,
                        'remaining_items' => $orderItems
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Return processing failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process return: ' . $e->getMessage()
            ], 500);
        }
    }
}
