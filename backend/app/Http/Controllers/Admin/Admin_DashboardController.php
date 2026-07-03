<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Delivery;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Admin_DashboardController extends Controller
{
    /**
     * Get admin dashboard data
     */
    public function index()
    {
        // Today's revenue
        $todayRevenue = Bill::whereDate('created_at', today())
            ->where('is_paid', true)
            ->sum('total_amount');

        // Total revenue
        $totalRevenue = Bill::where('is_paid', true)->sum('total_amount');

        // Total orders
        $totalOrders = Bill::count();

        // Total customers
        $totalCustomers = User::where('authority', 'User')->count();

        // Active deliveries
        $activeDeliveries = Delivery::whereIn('status', ['approved', 'in_delivery'])->count();

        // Low stock items
        $lowStockItems = Stock::where('status', 'low_stock')
            ->orWhere('quantity_left', '<=', 15)
            ->count();

        // Top dishes this month
        $topDishes = DB::table('orders')
            ->join('dishes', 'orders.dish_id', '=', 'dishes.id')
            ->join('bills', 'orders.bill_id', '=', 'bills.id')
            ->whereMonth('bills.created_at', now()->month)
            ->select('dishes.id', 'dishes.name', 'dishes.price', DB::raw('SUM(orders.quantity) as total_sold'), DB::raw('SUM(orders.quantity * orders.price_at_order) as revenue'))
            ->groupBy('dishes.id', 'dishes.name', 'dishes.price')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        // Revenue trend (last 7 days)
        $revenueTrend = DB::table('bills')
            ->where('is_paid', true)
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Customer segment
        $membershipStats = DB::table('users')
            ->select('membership', DB::raw('COUNT(*) as count'))
            ->where('authority', 'User')
            ->groupBy('membership')
            ->get();

        // Order type distribution
        $orderTypeStats = DB::table('bills')
            ->select('order_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('order_type')
            ->get();

        return response()->json([
            'data' => [
                'summary' => [
                    'today_revenue' => $todayRevenue,
                    'total_revenue' => $totalRevenue,
                    'total_orders' => $totalOrders,
                    'total_customers' => $totalCustomers,
                    'active_deliveries' => $activeDeliveries,
                    'low_stock_items' => $lowStockItems,
                ],
                'top_dishes' => $topDishes,
                'revenue_trend' => $revenueTrend,
                'membership_stats' => $membershipStats,
                'order_type_stats' => $orderTypeStats,
            ],
        ]);
    }
}
