<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Delivery;
use App\Models\Bill;
use App\Services\OrderCodeGenerator;

class DeliveryController extends Controller
{
    /**
     * Load the checkout page for delivery
     */
    public function deliveryCheckoutPage()
    {
        $userId = auth()->id();
        $type = 'mang-ve';

        // Find pending deliveries for recovery if needed
        $pendingOrder = Order::where('user_id', $userId)
            ->where('order_type', 'delivery')
            ->whereHas('delivery', function ($q) {
                $q->where('D_payment_status', 'unpaid');
            })
            ->with(['items.dish', 'delivery'])
            ->first();

        if ($pendingOrder && !session()->has("last_confirmed_{$type}")) {
            session(["last_confirmed_{$type}" => true]);
            session(["last_bill_code_{$type}" => $pendingOrder->bill->bill_id ?? '']);

            $cart = session()->get('cart', []);
            $hasItems = false;
            foreach ($cart as $item) {
                if (($item['order_type'] ?? '') === $type) {
                    $hasItems = true;
                    break;
                }
            }

            if (!$hasItems) {
                foreach ($pendingOrder->items as $d) {
                    $cartKey = $d->dish_id . '_' . $type;
                    $cart[$cartKey] = [
                        "dish_id" => $d->dish_id,
                        "name" => $d->dish->dish_name,
                        "quantity" => $d->quantity,
                        "price" => $d->unit_price,
                        "order_type" => $type,
                        "note" => null,
                        "created_at" => $pendingOrder->created_at->format('H:i d/m/Y')
                    ];
                }
                session()->put('cart', $cart);
            }

            if ($pendingOrder->delivery && $pendingOrder->delivery->address) {
                session(['user_address' => $pendingOrder->delivery->address]);
            }
        }

        $cart = session()->get('cart', []);

        // Lấy danh sách đơn giao hàng đang hoạt động của user (không bao gồm đơn chưa thanh toán/chưa xác nhận)
        $activeOrders = Order::where('user_id', $userId)
            ->where('order_type', 'delivery')
            ->whereHas('delivery', function ($q) {
                $q->whereIn('delivery_status', ['waiting_confirmation', 'waiting_delivery', 'delivering', 'delivered', 'cancelled']);
            })
            ->with(['items.dish', 'delivery', 'bill'])
            ->orderByDesc('created_at')
            ->get();

        return view('delivery', compact('cart', 'activeOrders'));
    }

    /**
     * Save temporary address to session
     */
    public function saveAddress(Request $request)
    {
        session(['user_address' => $request->address]);
        return response()->json(['status' => 'success']);
    }

    /**
     * Process checkout for delivery
     */
    public function processDeliveryCheckout(Request $request)
    {
        $type = 'mang-ve';
        $cart = session()->get('cart', []);

        $itemsToConfirm = array_filter($cart, function ($item) use ($type) {
            return ($item['order_type'] ?? '') === $type;
        });

        if (empty($itemsToConfirm)) {
            return response()->json(['status' => 'error', 'message' => 'Giỏ hàng trống!'], 400);
        }

        try {
            DB::beginTransaction();

            $totalAmount = array_reduce($itemsToConfirm, function ($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0);

            $oldBillCode = session('last_bill_code_' . $type);
            $bill = Bill::where('bill_id', $oldBillCode)->first();

            if ($bill && $bill->order && $bill->order->delivery && $bill->order->delivery->D_payment_status == 'unpaid') {
                $order = $bill->order;
                // Delete old items
                $order->items()->delete();
                $order->update(['subtotal_price' => $totalAmount]);
                $bill->update(['total_price' => $totalAmount]);
                $order->delivery->update(['address' => $request->address]);
            } else {
                $generator = new OrderCodeGenerator();
                $orderId = $generator->generateOrderId(today()->toDateString(), Order::whereDate('created_at', today())->count() + 1);

                // Create Order
                $order = Order::create([
                    'order_id' => $orderId,
                    'user_id' => auth()->id(),
                    'order_type' => 'delivery',
                    'subtotal_price' => $totalAmount
                ]);

                // Create Delivery
                $delivery = Delivery::create([
                    'delivery_id' => $generator->generateDeliveryId(today()->toDateString(), Delivery::whereDate('created_at', today())->count() + 1),
                    'order_id' => $order->order_id,
                    'address' => $request->address,
                    'D_payment_status' => 'unpaid',
                    'delivery_status' => 'waiting_info'
                ]);

                // Create Bill
                $bill = Bill::create([
                    'bill_id' => $generator->generateBillId('delivery', $delivery->delivery_id),
                    'order_id' => $order->order_id,
                    'total_price' => $totalAmount,
                    'payment_method' => 'unpaid'
                ]);
            }

            // Insert new items
            foreach ($itemsToConfirm as $item) {
                OrderItem::create([
                    'order_id' => $order->order_id,
                    'dish_id' => $item['dish_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price']
                ]);
            }

            DB::commit();

            session()->put('last_confirmed_' . $type, true);
            session()->put('last_bill_code_' . $type, $bill->bill_id);

            $newCart = array_filter($cart, function ($item) use ($type) {
                return ($item['order_type'] ?? '') !== $type;
            });
            session()->put('cart', $newCart);

            return response()->json(['status' => 'success', 'bill_id' => $bill->bill_id]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            \Log::error('Delivery Checkout failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Lỗi lưu đơn hàng: ' . $e->getMessage()], 500);
        }
    }
}
