<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupons;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * Display a listing of customer's orders.
     */
    public function index(Request $request)
    {
        $orders = $request->user()->orders()
            ->with(['items.product', 'items.variant'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Display the specified order for the customer.
     */
    public function show(Request $request, $id)
    {
        $order = $request->user()->orders()
            ->with(['items.product', 'items.variant'])
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Place a new order.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'phone_number' => 'required|string',
            'payment_method' => 'required|string',
            'coupon_code' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_varaints,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $items = $request->items;
        $couponCode = $request->coupon_code;

        // Calculate subtotal
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['qty'] * $item['price'];
        }

        // Handle Coupon logic if provided
        $discountAmount = 0;
        $coupon = null;
        if ($couponCode) {
            $coupon = Coupons::where('code', strtoupper($couponCode))->first();
            if ($coupon) {
                // Perform checks
                $isValid = true;
                $now = Carbon::now();
                if (!$coupon->status || 
                    $now->lt(Carbon::parse($coupon->start_date)) || 
                    $now->gt(Carbon::parse($coupon->end_date)) ||
                    ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) ||
                    $subtotal < $coupon->minimum_order
                ) {
                    $isValid = false;
                }

                if ($isValid) {
                    if ($coupon->type === 'percentage') {
                        $discountAmount = ($subtotal * $coupon->discount_value) / 100;
                    } else {
                        $discountAmount = $coupon->discount_value;
                    }
                    if ($discountAmount > $subtotal) {
                        $discountAmount = $subtotal;
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'The coupon code is invalid, expired, or minimum limit not reached.'
                    ], 400);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'The coupon code does not exist.'
                ], 404);
            }
        }

        // Delivery fee: Deleted (set to 0.00)
        $deliveryFee = 0.00;

        $totalAmount = $subtotal - $discountAmount;
        if ($totalAmount < 0) {
            $totalAmount = 0;
        }

        try {
            DB::beginTransaction();

            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'address' => $request->address,
                'phone_number' => $request->phone_number,
                'payment_method' => $request->payment_method,
                'coupon_code' => $coupon ? $coupon->code : null
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                ]);
            }

            // Increment coupon usage count if used
            if ($coupon) {
                $coupon->increment('used_count');
            }

            // Award spin quota if order subtotal is $5.00 or more
            if ($subtotal >= 5.00) {
                $todayStr = Carbon::today()->toDateString();
                $quota = \App\Models\SpinQuotas::where('user_id', $user->id)
                    ->where('date', $todayStr)
                    ->first();
                if ($quota) {
                    \App\Models\SpinQuotas::where('user_id', $user->id)
                        ->where('date', $todayStr)
                        ->increment('spin_count');
                } else {
                    \App\Models\SpinQuotas::create([
                        'user_id' => $user->id,
                        'spin_count' => 1,
                        'date' => $todayStr
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data' => $order->load('items.product', 'items.variant')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to place order. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Display a listing of all orders.
     */
    public function adminIndex(Request $request)
    {
        $orders = Order::with(['user', 'items.product', 'items.variant'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Admin: Display details of a specific order.
     */
    public function adminShow($id)
    {
        $order = Order::with(['user', 'items.product', 'items.variant'])
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Admin: Update the status of an order.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        $order->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $order
        ]);
    }

    /**
     * Customer: Validate a coupon.
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0'
        ]);

        $code = strtoupper($request->code);
        $subtotal = $request->subtotal;

        $coupon = Coupons::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found.'
            ], 404);
        }

        if (!$coupon->status) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon is inactive.'
            ], 400);
        }

        $now = Carbon::now();
        $startDate = Carbon::parse($coupon->start_date);
        $endDate = Carbon::parse($coupon->end_date);

        if ($now->lt($startDate) || $now->gt($endDate)) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon is expired or not yet active.'
            ], 400);
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon has reached its usage limit.'
            ], 400);
        }

        if ($subtotal < $coupon->minimum_order) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum order of $' . number_format($coupon->minimum_order, 2) . ' is required.'
            ], 400);
        }

        // Calculate discount
        $discount = 0;
        if ($coupon->type === 'percentage') {
            $discount = ($subtotal * $coupon->discount_value) / 100;
        } else {
            $discount = $coupon->discount_value;
        }

        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        return response()->json([
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'data' => [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'discount_value' => $coupon->discount_value,
                'discount_amount' => $discount
            ]
        ]);
    }

    /**
     * Admin: Retrieve statistics for Dashboard
     */
    public function dashboardStats(Request $request)
    {
        $totalOrders = Order::count();
        $totalRevenue = Order::where('status', 'completed')->sum('total_amount');
        $totalCustomers = \App\Models\User::where('role_id', 2)->count();
        $totalProducts = \App\Models\Product::count();

        // Get status counts
        $statusCounts = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Ensure all statuses have a default of 0
        $statuses = ['pending', 'processing', 'completed', 'cancelled'];
        $statusStats = [];
        foreach ($statuses as $status) {
            $statusStats[$status] = $statusCounts[$status] ?? 0;
        }

        // Get recent orders (latest 5)
        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get();

        // Get monthly sales for the past 6 months
        $monthlySales = Order::select(
                DB::raw('MONTHNAME(created_at) as month'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as count')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy(DB::raw('YEAR(created_at), MONTH(created_at), MONTHNAME(created_at)'))
            ->orderBy(DB::raw('YEAR(created_at), MONTH(created_at)'))
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_orders' => $totalOrders,
                'total_revenue' => (float)$totalRevenue,
                'total_customers' => $totalCustomers,
                'total_products' => $totalProducts,
                'status_stats' => $statusStats,
                'recent_orders' => $recentOrders,
                'monthly_sales' => $monthlySales
            ]
        ]);
    }

    /**
     * Admin: Retrieve detailed reports statistics
     */
    public function reportsStats(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Order::query();
        $completedQuery = Order::where('status', 'completed');

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            $query->whereBetween('created_at', [$start, $end]);
            $completedQuery->whereBetween('created_at', [$start, $end]);
        } else {
            // Default: last 30 days
            $start = Carbon::now()->subDays(30)->startOfDay();
            $end = Carbon::now()->endOfDay();

            $query->whereBetween('created_at', [$start, $end]);
            $completedQuery->whereBetween('created_at', [$start, $end]);
        }

        $totalRevenue = $completedQuery->sum('total_amount');
        $totalOrders = $query->count();
        $completedOrdersCount = $completedQuery->count();
        $averageOrderValue = $completedOrdersCount > 0 ? ($totalRevenue / $completedOrdersCount) : 0;
        $cancelledOrders = (clone $query)->where('status', 'cancelled')->count();

        // Best Sellers (join order_items with products)
        $bestSellers = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                'products.name',
                DB::raw('SUM(order_items.qty) as quantity_sold'),
                DB::raw('SUM(order_items.price * order_items.qty) as revenue')
            )
            ->where('orders.status', 'completed')
            ->when($startDate && $endDate, function($q) use ($start, $end) {
                $q->whereBetween('orders.created_at', [$start, $end]);
            }, function($q) use ($start, $end) {
                $q->whereBetween('orders.created_at', [$start, $end]);
            })
            ->groupBy('products.id', 'products.name')
            ->orderBy('quantity_sold', 'desc')
            ->take(5)
            ->get();

        // Payment Method breakdown
        $paymentMethods = (clone $completedQuery)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('payment_method')
            ->get();

        // Sales history log list
        $salesHistory = (clone $query)
            ->with('user')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => (float)$totalRevenue,
                'total_orders' => $totalOrders,
                'average_order_value' => (float)$averageOrderValue,
                'cancelled_orders' => $cancelledOrders,
                'best_sellers' => $bestSellers,
                'payment_methods' => $paymentMethods,
                'sales_history' => $salesHistory
            ]
        ]);
    }
}
