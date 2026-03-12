<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Models\Bill;
use App\Models\BillDetail;
use Carbon\Carbon;

class CartController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        // CỨU HỘ ĐƠN HÀNG KẸT: Tìm đơn hàng chưa thanh toán để nạp lại session
        $pendingBills = Bill::where('user_id', $userId)
            ->where('is_paid', false)
            ->where('status', 'pending')
            ->get();

        foreach ($pendingBills as $bill) {
            $type = $bill->order_type;

            // Nếu session bị mất (do login/logout) thì phục hồi từ DB
            if (!session("last_confirmed_{$type}")) {
                session(["last_confirmed_{$type}" => true]);
                session(["last_bill_code_{$type}" => $bill->bill_code]);

                // Nạp món ăn vào giỏ nếu giỏ đang trống loại đơn này
                $cart = session()->get('cart', []);
                $hasItems = false;
                foreach ($cart as $item) {
                    if (($item['order_type'] ?? '') === $type) {
                        $hasItems = true;
                        break;
                    }
                }

                if (!$hasItems) {
                    $details = $bill->details()->with('dish')->get();
                    foreach ($details as $d) {
                        $cartKey = $d->dish_id . '_' . $type;
                        $cart[$cartKey] = [
                            "dish_id" => $d->dish_id,
                            "name" => $d->dish->dish_name,
                            "quantity" => $d->quantity,
                            "price" => $d->price_at_time,
                            "order_type" => $type,
                            "note" => $d->note,
                            "created_at" => $bill->created_at->format('H:i d/m/Y')
                        ];
                    }
                    session()->put('cart', $cart);
                }

                // Phục hồi địa chỉ/thông tin bàn
                if ($type === 'mang-ve' && $bill->address) {
                    session(['user_address' => $bill->address]);
                }
            }
        }

        $cart = session()->get('cart', []);
        return view('cart', compact('cart'));
    }

    public function addToCart(Request $request)
    {
        $id = $request->dish_id;
        $type = $request->order_type;

        if (session('last_confirmed_' . $type) && !session('paid_' . $type)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bạn có đơn hàng [' . ($type == 'mang-ve' ? 'Mang về' : 'Tại bàn') . '] đang chờ thanh toán. Vui lòng hoàn tất thanh toán trước khi thêm món mới!'
            ], 403);
        }

        $cart = session()->get('cart', []);
        $cartKey = $id . '_' . $type;

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $request->quantity;
            $cart[$cartKey]['created_at'] = now()->format('H:i d/m/Y');
            if ($request->note) {
                $cart[$cartKey]['note'] = $request->note;
            }
        }
        else {
            $cart[$cartKey] = [
                "dish_id" => $id,
                "name" => $request->dish_name,
                "quantity" => (int)$request->quantity,
                "price" => $request->price,
                "order_type" => $type,
                "note" => $request->note ?? 'Không có',
                "created_at" => now()->format('H:i d/m/Y')
            ];
        }

        session()->put('cart', $cart);
        session()->forget(['last_confirmed_' . $type, 'last_bill_code_' . $type, 'paid_' . $type]);

        return response()->json(['message' => 'Thành công!', 'cart_count' => count($cart)]);
    }

    public function updateCart(Request $request)
    {
        $cart = session()->get('cart', []);
        if ($request->has('updates')) {
            foreach ($request->updates as $update) {
                $key = $update['key'];
                if (isset($cart[$key])) {
                    $qty = (int)$update['quantity'];
                    if ($qty < 0)
                        $qty = 0; // Không cho âm
                    if ($qty == 0)
                        unset($cart[$key]); // Xóa nếu bằng 0
                    else
                        $cart[$key]['quantity'] = $qty;
                }
            }
            session()->put('cart', $cart);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }

    public function updateCartQuantities(Request $request)
    {
        $cart = session()->get('cart', []);
        $quantities = $request->input('quantities', []);

        foreach ($quantities as $id => $qty) {
            $qty = (int)$qty;
            if ($qty < 0)
                $qty = 0; // Không cho âm
            if ($qty == 0) {
                unset($cart[$id]);
            }
            else {
                if (isset($cart[$id])) {
                    $cart[$id]['quantity'] = $qty;
                }
            }
        }

        session()->put('cart', $cart);
        return response()->json(['success' => true]);
    }

    public function checkout(Request $request)
    {
        $type = $request->order_type;
        $cart = session()->get('cart', []);

        $itemsToConfirm = array_filter($cart, function ($item) use ($type) {
            return ($item['order_type'] ?? '') === $type;
        });

        if (empty($itemsToConfirm)) {
            return response()->json(['status' => 'error', 'message' => 'Giỏ hàng trống!'], 400);
        }

        $isTakeaway = ($request->order_type === 'mang-ve');

        if (!$isTakeaway) {
            $tables = session('tables_detail', []);
            $date = $request->start_date ?? session('start_date');
            $startTime = $request->start_time ?? session('start_time');
            $endTime = $request->end_time ?? session('end_time');

            $startDateTime = $date . ' ' . $startTime . ':00';
            $endDateTime = $date . ' ' . $endTime . ':00';

            foreach ($tables as $t) {
                $overlap = DB::table('booking_tables')
                    ->join('bills', 'booking_tables.bill_id', '=', 'bills.id')
                    ->where('bills.is_paid', true)
                    ->where('bills.status', '!=', 'cancelled')
                    ->where('booking_tables.table_number', $t['number'])
                    ->where(function ($query) use ($startDateTime, $endDateTime) {
                    $query->where('booking_tables.start_time', '<', $endDateTime)
                        ->where('booking_tables.end_time', '>', $startDateTime);
                })
                    ->exists();

                if ($overlap) {
                    return response()->json(['status' => 'error', 'message' => 'Bàn số ' . $t['number'] . ' đã có người đặt trong khung giờ bạn chọn.'], 422);
                }
            }
        }

        try {
            DB::beginTransaction();

            $targetBookingDate = $isTakeaway ? now()->format('Y-m-d') : session('start_date');

            $totalAmount = array_reduce($itemsToConfirm, function ($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0);

            $billData = [
                'customer_name' => $request->customer_name ?? 'Khách hàng',
                'user_id' => auth()->id(), // LƯU ID NGƯỜI DÙNG ĐANG ĐĂNG NHẬP
                'order_type' => $request->order_type,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'is_paid' => false,
                'payment_method' => $request->payment_method,
                'paid_at' => null,
                'address' => $isTakeaway ? $request->address : null,
                'table_number' => $isTakeaway ? null : (session('table_numbers') ? implode(', ', collect(session('table_numbers'))->sort()->values()->all()) : null),
                'booking_date' => $targetBookingDate,
                'arrival_time' => $isTakeaway ? null : session('start_time'),
                'finish_time' => $isTakeaway ? null : session('end_time'),
            ];

            $oldBillCode = session('last_bill_code_' . $type);
            $bill = Bill::where('bill_code', $oldBillCode)->where('is_paid', false)->first();

            if ($bill) {
                $bill->update($billData);
                $bill->details()->delete();
                $bill->bookings()->delete(); // DỌN DẸP BÀN CŨ TRƯỚC KHI LƯU BÀN MỚI
                $customBillCode = $bill->bill_code;
            }
            else {
                $dateSuffix = \Carbon\Carbon::parse($targetBookingDate)->format('dmY');
                $maxOrder = Bill::where('booking_date', $targetBookingDate)->max('order_in_day') ?? 0;
                $orderInDay = $maxOrder + 1;
                $customBillCode = str_pad($orderInDay, 3, '0', STR_PAD_LEFT) . $dateSuffix;

                $bill = Bill::create(array_merge($billData, [
                    'bill_code' => $customBillCode,
                    'order_in_day' => $orderInDay
                ]));
            }

            foreach ($itemsToConfirm as $item) {
                BillDetail::create([
                    'bill_id' => $bill->id,
                    'dish_id' => $item['dish_id'],
                    'quantity' => $item['quantity'],
                    'price_at_time' => $item['price'],
                    'note' => $item['note'] ?? 'Không có',
                ]);
            }

            if (!$isTakeaway) {
                $tables = session('tables_detail', []);
                foreach ($tables as $t) {
                    \App\Models\BookingTable::create([
                        'bill_id' => $bill->id,
                        'table_number' => $t['number'],
                        'start_time' => session('start_date') . ' ' . session('start_time') . ':00',
                        'end_time' => session('start_date') . ' ' . session('end_time') . ':00',
                    ]);
                }
            }

            session()->put('last_confirmed_' . $type, true);
            session()->put('last_bill_code_' . $type, $customBillCode);

            $newCart = array_filter($cart, function ($item) use ($type) {
                return ($item['order_type'] ?? '') !== $type;
            });
            session()->put('cart', $newCart);

            DB::commit();
            return response()->json(['status' => 'success']);
        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function processPayment(Request $request)
    {
        try {
            $type = (string)$request->order_type;
            $billCode = (string)($request->input('bill_code') ?: session()->get('last_bill_code_' . $type));

            if (!$billCode) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy hóa đơn. Vui lòng xác nhận đơn hàng trước.'
                ], 400);
            }

            $bill = Bill::where('bill_code', $billCode)->first();

            if (!$bill) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hóa đơn không tồn tại.'
                ], 404);
            }

            // KIỂM TRA LẦN CUỐI: Trước khi thanh toán, check xem có ai vừa thanh toán bàn này không
            if ($bill->order_type === 'dat-ban') {
                $tables = \App\Models\BookingTable::where('bill_id', $bill->id)->get();
                foreach ($tables as $t) {
                    $overlap = DB::table('booking_tables')
                        ->join('bills', 'booking_tables.bill_id', '=', 'bills.id')
                        ->where('bills.is_paid', true)
                        ->where('bills.status', '!=', 'cancelled')
                        ->where('bills.id', '!=', $bill->id)
                        ->where('booking_tables.table_number', $t->table_number)
                        ->where(function ($query) use ($t) {
                        $query->where('booking_tables.start_time', '<', $t->end_time)
                            ->where('booking_tables.end_time', '>', $t->start_time);
                    })
                        ->select('bills.bill_code')
                        ->first();

                    if ($overlap) {
                        // ROLLBACK: Khôi phục lại giỏ hàng cho người dùng
                        $cart = session()->get('cart', []);
                        $billDetails = BillDetail::with('dish')->where('bill_id', $bill->id)->get();

                        foreach ($billDetails as $detail) {
                            $cartKey = $detail->dish_id . '_' . $bill->order_type;
                            $cart[$cartKey] = [
                                "dish_id" => $detail->dish_id,
                                "name" => $detail->dish->dish_name,
                                "quantity" => $detail->quantity,
                                "price" => $detail->price_at_time,
                                "order_type" => $bill->order_type,
                                "note" => $detail->note,
                                "created_at" => now()->format('H:i d/m/Y')
                            ];
                        }

                        session()->put('cart', $cart);
                        session()->put('last_confirmed_' . $type, false);

                        $bill->update(['status' => 'cancelled']);

                        return response()->json([
                            'status' => 'error',
                            'message' => 'Rất tiếc, bàn ' . $t->table_number . ' vừa có người khác thanh toán trước trong khung giờ bạn chọn. Giỏ hàng của bạn đã được khôi phục!'
                        ], 422);
                    }
                }
            }

            // Cập nhật hóa đơn
            $bill->update([
                'is_paid' => true,
                'status' => 'completed',
                'payment_method' => $request->payment_method ?? 'Tiền mặt',
                'paid_at' => now(),
            ]);

            session(['paid_' . $type => true]);

            // Dọn dẹp session đặt bàn nếu là đơn tại bàn thành công
            if ($bill->order_type === 'dat-ban') {
                session()->forget([
                    'table_numbers', 'tables_detail', 'start_date', 'start_time',
                    'end_time', 'total_tables', 'types', 'table_number'
                ]);
            }

            return response()->json(['status' => 'success']);
        }
        catch (\Exception $e) {
            // if bill was already marked paid but an exception occurred later,
            // treat as success to avoid confusing the user
            if (isset($bill) && $bill->is_paid) {
                \Log::warning('processPayment encountered exception after bill update: ' . $e->getMessage());
                return response()->json(['status' => 'success']);
            }
            // Otherwise log and return error
            \Log::error('processPayment failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi xử lý thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    public function transactionHistory(Request $request)
    {
        $q = $request->input('q');
        $date = $request->input('date');
        $sort = $request->input('sort', 'desc');
        $is_paid = $request->input('is_paid');
        $search_type = $request->input('search_type', 'bill_code');

        $query = Bill::with(['details.dish', 'bookings']);

        // CHỈ LẤY ĐƠN HÀNG CỦA USER ĐANG ĐĂNG NHẬP
        $query->where('user_id', auth()->id());

        $minDate = now()->subMonths(3)->startOfDay();

        if ($search_type === 'bill_code' && $q) {
            $query->where('bill_code', 'like', "%{$q}%");
        }
        elseif ($search_type === 'payment_status' && $request->filled('is_paid')) {
            $query->where('is_paid', $is_paid == '1');
        }

        if ($date) {
            $query->whereDate('booking_date', $date);
        }
        else {
            $query->whereDate('booking_date', '>=', $minDate);
        }

        $bills = $query->orderBy('booking_date', $sort)
            ->orderBy('created_at', $sort)
            ->get();

        return view('transaction_history', compact('bills', 'q', 'date', 'sort', 'minDate', 'is_paid', 'search_type'));
    }

    public function transactionManagement(Request $request)
    {
        $q = $request->input('q');
        $date = $request->input('date');
        $sort = $request->input('sort', 'desc');
        $is_paid = $request->input('is_paid');
        $search_type = $request->input('search_type', 'bill_code');
        
        $order_type = $request->input('order_type');
        $username = $request->input('username');

        $query = Bill::with(['details.dish', 'bookings', 'user']);

        // Admin sees all
        $minDate = now()->subMonths(3)->startOfDay();

        // One main filter at a time (besides date)
        if ($search_type === 'bill_code' && $q) {
            $query->where('bill_code', 'like', "%{$q}%");
        }
        elseif ($search_type === 'payment_status' && $request->filled('is_paid')) {
            $query->where('is_paid', $is_paid == '1');
        }
        elseif ($search_type === 'order_type' && $order_type) {
            $query->where('order_type', $order_type);
        }
        elseif ($search_type === 'username' && $username) {
            $query->whereHas('user', function($u) use ($username) {
                $u->where('username', 'like', "%{$username}%");
            });
        }

        if ($date) {
            $query->whereDate('booking_date', $date);
        }
        else {
            $query->whereDate('booking_date', '>=', $minDate);
        }

        $bills = $query->orderBy('booking_date', $sort)
            ->orderBy('created_at', $sort)
            ->get();

        return view('transaction_management', compact('bills', 'q', 'date', 'sort', 'minDate', 'is_paid', 'search_type', 'order_type', 'username'));
    }

    public function exportPDF(Request $request)
    {
        $type = $request->query('type');
        $code = $request->query('code'); // Allow exporting by specific bill code

        if ($code) {
            $bill = Bill::with(['details.dish'])->where('bill_code', $code)->first();
        }
        else {
            $billCode = session()->get('last_bill_code_' . $type);
            $bill = Bill::with(['details.dish'])->where('bill_code', $billCode)->first();
        }

        if (!$bill) {
            return "Không tìm thấy hóa đơn!";
        }

        $tables = \App\Models\BookingTable::where('bill_id', $bill->id)->get();

        $pdf = Pdf::loadView('pdf.invoice', compact('bill', 'tables'));

        return $pdf->stream('hoa-don-' . $bill->bill_code . '.pdf');
    }

    public function saveAddress(Request $request)
    {
        session(['user_address' => $request->address]);
        return response()->json(['status' => 'success']);
    }

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
            'total_tables' => $request->total_tables ?? count($tables),
            'types' => $request->types // optional
        ]);
        return response()->json(['status' => 'success']);
    }

    public function checkMultiOverlap(Request $request)
    {
        try {
            $date = $request->date;
            $startTime = $request->start_time;
            $endTime = $request->end_time;
            $tables = $request->tables; // array of numbers

            if (!$tables || !is_array($tables)) {
                return response()->json(['status' => 'success']);
            }

            // Format datetime for comparison
            $startDateTime = $date . ' ' . $startTime . ':00';
            $endDateTime = $date . ' ' . $endTime . ':00';

            foreach ($tables as $num) {
                // Check for conflicts with paid bookings only
                $overlap = DB::table('booking_tables')
                    ->join('bills', 'booking_tables.bill_id', '=', 'bills.id')
                    ->where('booking_tables.table_number', (int)$num)
                    ->where('bills.is_paid', true)
                    ->where('bills.status', '!=', 'cancelled')
                    ->whereRaw("booking_tables.start_time < ? AND booking_tables.end_time > ?", [$endDateTime, $startDateTime])
                    ->select('booking_tables.*', 'bills.bill_code')
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
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi kiểm tra trùng lịch: ' . $e->getMessage()
            ], 500);
        }
    }
}