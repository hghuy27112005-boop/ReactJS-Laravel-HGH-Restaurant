<?php

namespace App\Http\Controllers;

use App\Models\Statistics;
use App\Models\Bill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Get user statistics
     */
    public function userStats(Request $request)
    {
        $user = $request->user();
        $stats = $user->statistics;

        if (!$stats) {
            $stats = Statistics::create([
                'user_id' => $user->id,
                'total_orders' => 0,
                'booking_orders' => 0,
                'delivery_orders' => 0,
                'total_spent' => 0,
                'total_discount' => 0,
                'total_points' => 0,
                'membership' => $user->membership,
            ]);
        }

        return response()->json([
            'data' => [
                'total_orders' => $stats->total_orders,
                'booking_orders' => $stats->booking_orders,
                'delivery_orders' => $stats->delivery_orders,
                'total_spent' => $stats->total_spent,
                'average_order_value' => $stats->total_orders > 0 ? $stats->total_spent / $stats->total_orders : 0,
                'total_discount' => $stats->total_discount,
                'total_points' => $stats->total_points,
                'membership' => $stats->membership,
                'last_order_date' => $stats->last_order_date,
                'order_frequency' => $stats->getOrderFrequency(),
            ],
        ]);
    }

    /**
     * Get order history
     */
    public function orderHistory(Request $request)
    {
        $user = $request->user();
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Bill::where('user_id', $user->id);

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $orders = $query->with('orders.dish', 'delivery', 'bookingTable')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $orders->items(),
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * Get spending trends
     */
    public function spendingTrends(Request $request)
    {
        $user = $request->user();
        $period = $request->get('period', 'month'); // week, month, year

        $query = Bill::where('user_id', $user->id)
            ->where('is_paid', true)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as orders'))
            ->groupBy('date');

        if ($period === 'week') {
            $query->whereDate('created_at', '>=', now()->subDays(7));
        } elseif ($period === 'month') {
            $query->whereDate('created_at', '>=', now()->subDays(30));
        } elseif ($period === 'year') {
            $query->whereDate('created_at', '>=', now()->subDays(365));
        }

        $trends = $query->orderBy('date')->get();

        return response()->json([
            'data' => $trends,
            'summary' => [
                'total_spent' => $trends->sum('total'),
                'total_orders' => $trends->sum('orders'),
                'average_daily' => $trends->count() > 0 ? $trends->sum('total') / $trends->count() : 0,
            ],
        ]);
    }

    /**
     * Get favorite dishes
     */
    public function favoriteDishes(Request $request)
    {
        $user = $request->user();
        $limit = $request->get('limit', 10);

        $favorites = DB::table('orders')
            ->join('bills', 'orders.bill_id', '=', 'bills.id')
            ->join('dishes', 'orders.dish_id', '=', 'dishes.id')
            ->where('bills.user_id', $user->id)
            ->select('dishes.id', 'dishes.name', 'dishes.price', 'dishes.image_url', DB::raw('SUM(orders.quantity) as total_ordered'), DB::raw('COUNT(DISTINCT bills.id) as times_ordered'))
            ->groupBy('dishes.id', 'dishes.name', 'dishes.price', 'dishes.image_url')
            ->orderByDesc('times_ordered')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $favorites,
        ]);
    }

    /**
     * Get comparison with previous period
     */
    public function periodComparison(Request $request)
    {
        $user = $request->user();

        $currentMonth = Bill::where('user_id', $user->id)
            ->where('is_paid', true)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get();

        $previousMonth = Bill::where('user_id', $user->id)
            ->where('is_paid', true)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->get();

        return response()->json([
            'data' => [
                'current_month' => [
                    'total_spent' => $currentMonth->sum('total_amount'),
                    'total_orders' => $currentMonth->count(),
                    'average_order' => $currentMonth->count() > 0 ? $currentMonth->sum('total_amount') / $currentMonth->count() : 0,
                ],
                'previous_month' => [
                    'total_spent' => $previousMonth->sum('total_amount'),
                    'total_orders' => $previousMonth->count(),
                    'average_order' => $previousMonth->count() > 0 ? $previousMonth->sum('total_amount') / $previousMonth->count() : 0,
                ],
                'change' => [
                    'spending_percent' => $previousMonth->sum('total_amount') > 0 ? (($currentMonth->sum('total_amount') - $previousMonth->sum('total_amount')) / $previousMonth->sum('total_amount') * 100) : 0,
                    'orders_percent' => $previousMonth->count() > 0 ? (($currentMonth->count() - $previousMonth->count()) / $previousMonth->count() * 100) : 0,
                ],
            ],
        ]);
    }
}
