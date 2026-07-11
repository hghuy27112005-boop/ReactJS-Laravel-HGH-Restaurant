<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\UserDeletedMail;
use App\Models\Bill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class Admin_UserController extends Controller
{
    /**
     * Danh sách user (không gồm admin), phân trang 25/trang, search theo username hoặc email.
     */
    public function index(Request $request)
    {
        $query = User::where('role', '!=', 'admin');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $perPage = 25;

        // Gắn STT theo vị trí thật trong danh sách (không dựa vào user_id, vì
        // user_id có thể bị nhảy số sau khi xóa/tạo lại tài khoản nhiều lần).
        $attachStt = function ($users, $currentPage) use ($perPage) {
            $startStt = ($currentPage - 1) * $perPage + 1;
            $items = collect($users->items())->values()->map(function ($user, $index) use ($startStt) {
                $user->stt = $startStt + $index;
                return $user;
            });
            return $items;
        };

        // Khi search: FE cần biết user nằm ở trang nào để tự nhảy tới, nên
        // tính rank của user theo thứ tự user_id trước khi phân trang chuẩn.
        if ($request->filled('search')) {
            $matched = (clone $query)->orderBy('user_id')->get(['user_id']);
            $firstMatchId = $matched->first()?->user_id;

            $targetPage = 1;
            if ($firstMatchId) {
                $position = User::where('role', '!=', 'admin')
                    ->where('user_id', '<', $firstMatchId)
                    ->orderBy('user_id')
                    ->count();
                $targetPage = intdiv($position, $perPage) + 1;
            }

            $users = User::where('role', '!=', 'admin')
                ->orderBy('user_id')
                ->paginate($perPage, ['*'], 'page', $targetPage);

            return response()->json([
                'data' => $attachStt($users, $users->currentPage()),
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ],
                'matched_user_id' => $firstMatchId,
            ]);
        }

        $users = $query->orderBy('user_id')->paginate($perPage);

        return response()->json([
            'data' => $attachStt($users, $users->currentPage()),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Xóa tài khoản user (không phải admin). Gửi mail thông báo kèm PDF các
     * hóa đơn "dang dở" TRƯỚC, xong mới xóa (cascade DB tự xóa orders/
     * bookings/deliveries/bills liên quan).
     */
    public function destroy(User $user)
    {
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Không thể xóa tài khoản admin'], 403);
        }

        $pendingBills = $this->getPendingBills($user);

        if ($pendingBills->isNotEmpty()) {
            try {
                Mail::to($user->email)->send(new UserDeletedMail($user, $pendingBills));
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Gửi mail thất bại, tài khoản chưa bị xóa: ' . $e->getMessage(),
                ], 500);
            }
        }

        $user->delete();

        return response()->json([
            'message' => 'Đã xóa tài khoản người dùng thành công',
        ]);
    }

    /**
     * Lấy các bill "dang dở":
     * - Booking: booking_status <> 'cancelled' AND booking_date > now()
     * - Delivery: delivery_status = 'waiting_approval'
     * Chỉ tính đơn đã có bill (đã thanh toán).
     */
    private function getPendingBills(User $user)
    {
        $bookingBillIds = Bill::whereHas('order', function ($q) use ($user) {
            $q->where('user_id', $user->user_id)
                ->where('order_type', 'booking_table')
                ->whereHas('bookings', function ($bq) {
                    $bq->where('booking_status', '!=', 'cancelled')
                        ->whereRaw('booking_date > now()');
                });
        })->pluck('bill_id');

        $deliveryBillIds = Bill::whereHas('order', function ($q) use ($user) {
            $q->where('user_id', $user->user_id)
                ->where('order_type', 'delivery')
                ->whereHas('delivery', function ($dq) {
                    $dq->where('delivery_status', 'waiting_approval');
                });
        })->pluck('bill_id');

        $billIds = $bookingBillIds->merge($deliveryBillIds)->unique();

        return Bill::whereIn('bill_id', $billIds)
            ->with(['order.items.dish', 'order.bookings', 'order.delivery'])
            ->get();
    }
}