<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\BookingTable;
use App\Models\Bill;

class BookingTableController extends Controller
{
    /**
     * Load the checkout page for booking table
     */
    public function bookingCheckoutPage()
    {
        $userId = auth()->id();
        $type = 'dat-ban';

        // Find pending booking for recovery if needed
        $pendingOrder = Order::where('user_id', $userId)
            ->where('order_type', 'booking')
            ->whereHas('bookingTable', function ($q) {
                $q->where('B_payment_status', 'unpaid');
            })
            ->with(['items.dish', 'bookingTable'])
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
        }

        $cart = session()->get('cart', []);
        $cartMode = 'dat-ban';

        return view('booking_table', compact('cart', 'cartMode'));
    }

    /**
     * Save temporary booking details to session
     */
    public function saveBooking(Request $request)
    {
        $tables = $request->tables; // array of {number, type}
        if (!$tables || !is_array($tables)) {
            return response()->json(['status' => 'error', 'message' => 'Dữ liệu bàn trống!'], 400);
        }

        $tableNumbers = array_map(fn($t) => $t['number'], $tables);

        session([
            'table_numbers' => $tableNumbers,
            'tables_detail' => $tables,
            'start_date' => $request->start_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'table_number' => $tableNumbers[0] ?? null,
            'total_tables' => count($tables)
        ]);
        return response()->json(['status' => 'success']);
    }

    /**
     * Check if tables overlap with existing paid/active bookings
     */
    public function checkMultiOverlap(Request $request)
    {
        try {
            $date = $request->date;
            $maxDate = now()->addDays(60)->format('Y-m-d');
            if ($date > $maxDate) {
                return response()->json(['status' => 'error', 'message' => 'Bạn chỉ có thể đặt bàn trong vòng 60 ngày tới!'], 422);
            }

            $startTime = $request->start_time;
            $endTime = $request->end_time;
            $tables = $request->tables;

            if (!$tables || !is_array($tables)) {
                return response()->json(['status' => 'success']);
            }

            foreach ($tables as $num) {
                $overlap = DB::table('booking_tables')
                    ->join('bills', 'booking_tables.order_id', '=', 'bills.order_id')
                    ->where('booking_tables.table_number', (int) $num)
                    ->where('booking_tables.B_payment_status', 'paid')
                    ->where('booking_tables.booking_status', '!=', 'cancelled')
                    ->where('booking_tables.booking_date', $date)
                    ->whereRaw("booking_tables.start_time < ? AND booking_tables.end_time > ?", [$endTime, $startTime])
                    ->first();

                if ($overlap) {
                    $readableStart = \Carbon\Carbon::parse($overlap->start_time)->format('H:i');
                    $readableEnd = \Carbon\Carbon::parse($overlap->end_time)->format('H:i');
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Bàn số ' . $num . ' đã có người đặt từ ' . $readableStart . ' đến ' . $readableEnd
                    ], 422);
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Lỗi kiểm tra trùng lịch: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process checkout for booking
     */
    public function processBookingCheckout(Request $request)
    {
        $type = 'dat-ban';
        $cart = session()->get('cart', []);

        $itemsToConfirm = array_filter($cart, function ($item) use ($type) {
            return ($item['order_type'] ?? '') === $type;
        });

        if (empty($itemsToConfirm)) {
            return response()->json(['status' => 'error', 'message' => 'Giỏ hàng trống!'], 400);
        }

        $bookingDate = $request->input('start_date') ?? session('start_date');
        $startTime = $request->start_time ?? session('start_time');
        $endTime = $request->end_time ?? session('end_time');
        $tables = session('tables_detail', []);

        if (empty($tables)) {
            return response()->json(['status' => 'error', 'message' => 'Vui lòng chọn bàn trước khi xác nhận!'], 400);
        }

        // Validate overlap again
        foreach ($tables as $t) {
            $overlap = DB::table('booking_tables')
                ->join('bills', 'booking_tables.order_id', '=', 'bills.order_id')
                ->where('booking_tables.table_number', $t['number'])
                ->where('booking_tables.B_payment_status', 'paid')
                ->where('booking_tables.booking_status', '!=', 'cancelled')
                ->where('booking_tables.booking_date', $bookingDate)
                ->whereRaw("booking_tables.start_time < ? AND booking_tables.end_time > ?", [$endTime, $startTime])
                ->exists();

            if ($overlap) {
                return response()->json(['status' => 'error', 'message' => 'Bàn số ' . $t['number'] . ' đã có người đặt trong khung giờ bạn chọn.'], 422);
            }
        }

        try {
            DB::beginTransaction();

            $totalAmount = array_reduce($itemsToConfirm, function ($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0);

            $oldBillCode = session('last_bill_code_' . $type);
            $bill = Bill::where('bill_id', $oldBillCode)->first();

            // We handle only 1 table per order in the new schema (BookingTable has order_id).
            // If they chose multiple tables, we might need multiple orders, or change DB.
            // Assuming 1 table for simplicity as per DB: `booking_tables` (booking_id, order_id, table_number).
            // If multiple, just use the first one for now or adjust DB later.
            $primaryTableNumber = $tables[0]['number'];

            if ($bill && $bill->order && $bill->order->bookingTable && $bill->order->bookingTable->B_payment_status == 'unpaid') {
                $order = $bill->order;
                $order->items()->delete();
                $order->update(['subtotal_price' => $totalAmount]);
                $bill->update(['total_price' => $totalAmount]);
                $order->bookingTable->update([
                    'table_number' => $primaryTableNumber,
                    'booking_date' => $bookingDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime
                ]);
            } else {
                // Create Order
                $order = Order::create([
                    'user_id' => auth()->id(),
                    'order_type' => 'booking',
                    'subtotal_price' => $totalAmount
                ]);

                // Create BookingTable
                BookingTable::create([
                    'order_id' => $order->order_id,
                    'table_number' => $primaryTableNumber,
                    'booking_date' => $bookingDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'B_payment_status' => 'unpaid',
                    'booking_status' => 'pending'
                ]);

                // Create Bill
                $bill = Bill::create([
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

            return response()->json(['status' => 'success']);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            \Log::error('Booking Checkout failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Lỗi lưu đơn hàng: ' . $e->getMessage()], 500);
        }
    }
}
