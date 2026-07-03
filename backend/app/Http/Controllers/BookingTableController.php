<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\BookingTable;
use App\Models\Bill;
use App\Services\OrderCodeGenerator;

class BookingTableController extends Controller
{
    private const TYPE = 'dat-ban';

    /**
     * Load the checkout page for booking table
     */
    public function bookingCheckoutPage()
    {
        $userId = auth()->id();
        $type = self::TYPE;

        // Find pending booking for recovery if needed
        $pendingOrder = Order::where('user_id', $userId)
            ->where('order_type', 'booking')
            ->whereHas('bookings', function ($q) {
                $q->where('B_payment_status', 'unpaid');
            })
            ->with(['items.dish', 'bookings'])
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
                $cart = array_merge(
                    $cart,
                    $this->buildCartFromOrderItems(
                        $pendingOrder,
                        $type,
                        $pendingOrder->created_at->format('H:i d/m/Y')
                    )
                );
                session()->put('cart', $cart);
            }
        }

        $cart = session()->get('cart', []);
        $cartMode = self::TYPE;

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
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Bàn số ' . $num . ' đã được đặt trong khoảng thời gian này, vui lòng chọn bàn khác hoặc đổi thời gian.'
                    ], 422);
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Lỗi kiểm tra trùng lịch: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process checkout for booking (bước "Xác nhận thông tin đặt bàn" — chưa thanh toán)
     */
    public function processBookingCheckout(Request $request)
    {
        $type = self::TYPE;
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

        // Validate overlap again — kiểm tra TỪNG bàn trong danh sách đã chọn
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

            $isEditingPendingOrder = $bill
                && $bill->order
                && optional($bill->order->bookings->first())->B_payment_status === 'unpaid';

            if ($isEditingPendingOrder) {
                $order = $bill->order;
                $order->items()->delete();
                $order->update(['subtotal_price' => $totalAmount]);
                $bill->update(['total_price' => $totalAmount]);

                // Xóa hết các dòng bàn cũ rồi lưu lại đầy đủ danh sách bàn mới
                $order->bookings()->delete();
                $generator = new OrderCodeGenerator();
                foreach ($tables as $index => $t) {
                    $bookingId = $generator->generateBookingId($bookingDate, BookingTable::where('booking_date', $bookingDate)->count() + $index + 1);
                    BookingTable::create([
                        'booking_id' => $bookingId,
                        'order_id' => $order->order_id,
                        'table_number' => $t['number'],
                        'booking_date' => $bookingDate,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'B_payment_status' => 'unpaid',
                        'booking_status' => 'pending'
                    ]);
                }
            } else {
                $generator = new OrderCodeGenerator();
                $orderId = $generator->generateOrderId(today()->toDateString(), Order::whereDate('created_at', today())->count() + 1);

                $order = Order::create([
                    'order_id' => $orderId,
                    'user_id' => auth()->id(),
                    'order_type' => 'booking',
                    'subtotal_price' => $totalAmount
                ]);

                $firstBookingId = null;
                $bookingCountForDate = BookingTable::where('booking_date', $bookingDate)->count();

                // Lưu ĐẦY ĐỦ tất cả các bàn đã chọn (không chỉ bàn đầu tiên)
                foreach ($tables as $index => $t) {
                    $bookingId = $generator->generateBookingId($bookingDate, $bookingCountForDate + $index + 1);
                    if ($index === 0) {
                        $firstBookingId = $bookingId;
                    }

                    BookingTable::create([
                        'booking_id' => $bookingId,
                        'order_id' => $order->order_id,
                        'table_number' => $t['number'],
                        'booking_date' => $bookingDate,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'B_payment_status' => 'unpaid',
                        'booking_status' => 'pending'
                    ]);
                }

                $bill = Bill::create([
                    'bill_id' => $generator->generateBillId('booking', $firstBookingId ?? $order->order_id),
                    'order_id' => $order->order_id,
                    'total_price' => $totalAmount,
                    'payment_method' => 'unpaid'
                ]);
            }

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

    /**
     * Bước "Thanh toán" — xử lý khi người dùng bấm nút Thanh Toán.
     * Vì chưa tích hợp cổng thanh toán thật, hàm này giả lập thanh toán
     * thành công, nhưng vẫn kiểm tra overlap LẦN CUỐI trước khi chốt,
     * để xử lý đúng trường hợp 2 người cùng đặt trùng bàn/giờ.
     */
    public function processBookingPayment(Request $request)
    {
        $type = self::TYPE;

        try {
            DB::beginTransaction();

            $billId = $request->input('bill_code') ?: session()->get('last_bill_code_' . $type);

            if (!$billId) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy hóa đơn. Vui lòng xác nhận thông tin đặt bàn trước.'
                ], 400);
            }

            $bill = Bill::with(['order.bookings', 'order.items.dish'])
                ->where('bill_id', $billId)
                ->first();

            if (!$bill || !$bill->order || $bill->order->bookings->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hóa đơn hoặc thông tin đặt bàn không tồn tại.'
                ], 404);
            }

            // Đã thanh toán trước đó (double click / reload) -> trả success luôn, tránh xử lý 2 lần
            if ($bill->order->bookings->first()->B_payment_status === 'paid') {
                DB::rollBack();
                session(['paid_' . $type => true]);
                return response()->json(['status' => 'success']);
            }

            // KIỂM TRA LẦN CUỐI: kiểm tra TỪNG bàn trong đơn xem có ai vừa thanh toán trùng không
            foreach ($bill->order->bookings as $bt) {
                $overlap = DB::table('booking_tables')
                    ->join('bills', 'booking_tables.order_id', '=', 'bills.order_id')
                    ->where('booking_tables.order_id', '!=', $bill->order_id)
                    ->where('booking_tables.table_number', $bt->table_number)
                    ->where('booking_tables.booking_date', $bt->booking_date)
                    ->where('booking_tables.B_payment_status', 'paid')
                    ->where('booking_tables.booking_status', '!=', 'cancelled')
                    ->whereRaw("booking_tables.start_time < ? AND booking_tables.end_time > ?", [
                        $bt->end_time,
                        $bt->start_time
                    ])
                    ->select('bills.bill_id')
                    ->first();

                if ($overlap) {
                    // ROLLBACK: khôi phục lại giỏ hàng cho người dùng
                    $cart = session()->get('cart', []);
                    $cart = array_merge($cart, $this->buildCartFromOrderItems($bill->order, $type));

                    session()->put('cart', $cart);
                    session()->put('last_confirmed_' . $type, false);
                    // KHÔNG forget last_bill_code_{$type} để processBookingCheckout
                    // tìm thấy đơn cũ và ghi đè (update) thay vì tạo mới.

                    DB::commit();

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Rất tiếc, bàn số ' . $bt->table_number .
                            ' vừa có người khác thanh toán trước trong khung giờ bạn chọn. Giỏ hàng của bạn đã được khôi phục, vui lòng chọn lại bàn/giờ khác!'
                    ], 422);
                }
            }

            // Không có xung đột -> giả lập thanh toán thành công (chưa có cổng thanh toán thật)
            foreach ($bill->order->bookings as $bt) {
                $bt->update([
                    'B_payment_status' => 'paid',
                    'booking_status' => 'confirmed',
                ]);
            }

            $bill->update([
                'payment_method' => $request->input('payment_method', 'cash'),
            ]);

            DB::commit();

            session(['paid_' . $type => true]);

            // Dọn dẹp session đặt bàn vì đã hoàn tất
            session()->forget([
                'table_numbers',
                'tables_detail',
                'start_date',
                'start_time',
                'end_time',
                'total_tables',
                'types',
                'table_number'
            ]);

            return response()->json(['status' => 'success']);

        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            \Log::error('processBookingPayment failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi xử lý thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: build cart entries từ các món trong 1 Order, dùng chung cho
     * cả 2 trường hợp khôi phục (load lại trang & xung đột lúc thanh toán)
     * để tránh lặp code.
     */
    private function buildCartFromOrderItems($order, string $type, ?string $createdAt = null): array
    {
        $createdAt = $createdAt ?? now()->format('H:i d/m/Y');
        $items = [];

        foreach ($order->items as $d) {
            $cartKey = $d->dish_id . '_' . $type;
            $items[$cartKey] = [
                "dish_id" => $d->dish_id,
                "name" => $d->dish->dish_name ?? 'Món ăn đã bị xóa',
                "quantity" => $d->quantity,
                "price" => $d->unit_price,
                "order_type" => $type,
                "note" => null,
                "created_at" => $createdAt
            ];
        }

        return $items;
    }
}