<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Order;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function getStats(): JsonResponse
    {
        try {
            $now = Carbon::now();
            $weekStart = $now->startOfWeek();
            $weekEnd = $now->copy()->endOfWeek();

            // Wrap database queries in try-catch
            try {
                $weeklyEarnings = Order::whereBetween('created_at', [$weekStart, $weekEnd])
                    ->sum('total_amount');

                $lastWeekEarnings = Order::whereBetween('created_at', [
                    $weekStart->copy()->subWeek(),
                    $weekEnd->copy()->subWeek()
                ])->sum('total_amount');

                $salesGrowth = $lastWeekEarnings > 0 
                    ? number_format((($weeklyEarnings - $lastWeekEarnings) / $lastWeekEarnings) * 100, 1) . '%'
                    : '0%';

                $data = [
                    'dashboardStats' => [
                        'weeklyEarnings' => $weeklyEarnings,
                        'totalSales' => Order::count(),
                        'totalCustomers' => Customer::count(),
                        'totalSuppliers' => Supplier::count(),
                        'totalProducts' => Product::count(),
                        'totalEmployees' => Admin::count(),
                        'salesGrowth' => $salesGrowth,
                        'customerGrowth' => '+5.25%',
                        'supplierGrowth' => '+2.5%',
                        'productGrowth' => '+12%',
                        'employeeGrowth' => '+1',
                        'employeeStatus' => 'All Active'
                    ],
                    'chartData' => [
                        'labels' => ['Cash', 'Credit Card', 'Debit Card'],
                        'datasets' => [[
                            'data' => [
                                Order::where('payment_method', 'CASH')->count(),
                                Order::where('payment_method', 'CREDIT_CARD')->count(),
                                Order::where('payment_method', 'DEBIT_CARD')->count()
                            ],
                            'backgroundColor' => ['#10B981', '#3B82F6', '#8B5CF6']
                        ]]
                    ],
                    'recentTransactions' => Order::with('products')
                        ->latest()
                        ->take(5)
                        ->get()
                        ->map(function ($order) {
                            return [
                                'id' => $order->id,
                                'product' => $order->products->first()->name ?? 'Unknown Product',
                                'date' => $order->created_at->format('Y-m-d'),
                                'method' => $order->payment_method,
                                'amount' => $order->total_amount,
                                'status' => $order->status
                            ];
                        })
                ];

                return response()->json($data);

            } catch (Exception $e) {
                Log::error('Database error in dashboard stats: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Database error occurred',
                    'message' => $e->getMessage()
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Error in getStats: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
