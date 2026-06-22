<?php
namespace App\Services;

/**
 * VNPAY Payment Service
 * Xử lý thanh toán VNPAY Sandbox
 */
class VNPayService {
    
    private $tmnCode;
    private $hashSecret;
    private $apiUrl;
    private $returnUrl;
    private $ipnUrl;
    private $apiVersion;
    
    public function __construct() {
        // Lấy config từ .env
        $this->tmnCode = getenv('VNPAY_TMN_CODE') ?: $_ENV['VNPAY_TMN_CODE'] ?? '';
        $this->hashSecret = getenv('VNPAY_HASH_SECRET') ?: $_ENV['VNPAY_HASH_SECRET'] ?? '';
        $this->apiUrl = getenv('VNPAY_API_URL') ?: $_ENV['VNPAY_API_URL'] ?? 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
        $this->returnUrl = getenv('VNPAY_RETURN_URL') ?: $_ENV['VNPAY_RETURN_URL'] ?? '';
        $this->ipnUrl = getenv('VNPAY_IPN_URL') ?: $_ENV['VNPAY_IPN_URL'] ?? '';
        $this->apiVersion = getenv('VNPAY_API_VERSION') ?: $_ENV['VNPAY_API_VERSION'] ?? '2.1.0';
        
        // Auto-detect domain từ HTTP request (hỗ trợ localhost, ngrok, localtunnel, v.v.)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host;
        
        // Cập nhật callback URLs với domain hiện tại
        $this->returnUrl = str_replace('http://localhost', $baseUrl, $this->returnUrl);
        $this->ipnUrl = str_replace('http://localhost', $baseUrl, $this->ipnUrl);
    }
    
    /**
     * Tạo URL thanh toán VNPAY
     * @param int $orderId - ID đơn hàng
     * @param float $amount - Số tiền (VND)
     * @param string $bankCode - Mã ngân hàng (optional)
     * @return string - URL thanh toán
     */
    public function buildPaymentUrl($orderId, $amount, $bankCode = '') {
        // Kiểm tra config
        if (empty($this->tmnCode) || empty($this->hashSecret)) {
            throw new \Exception('VNPAY config không hoàn chỉnh. Vui lòng cấu hình TMN_CODE và HASH_SECRET trong .env');
        }
        
        // Chuẩn bị dữ liệu
        $inputData = [
            "vnp_Version" => $this->apiVersion,
            "vnp_TmnCode" => $this->tmnCode,
            "vnp_Amount" => (int)($amount * 100), // VNPAY tính bằng đơn vị nhỏ nhất (x100)
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $this->getClientIp(),
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => "Thanh toán đơn hàng #{$orderId}",
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $this->returnUrl,
            "vnp_TxnRef" => $orderId . '-' . time(), // Mã tham chiếu giao dịch (unique, format: orderId-timestamp)
        ];
        
        // Nếu có mã ngân hàng, thêm vào
        if (!empty($bankCode)) {
            $inputData["vnp_BankCode"] = $bankCode;
        }
        
        // Sắp xếp theo thứ tự alphabet
        ksort($inputData);
        
        // Tạo query string
        $query = http_build_query($inputData);
        
        // Tạo checksum
        $hashData = hash_hmac('sha512', $query, $this->hashSecret);
        
        // Tạo URL thanh toán
        $paymentUrl = $this->apiUrl . "?" . $query . "&vnp_SecureHash=" . $hashData;
        
        return $paymentUrl;
    }
    
    /**
     * Kiểm tra và xử lý kết quả callback từ VNPAY
     * @param array $responseData - Dữ liệu response từ VNPAY
     * @return array - ['success' => bool, 'message' => string, 'transactionCode' => string, 'amount' => int]
     */
    public function verifyPaymentReturn($responseData) {
        $result = [
            'success' => false,
            'message' => 'Không xác định',
            'transactionCode' => null,
            'amount' => null,
            'orderId' => null,
            'responseCode' => null
        ];
        
        // Lấy checksum từ response
        $vnpSecureHash = $responseData['vnp_SecureHash'] ?? '';
        unset($responseData['vnp_SecureHash']);
        unset($responseData['vnp_SecureHashType']);
        
        // Sắp xếp dữ liệu
        ksort($responseData);
        
        // Tạo query string để kiểm tra
        $query = http_build_query($responseData);
        
        // Tính toán hash để so sánh
        $hashData = hash_hmac('sha512', $query, $this->hashSecret);
        
        // So sánh hash
        if ($hashData !== $vnpSecureHash) {
            $result['message'] = 'Checksum không hợp lệ!';
            $result['responseCode'] = '99';
            return $result;
        }
        
        // Nếu hash đúng, kiểm tra response code
        $responseCode = $responseData['vnp_ResponseCode'] ?? '';
        $result['responseCode'] = $responseCode;
        
        // Parse orderid từ vnp_TxnRef (format: orderId-timestamp)
        $txnRef = $responseData['vnp_TxnRef'] ?? '';
        if ($txnRef) {
            // Lấy phần orderId (các chữ số đầu, trước dấu -)
            preg_match('/^(\d+)-/', $txnRef, $matches);
            if ($matches) {
                $result['orderId'] = (int)$matches[1];
            }
        }
        
        // Lấy transaction code (Mã giao dịch tại VNPAY)
        $result['transactionCode'] = $responseData['vnp_TransactionNo'] ?? '';
        
        // Lấy số tiền gốc
        $result['amount'] = isset($responseData['vnp_Amount']) ? (int)$responseData['vnp_Amount'] / 100 : null;
        
        // Kiểm tra code
        if ($responseCode == '00') {
            $result['success'] = true;
            $result['message'] = 'Thanh toán thành công!';
        } elseif ($responseCode == '24') {
            $result['message'] = 'Giao dịch bị hủy bởi người dùng';
        } elseif ($responseCode == '10') {
            $result['message'] = 'Không đủ tiền trong tài khoản';
        } elseif ($responseCode == '99') {
            $result['message'] = 'Lỗi hệ thống VNPAY';
        } else {
            $result['message'] = 'Lỗi thanh toán: Code ' . $responseCode;
        }
        
        return $result;
    }
    
    /**
     * Xác minh IPN (Instant Payment Notification)
     * @param array $data - Dữ liệu từ VNPAY gửi đến
     * @return bool
     */
    public function verifyIPN($data) {
        $vnpSecureHash = $data['vnp_SecureHash'] ?? '';
        unset($data['vnp_SecureHash']);
        unset($data['vnp_SecureHashType']);
        
        ksort($data);
        $query = http_build_query($data);
        $hashData = hash_hmac('sha512', $query, $this->hashSecret);
        
        return $hashData === $vnpSecureHash;
    }
    
    /**
     * Lấy địa chỉ IP của client
     */
    private function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Lấy IP đầu tiên nếu có multiple IPs
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }
    
    /**
     * Định dạng số tiền cho VNPAY
     */
    public static function formatAmount($amount) {
        return (int)($amount * 100);
    }
    
    /**
     * Lấy thông tin TMN Code (để debug)
     */
    public function getTmnCode() {
        return $this->tmnCode;
    }
    
    /**
     * Kiểm tra config
     */
    public function isConfigured() {
        return !empty($this->tmnCode) && !empty($this->hashSecret);
    }
}
