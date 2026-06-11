<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Bill;
use App\Models\Dish;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Get all orders for a bill
     */
    public function index(Request $request)
    {
        $billId = $request->query('bill_id');
        
        if ($billId) {
            $bill = Bill::findOrFail($billId);
            $this->authorize('view', $bill);
            
            $orders = $bill->orders()->with('dish')->get();
        } else {
            $orders = Order::with('bill', 'dish')->paginate(20);
        }

        return response()->json([
            'data' => $orders,
        ]);
    }

    /**
     * Create order item
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bill_id' => 'required|exists:bills,id',
            'dish_id' => 'required|exists:dishes,id',
            'quantity' => 'required|integer|min:1',
            'price_at_order' => 'required|numeric|min:0',
            'order_type' => 'required|in:booking_table,delivery',
        ]);

        $bill = Bill::findOrFail($validated['bill_id']);
        $this->authorize('update', $bill);

        // Check if dish exists and is available
        $dish = Dish::findOrFail($validated['dish_id']);

        $order = Order::create([
            'bill_id' => $validated['bill_id'],
            'dish_id' => $validated['dish_id'],
            'quantity' => $validated['quantity'],
            'price_at_order' => $validated['price_at_order'],
            'order_code' => $this->generateOrderCode(),
            'order_type' => $validated['order_type'],
        ]);

        // Update bill total
        $newTotal = $bill->orders()->sum('price_at_order');
        $bill->update(['total_amount' => $newTotal]);

        return response()->json([
            'data' => $order->load('dish'),
            'message' => 'Order item created successfully',
        ], 201);
    }

    /**
     * Get order details
     */
    public function show(Order $order)
    {
        return response()->json([
            'data' => $order->load('bill', 'dish'),
        ]);
    }

    /**
     * Update order
     */
    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order->bill);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'price_at_order' => 'required|numeric|min:0',
        ]);

        $order->update($validated);

        // Recalculate bill total
        $newTotal = $order->bill->orders()->sum(function ($o) {
            return $o->price_at_order * $o->quantity;
        });
        $order->bill->update(['total_amount' => $newTotal]);

        return response()->json([
            'data' => $order->load('dish'),
            'message' => 'Order updated successfully',
        ]);
    }

    /**
     * Delete order
     */
    public function destroy(Order $order)
    {
        $this->authorize('update', $order->bill);

        $bill = $order->bill;
        $order->delete();

        // Recalculate bill total
        $newTotal = $bill->orders()->sum(function ($o) {
            return $o->price_at_order * $o->quantity;
        });
        $bill->update(['total_amount' => $newTotal]);

        return response()->json([
            'message' => 'Order item deleted successfully',
        ]);
    }

    /**
     * Add to bill (shorthand)
     */
    public function addToBill(Request $request, Bill $bill)
    {
        $this->authorize('update', $bill);

        $validated = $request->validate([
            'dish_id' => 'required|exists:dishes,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $dish = Dish::findOrFail($validated['dish_id']);

        $order = Order::create([
            'bill_id' => $bill->id,
            'dish_id' => $validated['dish_id'],
            'quantity' => $validated['quantity'],
            'price_at_order' => $dish->price,
            'order_code' => $this->generateOrderCode(),
            'order_type' => $bill->order_type,
        ]);

        // Update bill total
        $newTotal = $bill->orders()->sum(function ($o) {
            return $o->price_at_order * $o->quantity;
        });
        $bill->update(['total_amount' => $newTotal]);

        return response()->json([
            'data' => $order->load('dish'),
            'message' => 'Item added to bill successfully',
        ], 201);
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
}
