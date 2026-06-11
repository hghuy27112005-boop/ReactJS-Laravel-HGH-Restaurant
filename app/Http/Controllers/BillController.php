<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Order;
use App\Models\Dish;
use App\Models\Discount;
use App\Models\Points;
use App\Models\Statistics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    /**
     * Get all bills for authenticated user
     */
    public function index(Request $request)
    {
        $query = $request->user()->bills();

        // Filter by order type
        if ($request->has('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bills = $query->with('orders.dish', 'delivery', 'bookingTable')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $bills->items(),
            'pagination' => [
                'total' => $bills->total(),
                'per_page' => $bills->perPage(),
                'current_page' => $bills->currentPage(),
                'last_page' => $bills->lastPage(),
            ],
        ]);
    }

    /**
     * Create new bill
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_type' => 'required|in:booking_table,delivery',
            'items' => 'required|array|min:1',
            'items.*.dish_id' => 'required|exists:dishes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price_at_order' => 'required|numeric|min:0',
            'table_number' => 'required_if:order_type,booking_table|integer|between:1,50',
            'booking_table.start_time' => 'required_if:order_type,booking_table|date',
            'booking_table.end_time' => 'required_if:order_type,booking_table|date|after:booking_table.start_time',
            'delivery.address' => 'required_if:order_type,delivery|string',
            'delivery.phone' => 'required_if:order_type,delivery|string',
        ]);

        DB::beginTransaction();
        try {
            // Create bill
            $bill = $request->user()->bills()->create([
                'bill_code' => $this->generateBillCode(),
                'order_type' => $validated['order_type'],
                'status' => 'pending',
                'is_paid' => false,
                'total_amount' => 0,
            ]);

            // Add order items
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                Order::create([
                    'bill_id' => $bill->id,
                    'dish_id' => $item['dish_id'],
                    'quantity' => $item['quantity'],
                    'price_at_order' => $item['price_at_order'],
                    'order_code' => $this->generateOrderCode(),
                    'order_type' => $validated['order_type'],
                ]);
                $totalAmount += $item['price_at_order'] * $item['quantity'];
            }

            // Create booking table if dine-in
            if ($validated['order_type'] === 'booking_table') {
                $bill->bookingTable()->create([
                    'table_number' => $validated['table_number'],
                    'start_time' => $validated['booking_table']['start_time'],
                    'end_time' => $validated['booking_table']['end_time'],
                ]);
            }

            // Create delivery if takeout
            if ($validated['order_type'] === 'delivery') {
                $deliveryCode = $this->generateDeliveryCode();
                $bill->delivery()->create([
                    'delivery_code' => $deliveryCode,
                    'address' => $validated['delivery']['address'],
                    'phone' => $validated['delivery']['phone'],
                    'status' => 'pending',
                    'final_price' => 0, // Will be calculated on payment
                ]);
            }

            // Update bill total
            $bill->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'data' => $bill->load('orders.dish', 'delivery', 'bookingTable'),
                'message' => 'Bill created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get bill details
     */
    public function show(Bill $bill)
    {
        $this->authorize('view', $bill);

        return response()->json([
            'data' => $bill->load('orders.dish', 'delivery', 'bookingTable'),
        ]);
    }

    /**
     * Update bill
     */
    public function update(Request $request, Bill $bill)
    {
        $this->authorize('update', $bill);

        $validated = $request->validate([
            'status' => 'in:pending,unpaid,completed,cancelled',
        ]);

        $bill->update($validated);

        return response()->json([
            'data' => $bill,
            'message' => 'Bill updated successfully',
        ]);
    }

    /**
     * Delete bill
     */
    public function destroy(Bill $bill)
    {
        $this->authorize('delete', $bill);

        $bill->delete();

        return response()->json([
            'message' => 'Bill deleted successfully',
        ]);
    }

    /**
     * Calculate total with discount
     */
    public function calculateTotal(Request $request, Bill $bill)
    {
        $this->authorize('view', $bill);

        $user = $request->user();
        $subtotal = $bill->orders->sum(function ($order) {
            return $order->price_at_order * $order->quantity;
        });

        // Get user discount
        $discount = Discount::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        $discountAmount = 0;
        if ($discount) {
            $discountAmount = floor($subtotal * ($discount->discount_percentage / 100));
        }

        $total = $subtotal - $discountAmount;

        // Add shipping for delivery
        if ($bill->order_type === 'delivery') {
            $shippingFee = config('app.shipping_fee', 5000);
            $total += $shippingFee;
        }

        return response()->json([
            'data' => [
                'subtotal' => $subtotal,
                'discount_percentage' => $discount?->discount_percentage ?? 0,
                'discount_amount' => $discountAmount,
                'shipping_fee' => $bill->order_type === 'delivery' ? 5000 : 0,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request, Bill $bill)
    {
        $this->authorize('update', $bill);

        $validated = $request->validate([
            'payment_method' => 'required|in:credit_card,cash,bank_transfer',
            'amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Update bill as paid
            $bill->update([
                'is_paid' => true,
                'status' => 'unpaid',
                'total_amount' => $validated['amount'],
            ]);

            // Calculate and add points
            $pointsEarned = Points::calculatePoints($validated['amount']);
            $points = Points::create([
                'user_id' => $request->user()->id,
                'bill_id' => $bill->id,
                'points_earned' => $pointsEarned,
                'booking_total_price' => $bill->order_type === 'booking_table' ? $validated['amount'] : 0,
                'delivery_total_price' => $bill->order_type === 'delivery' ? $validated['amount'] : 0,
            ]);

            // Update user points and membership
            $request->user()->incrementPoints($pointsEarned);

            // Update statistics
            $stats = $request->user()->statistics;
            $stats->incrementTotalOrders();
            $stats->addSpent($validated['amount']);
            $stats->addPoints($pointsEarned);

            if ($bill->order_type === 'booking_table') {
                $stats->increment('booking_orders');
            } else {
                $stats->increment('delivery_orders');
            }
            $stats->save();

            // Update delivery status if applicable
            if ($bill->delivery) {
                $bill->delivery->update([
                    'status' => 'approved',
                    'final_price' => $validated['amount'],
                    'approved_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'data' => [
                    'bill_id' => $bill->id,
                    'payment_status' => 'completed',
                    'transaction_id' => 'TXN' . time(),
                    'amount' => $validated['amount'],
                    'points_earned' => $pointsEarned,
                ],
                'message' => 'Payment processed successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate unique bill code
     */
    private function generateBillCode()
    {
        $date = date('dmy');
        $sequence = Bill::whereDate('created_at', today())->count() + 1;
        return $date . '_' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique order code
     */
    private function generateOrderCode()
    {
        $date = date('dmy');
        $sequence = Order::whereDate('created_at', today())->count() + 1;
        return $date . '_' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique delivery code
     */
    private function generateDeliveryCode()
    {
        $date = date('dmy');
        $sequence = Delivery::whereDate('created_at', today())->count() + 1;
        return $date . 'D' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
}
