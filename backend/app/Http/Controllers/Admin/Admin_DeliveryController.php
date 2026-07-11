<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Stock;
use App\Services\OrderCodeGenerator;
use Illuminate\Http\Request;

class Admin_DeliveryController extends Controller
{
    /**
     * Get all deliveries (admin view)
     */
    public function index(Request $request)
    {
        $query = Delivery::with('order.user', 'order.items.dish', 'order.bill')
            ->whereIn('delivery_status', ['waiting_approval', 'shipping', 'completed', 'cancelled']);

        // Filter by delivery status (thu hẹp thêm trong phạm vi 4 trạng thái cho phép ở trên)
        if ($request->has('delivery_status')) {
            $statuses = explode(',', $request->delivery_status);
            $query->whereIn('delivery_status', $statuses);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('D_payment_status', $request->payment_status);
        }

        // Filter by date
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by delivery_id or customer name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('delivery_id', 'like', '%' . $search . '%')
                    ->orWhereHas('order.user', function ($subq) use ($search) {
                        $subq->where('username', 'like', '%' . $search . '%');
                    });
            });
        }

        $deliveries = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $deliveries->items(),
            'pagination' => [
                'total' => $deliveries->total(),
                'per_page' => $deliveries->perPage(),
                'current_page' => $deliveries->currentPage(),
                'last_page' => $deliveries->lastPage(),
            ],
        ]);
    }

    /**
     * Thống kê tổng số lượng theo từng trạng thái (dùng COUNT, không load toàn bộ record)
     */
    public function stats(Request $request)
    {
        $base = Delivery::whereIn('delivery_status', ['waiting_approval', 'shipping', 'completed', 'cancelled']);

        if ($request->has('date_from')) {
            $base->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $base->whereDate('created_at', '<=', $request->date_to);
        }

        $total = (clone $base)->count();
        $pending = (clone $base)->whereIn('delivery_status', ['waiting_info', 'waiting_confirmation', 'waiting_approval'])->count();
        $shipping = (clone $base)->where('delivery_status', 'shipping')->count();
        $completed = (clone $base)->where('delivery_status', 'completed')->count();
        $cancelled = (clone $base)->where('delivery_status', 'cancelled')->count();

        return response()->json([
            'data' => [
                'total' => $total,
                'pending' => $pending,
                'shipping' => $shipping,
                'completed' => $completed,
                'cancelled' => $cancelled,
            ],
        ]);
    }

    /**
     * Get single delivery
     */
    public function show(Delivery $delivery)
    {
        return response()->json([
            'data' => $delivery->load('order.user'),
        ]);
    }

    /**
     * Create delivery (store)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|string|exists:orders,order_id',
            'address' => 'required|string',
        ]);

        $generator = new OrderCodeGenerator();
        $sequence = Delivery::whereDate('created_at', today())->count() + 1;
        $deliveryId = $generator->generateDeliveryId(today()->toDateString(), $sequence);

        $delivery = Delivery::create([
            'delivery_id' => $deliveryId,
            'order_id' => $validated['order_id'],
            'address' => $validated['address'],
            'D_payment_status' => 'unpaid',
            'delivery_status' => 'waiting_info',
        ]);

        return response()->json([
            'data' => $delivery->load('order.user'),
            'message' => 'Delivery created successfully',
        ], 201);
    }

    /**
     * Approve delivery (confirm delivery info)
     */
    public function approve(Request $request, Delivery $delivery)
    {
        if ($delivery->delivery_status !== 'waiting_info') {
            return response()->json([
                'message' => 'Chỉ có thể xác nhận giao hàng ở trạng thái "Chờ thông tin"',
            ], 422);
        }

        $delivery->update([
            'delivery_status' => 'waiting_confirmation',
            'approved_at' => now(),
        ]);

        return response()->json([
            'data' => $delivery,
            'message' => 'Delivery approved successfully',
        ]);
    }

    /**
     * Start delivery (shipping)
     */
    public function startDelivery(Request $request, Delivery $delivery)
    {
        // Kiểm tra điều kiện để bắt đầu giao hàng
        if (!in_array($delivery->delivery_status, ['waiting_confirmation', 'waiting_payment', 'waiting_approval'])) {
            return response()->json([
                'message' => 'Không thể bắt đầu giao hàng từ trạng thái hiện tại',
            ], 422);
        }

        $delivery->update([
            'delivery_status' => 'shipping',
        ]);

        if ($delivery->order) {
            Stock::decrementStockForOrder($delivery->order, now()->format('Y-m-d'));
            Stock::refillIfLowForOrder($delivery->order, now()->format('Y-m-d'));
        }

        return response()->json([
            'data' => $delivery,
            'message' => 'Delivery started successfully',
        ]);
    }

    /**
     * Complete delivery
     */
    public function completeDelivery(Request $request, Delivery $delivery)
    {
        if ($delivery->delivery_status !== 'shipping') {
            return response()->json([
                'message' => 'Chỉ có thể hoàn thành giao hàng ở trạng thái "Đang giao"',
            ], 422);
        }

        $delivery->update([
            'delivery_status' => 'completed',
            'delivered_at' => now(),
        ]);

        return response()->json([
            'data' => $delivery,
            'message' => 'Delivery completed successfully',
        ]);
    }

    /**
     * Cancel delivery
     */
    public function cancel(Request $request, Delivery $delivery)
    {
        if ($delivery->delivery_status === 'completed' || $delivery->delivery_status === 'cancelled') {
            return response()->json([
                'message' => 'Không thể hủy giao hàng đã hoàn thành hoặc đã hủy',
            ], 422);
        }

        $delivery->update([
            'delivery_status' => 'cancelled',
        ]);

        return response()->json([
            'data' => $delivery,
            'message' => 'Delivery cancelled successfully',
        ]);
    }
}
