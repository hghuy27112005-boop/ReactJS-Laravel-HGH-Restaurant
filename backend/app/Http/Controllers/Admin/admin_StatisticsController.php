<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Admin_StatisticsController extends Controller
{
    /**
     * Revenue grouped by day for a given month (defaults to current month)
     */
    public function revenue(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $rows = DB::table('bills')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->select(DB::raw("DATE(created_at) as date"), DB::raw('SUM(total_price) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $rows]);
    }

    /**
     * Danh sách các tháng (year-month) đã có bill, dùng đổ dropdown
     */
    public function availableMonths(Request $request)
    {
        $rows = DB::table('bills')
            ->select(
                DB::raw('EXTRACT(YEAR FROM created_at)::int as year'),
                DB::raw('EXTRACT(MONTH FROM created_at)::int as month')
            )
            ->distinct()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return response()->json(['data' => $rows]);
    }

    /**
     * Top selling dishes.
     * period = 'week'  -> tuần vừa qua (T2-CN, tính theo lịch)
     * period = 'month' -> tháng vừa qua (trọn tháng dương lịch trước)
     * period = null    -> lọc theo year/month cụ thể (mặc định tháng hiện tại)
     * Loại trừ đơn cancelled. Booking tính theo booking_date, ship tính theo created_at.
     */
    public function bestsellers(Request $request)
    {
        $period = $request->get('period');

        if ($period === 'week') {
            $start = now()->subWeek()->startOfWeek();
            $end = now()->subWeek()->endOfWeek();
        } elseif ($period === 'month') {
            $start = now()->subMonthNoOverflow()->startOfMonth();
            $end = now()->subMonthNoOverflow()->endOfMonth();
        } else {
            $year = $request->get('year', now()->year);
            $month = $request->get('month', now()->month);
            $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
            $end = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();
        }

        $rows = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.order_id')
            ->join('dishes', 'order_items.dish_id', '=', 'dishes.dish_id')
            ->leftJoin('booking_tables', 'orders.order_id', '=', 'booking_tables.order_id')
            ->leftJoin('deliveries', 'orders.order_id', '=', 'deliveries.order_id')
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($q2) use ($start, $end) {
                    $q2->where('orders.order_type', 'booking_table')
                        ->where('booking_tables.booking_status', '<>', 'cancelled')
                        ->whereBetween('booking_tables.booking_date', [$start->toDateString(), $end->toDateString()]);
                })->orWhere(function ($q2) use ($start, $end) {
                    $q2->where('orders.order_type', 'delivery')
                        ->where('deliveries.delivery_status', '<>', 'cancelled')
                        ->whereBetween('orders.created_at', [$start, $end]);
                });
            })
            ->select('dishes.dish_id', 'dishes.dish_name as name', DB::raw('SUM(order_items.quantity) as count'))
            ->groupBy('dishes.dish_id', 'dishes.dish_name')
            ->orderByDesc('count')
            ->limit(100)
            ->get();

        $result = $this->applyCompetitionRank($rows, 'count', 3);

        return response()->json(['data' => $result]);
    }

    /**
     * Gán "hạng thi đấu" (competition ranking: 1,1,3) theo trường $field,
     * rồi chỉ giữ lại các dòng có rank <= $topN. Đồng hạng thì giữ hết.
     */
    private function applyCompetitionRank($rows, string $field, int $topN)
    {
        $result = [];
        $rank = 0;
        $prevValue = null;
        $index = 0;

        foreach ($rows as $row) {
            $index++;
            $value = (float) $row->$field;

            if ($prevValue === null || $value != $prevValue) {
                $rank = $index;
                $prevValue = $value;
            }

            if ($rank > $topN) break;

            $row->rank = $rank;
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Top customers by spending.
     * period = 'week'  -> tuần vừa qua (T2-CN, tính theo lịch)
     * period = 'month' -> tháng vừa qua (trọn tháng dương lịch trước)
     * period = null    -> lọc theo year/month cụ thể (dùng cho trang Báo cáo)
     * Loại trừ bill của đơn đã cancelled.
     */
    public function customers(Request $request)
    {
        $period = $request->get('period');

        $query = DB::table('bills')
            ->join('orders', 'bills.order_id', '=', 'orders.order_id')
            ->join('users', 'orders.user_id', '=', 'users.user_id')
            ->leftJoin('booking_tables', 'orders.order_id', '=', 'booking_tables.order_id')
            ->leftJoin('deliveries', 'orders.order_id', '=', 'deliveries.order_id')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('orders.order_type', 'booking_table')
                        ->where('booking_tables.booking_status', '<>', 'cancelled');
                })->orWhere(function ($q2) {
                    $q2->where('orders.order_type', 'delivery')
                        ->where('deliveries.delivery_status', '<>', 'cancelled');
                });
            })
            ->select(
                'users.user_id',
                'users.username as name',
                DB::raw('SUM(bills.total_price) as total_spent')
            )
            ->groupBy('users.user_id', 'users.username')
            ->orderByDesc('total_spent');

        if ($period === 'week') {
            $start = now()->subWeek()->startOfWeek();
            $end = now()->subWeek()->endOfWeek();
            $query->whereBetween('bills.created_at', [$start, $end]);
        } elseif ($period === 'month') {
            $start = now()->subMonthNoOverflow()->startOfMonth();
            $end = now()->subMonthNoOverflow()->endOfMonth();
            $query->whereBetween('bills.created_at', [$start, $end]);
        } else {
            if ($request->has('year')) $query->whereYear('bills.created_at', $request->get('year'));
            if ($request->has('month')) $query->whereMonth('bills.created_at', $request->get('month'));
        }

        $rows = $query->limit(100)->get();

        $result = $this->applyCompetitionRank($rows, 'total_spent', 3);

        return response()->json(['data' => $result]);
    }
}
