<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Points;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VnpayController extends Controller
{
    public function createPaymentUrl(Request $request)
    {
        $request->validate([
            'bill_id' => 'required',
        ]);

        $billId = $request->input('bill_id');

        // QUAN TRỌNG: Số tiền thanh toán PHẢI lấy từ DB (bill->total_price đã
        // được server tính đúng lúc tạo bill), KHÔNG tin giá trị "amount" mà
        // client gửi lên — nếu không, người dùng có thể sửa request để thanh
        // toán với số tiền tuỳ ý trong khi đơn hàng thật có giá trị khác.
        $bill = Bill::where('bill_id', $billId)->first();

        if (!$bill) {
            return response()->json(['message' => 'Bill not found'], 404);
        }

        $amount = (int) round((float) $bill->total_price);

        if ($amount < 1000) {
            return response()->json(['message' => 'Invalid bill amount'], 422);
        }

        $vnp_TmnCode    = config('vnpay.tmn_code');
        $vnp_HashSecret = config('vnpay.hash_secret');
        $vnp_Url        = config('vnpay.url');
        $vnp_ReturnUrl  = config('vnpay.return_url');

        $vnp_TxnRef     = $billId . '_' . time();
        $vnp_Amount     = $amount * 100;
        $vnp_CreateDate = now()->format('YmdHis');
        $vnp_ExpireDate = now()->addMinutes(15)->format('YmdHis');
        $vnp_OrderInfo  = 'Thanh toan don hang ' . $billId;

        // Lấy IP khách hàng — theo đúng code mẫu chính thức của VNPAY (chỉ lấy
        // REMOTE_ADDR thẳng, không phân biệt IPv4/IPv6). Ưu tiên X-Forwarded-For
        // vì app chạy qua ngrok/proxy, REMOTE_ADDR lúc đó sẽ là IP của proxy
        // chứ không phải IP khách hàng thật.
        $rawIp = $request->header('X-Forwarded-For') ?? $request->ip();
        $ip    = trim(explode(',', $rawIp)[0]);

        $inputData = [
            "vnp_Version"    => "2.1.0",
            "vnp_TmnCode"    => $vnp_TmnCode,
            "vnp_Amount"     => $vnp_Amount,
            "vnp_Command"    => "pay",
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_CurrCode"   => "VND",
            "vnp_ExpireDate" => $vnp_ExpireDate,
            "vnp_IpAddr"     => $ip,
            "vnp_Locale"     => "vn",
            "vnp_OrderInfo"  => $vnp_OrderInfo,
            "vnp_OrderType"  => "other",
            "vnp_ReturnUrl"  => $vnp_ReturnUrl,
            "vnp_TxnRef"     => $vnp_TxnRef,
        ];

        // Thêm BankCode nếu có (tuỳ chọn)
        $bankCode = $request->input('bankCode');
        if (!empty($bankCode)) {
            $inputData['vnp_BankCode'] = $bankCode;
        }

        // ---- Build hashData & query theo đúng chuẩn code mẫu VNPAY ----
        ksort($inputData);

        $i        = 0;
        $hashData = "";
        $query    = "";

        foreach ($inputData as $key => $value) {
            if ($value !== '' && $value !== null) {
                if ($i == 1) {
                    $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashData .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }
        }
        // ----------------------------------------------------------------

        $vnpSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        $paymentUrl    = $vnp_Url . "?" . $query . 'vnp_SecureHash=' . $vnpSecureHash;

        Log::info('VNPay createPaymentUrl', [
            'input_data'  => $inputData,
            'hash_data'   => $hashData,
            'secure_hash' => $vnpSecureHash,
            'payment_url' => $paymentUrl,
        ]);

        Bill::where('bill_id', $billId)->update(['vnp_txn_ref' => $vnp_TxnRef]);

        return response()->json(['payment_url' => $paymentUrl]);
    }

    /**
     * Người dùng được VNPAY redirect về đây sau khi thanh toán.
     *
     * QUAN TRỌNG: KHÔNG cập nhật trạng thái đơn hàng ở đây.
     * Đây là request từ trình duyệt người dùng, không phải xác nhận
     * đáng tin cậy từ server VNPAY. Việc cập nhật trạng thái "paid"
     * chỉ được thực hiện trong vnpayIpn() — nơi VNPAY gọi server-to-server.
     * Ở đây chỉ dùng để hiển thị kết quả tạm thời cho người dùng.
     */
    public function vnpayReturn(Request $request)
    {
        Log::info('vnpayReturn called', ['query' => $request->query()]);

        $result = $this->verifyAndGetResult($request);

        $vnp_TxnRef = $result['data']['vnp_TxnRef'] ?? '';
        $billId     = $this->extractBillId($vnp_TxnRef);

        if (!$result['is_valid_signature']) {
            Log::warning('vnpayReturn invalid signature', ['txn_ref' => $vnp_TxnRef]);
        }

        // ✅ Lấy order_type từ bill để truyền về frontend
        $orderType = 'delivery'; // default
        if ($billId) {
            $bill = Bill::with('order')->where('bill_id', $billId)->first();
            $orderType = $bill?->order?->order_type ?? 'delivery';
        }

        $frontendUrl  = config('app.frontend_url', 'http://localhost:5173');
        $status       = $result['is_success'] ? 'success' : 'failed';
        $responseCode = $result['data']['vnp_ResponseCode'] ?? '99';

        return redirect()->away(
            "{$frontendUrl}/payment-result?bill_id={$billId}&status={$status}&code={$responseCode}&order_type={$orderType}"
        );
    }

    /**
     * VNPAY gọi server-to-server để xác nhận giao dịch.
     * Đây là NGUỒN DUY NHẤT được tin tưởng để cập nhật trạng thái thanh toán.
     */
    public function vnpayIpn(Request $request)
    {
        $result = $this->verifyAndGetResult($request);

        if (!$result['is_valid_signature']) {
            Log::warning('VNPay IPN invalid signature', ['query' => $request->query()]);
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $vnp_TxnRef = $result['data']['vnp_TxnRef'] ?? '';
        $billId     = $this->extractBillId($vnp_TxnRef);

        if (!$billId) {
            Log::warning('VNPay IPN invalid TxnRef format', ['txn_ref' => $vnp_TxnRef]);
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $bill = Bill::with('order.user')->where('bill_id', $billId)->first();

        if (!$bill || !$bill->order) {
            Log::warning('VNPay IPN bill/order not found', ['bill_id' => $billId]);
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $vnpAmount = ($result['data']['vnp_Amount'] ?? 0) / 100;

        // So sánh tiền tệ: dùng sai số cho phép thay vì so sánh float tuyệt đối
        if (abs((float) $bill->total_price - (float) $vnpAmount) > 0.01) {
            Log::warning('VNPay IPN amount mismatch', [
                'bill_id'     => $billId,
                'bill_amount' => $bill->total_price,
                'vnp_amount'  => $vnpAmount,
            ]);
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        [$record, $paidField, $statusField, $nextStatus] = $this->resolveOrderRecord($bill->order);

        if (!$record) {
            Log::warning('VNPay IPN order record not found', ['bill_id' => $billId]);
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        if ($record->{$paidField} === 'paid') {
            return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        }

        $responseCode      = $result['data']['vnp_ResponseCode']      ?? '';
        $transactionStatus = $result['data']['vnp_TransactionStatus'] ?? '';

        if ($responseCode === '00' && $transactionStatus === '00') {
            DB::beginTransaction();
            try {
                $record->{$paidField}   = 'paid';
                $record->{$statusField} = $nextStatus;
                $record->save();

                // Cập nhật payment_method trên bill thành 'vnpay' — trước đó
                // bill được tạo với payment_method = 'unpaid' (xem BillController::store).
                $bill->payment_method = 'vnpay';
                $bill->save();

                // Điểm thưởng tính SAU khi trạng thái thanh toán đã chắc chắn
                // là 'paid', và thống kê tính SAU điểm thưởng — đúng thứ tự
                // ưu tiên an toàn dữ liệu mà bạn muốn.
                $this->awardPointsAndStats($bill, $bill->order);

                DB::commit();

                Log::info('VNPay IPN success', ['bill_id' => $billId]);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('VNPay IPN update failed', [
                    'bill_id' => $billId,
                    'error'   => $e->getMessage(),
                ]);
                return response()->json(['RspCode' => '99', 'Message' => 'Unknown error']);
            }
        } else {
            Log::warning('VNPay IPN failed', [
                'bill_id'            => $billId,
                'response_code'      => $responseCode,
                'transaction_status' => $transactionStatus,
            ]);
        }

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Verify chữ ký VNPAY — build hashData theo đúng chuẩn code mẫu (urlencode thủ công).
     */
    private function verifyAndGetResult(Request $request): array
    {
        $vnp_HashSecret = trim(config('vnpay.hash_secret'));

        // Chỉ lấy các param bắt đầu bằng "vnp_" — giống code mẫu IPN VNPAY
        // Tránh trường hợp URL có thêm param lạ làm sai hash
        $inputData = [];
        foreach ($request->query() as $key => $value) {
            if (str_starts_with($key, 'vnp_')) {
                $inputData[$key] = $value;
            }
        }

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        // ---- Build hashData theo đúng chuẩn code mẫu VNPAY ----
        ksort($inputData);

        $i        = 0;
        $hashData = "";

        foreach ($inputData as $key => $value) {
            if ($value !== '' && $value !== null) {
                if ($i == 1) {
                    $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashData .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
            }
        }
        // --------------------------------------------------------

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        Log::info('VNPay Verify', [
            'hash_data'      => $hashData,
            'received_hash'  => $vnp_SecureHash,
            'generated_hash' => $secureHash,
        ]);

        $isValidSignature = hash_equals($secureHash, $vnp_SecureHash);

        // Theo code mẫu IPN VNPAY: phải check CẢ HAI ResponseCode VÀ TransactionStatus đều là '00'
        $isSuccess = $isValidSignature
            && (($inputData['vnp_ResponseCode']     ?? '') === '00')
            && (($inputData['vnp_TransactionStatus'] ?? '') === '00');

        return [
            'is_valid_signature' => $isValidSignature,
            'is_success'         => $isSuccess,
            'data'               => $inputData,
        ];
    }

    /**
     * Trích xuất bill_id từ vnp_TxnRef (format: "{billId}_{timestamp}").
     * Trả về null nếu format không hợp lệ — tránh update nhầm record
     * khi có request giả mạo hoặc dữ liệu bất thường.
     */
    private function extractBillId(string $vnpTxnRef): ?string
    {
        if ($vnpTxnRef === '') {
            return null;
        }

        $parts  = explode('_', $vnpTxnRef);
        $billId = $parts[0] ?? null;

        if (!$billId || !is_numeric($billId)) {
            return null;
        }

        return $billId;
    }

    /**
     * Ghi nhận điểm thưởng + cập nhật thống kê sau khi bill được xác nhận
     * "paid". Gọi từ vnpayIpn() — đây là luồng thanh toán chính của hệ
     * thống (qua VNPAY), nên Points/Statistics được gắn thẳng vào đây
     * thay vì chỉ tồn tại trong BillController::processPayment (route
     * không nằm trong luồng thanh toán thực tế hiện tại).
     *
     * Thứ tự: Points trước, Statistics sau — nếu Points lỗi, toàn bộ
     * transaction (bao gồm cả việc set "paid") sẽ rollback, tránh trạng
     * thái nửa-thanh-toán nửa-không.
     */
    private function awardPointsAndStats(Bill $bill, $order): void
    {
        $amount = (float) $bill->total_price;
        $user   = $order->user; // Order::user() — xem App\Models\Order

        if (!$user) {
            Log::warning('VNPay IPN: order has no user, skip points/statistics', [
                'bill_id' => $bill->bill_id,
            ]);
            return;
        }

        $pointsEarned = Points::calculatePoints($amount);

        Points::create([
            'user_id'              => $user->user_id,
            'bill_id'              => $bill->bill_id,
            'points_earned'        => $pointsEarned,
            'booking_total_price'  => $order->order_type === 'booking_table' ? $amount : 0,
            'delivery_total_price' => $order->order_type === 'delivery' ? $amount : 0,
        ]);

        $stats = $user->getOrCreateStatistics();
        $stats->incrementTotalOrders();
        $stats->addSpent($amount);
        $stats->addPoints($pointsEarned);

        if ($order->order_type === 'booking_table') {
            $stats->booking_orders += 1;
        } else {
            $stats->delivery_orders += 1;
        }

        $stats->save();
    }

    /**
     * Trả về [record, paidField, statusField, nextStatus] tuỳ delivery hay booking.
     */
    private function resolveOrderRecord($order): array
    {
        if ($order->isDelivery()) {
            return [
                $order->delivery,
                'D_payment_status',
                'delivery_status',
                'waiting_approval',
            ];
        }

        return [
            $order->booking,
            'B_payment_status',
            'booking_status',
            'completed',
        ];
    }
}