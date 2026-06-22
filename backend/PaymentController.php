<?php
namespace App\Controllers\Client;

use App\Core\Controller;
use App\Services\VNPayService;
use App\Config\Database;

/**
 * PaymentController - Xử lý các phương thức thanh toán
 */
class PaymentController extends Controller {
    
    /**
     * Xử lý thanh toán VNPAY - Chuyển hướng đến VNPAY
     */
    public function vnpay_redirect() {
        try {
            // Kiểm tra user
            if (!isset($_SESSION['user'])) {
                throw new \Exception('Vui lòng đăng nhập để thanh toán');
            }
            
            // Kiểm tra order tồn tại
            $orderId = $_GET['order_id'] ?? null;
            if (!$orderId) {
                throw new \Exception('Không tìm thấy đơn hàng');
            }
            
            // Lấy info order
            $orderModel = $this->model('Order');
            $order = $orderModel->getOrderById($orderId);
            
            if (!$order) {
                throw new \Exception('Đơn hàng không tồn tại');
            }
            
            // Kiểm tra quyền (user phải là chủ đơn hàng)
            if ($order['user_id'] != $_SESSION['user']['id']) {
                throw new \Exception('Bạn không có quyền thanh toán đơn hàng này');
            }
            
            // Kiểm tra trạng thái order (phải là pending hoặc chưa thanh toán)
            if ($order['payment_status'] === 'paid') {
                throw new \Exception('Đơn hàng đã được thanh toán rồi');
            }
            
            // Khởi tạo VNPAY Service
            $vnpay = new VNPayService();
            
            // Kiểm tra config
            if (!$vnpay->isConfigured()) {
                throw new \Exception('Hệ thống thanh toán VNPAY chưa được cấu hình. Vui lòng liên hệ admin.');
            }
            
            // Tạo URL thanh toán
            $bankCode = $_GET['bank_code'] ?? ''; // Tùy chọn chọn ngân hàng
            $paymentUrl = $vnpay->buildPaymentUrl(
                $orderId,
                (float)$order['total_amount'],
                $bankCode
            );
            
            // Chuyển hướng đến VNPAY
            header("Location: " . $paymentUrl);
            exit;
            
        } catch (\Exception $e) {
            error_log("[PaymentController::vnpay_redirect] Error: " . $e->getMessage());
            $_SESSION['flash_error'] = "Lỗi thanh toán: " . $e->getMessage();
            header("Location: /checkout/success");
            exit;
        }
    }
    
    /**
     * Callback từ VNPAY (Khi user quay lại từ VNPAY)
     * Xử lý kết quả thanh toán
     */
    public function vnpay_return() {
        try {
            error_log("[PaymentController::vnpay_return] Called with GET data: " . json_encode($_GET));
            
            // Khởi tạo VNPAY Service
            $vnpay = new VNPayService();
            
            // Kiểm tra checksum
            $verifyResult = $vnpay->verifyPaymentReturn($_GET);
            
            error_log("[PaymentController::vnpay_return] Verification result: " . json_encode($verifyResult));
            
            // Lấy order ID
            $orderId = $verifyResult['orderId'];
            if (!$orderId) {
                throw new \Exception('Không xác định được đơn hàng');
            }
            
            // Lấy order từ DB
            $orderModel = $this->model('Order');
            $order = $orderModel->getOrderById($orderId);
            
            if (!$order) {
                throw new \Exception('Đơn hàng không tồn tại');
            }
            
            // Kiểm tra user
            if (!isset($_SESSION['user']) || $order['user_id'] != $_SESSION['user']['id']) {
                throw new \Exception('Bạn không có quyền truy cập');
            }
            
            // Nếu thanh toán thành công
            if ($verifyResult['success']) {
                // Cập nhật trạng thái đơn hàng sang "paid"
                $orderModel->updatePaymentStatus($orderId, 'paid');
                $orderModel->updateOrderStatus($orderId, 'processing'); // Xác nhận đơn
                
                // Ghi log transaction
                $this->logTransaction($orderId, $verifyResult);
                
                $_SESSION['flash_message'] = "Thanh toán thành công!";
                $paymentSuccess = true;
            } else {
                // Cập nhật trạng thái sang "failed"
                $orderModel->updatePaymentStatus($orderId, 'failed');
                
                // Ghi log transaction failed
                $this->logTransaction($orderId, $verifyResult);
                
                $_SESSION['flash_error'] = $verifyResult['message'];
                $paymentSuccess = false;
            }
            
            // Lưu lên session để view hiển thị
            $_SESSION['vnpay_result'] = $verifyResult;
            
            // Hiển thị kết quả thanh toán
            $this->view('client/payment/vnpay-result', [
                'success' => $paymentSuccess,
                'order' => $order,
                'result' => $verifyResult
            ]);
            
        } catch (\Exception $e) {
            error_log("[PaymentController::vnpay_return] Exception: " . $e->getMessage());
            $_SESSION['flash_error'] = "Lỗi xử lý thanh toán: " . $e->getMessage();
            header("Location: /checkout/success");
            exit;
        }
    }
    
    /**
     * IPN - Instant Payment Notification (Webhook từ VNPAY)
     * Xử lý confirm thanh toán từ phía server VNPAY
     */
    public function vnpay_ipn() {
        // Log tất cả IPN requests
        error_log("[PaymentController::vnpay_ipn] IPN received at " . date('Y-m-d H:i:s'));
        error_log("[PaymentController::vnpay_ipn] Data: " . json_encode($_GET));
        
        $response = ['RspCode' => '99', 'Message' => 'Fail'];
        
        try {
            // Khởi tạo VNPAY Service
            $vnpay = new VNPayService();
            
            // Kiểm tra checksum
            if (!$vnpay->verifyIPN($_GET)) {
                error_log("[PaymentController::vnpay_ipn] Checksum verification failed");
                echo json_encode($response);
                exit;
            }
            
            error_log("[PaymentController::vnpay_ipn] Checksum verification passed");
            
            $responseCode = $_GET['vnp_ResponseCode'] ?? '';
            $txnRef = $_GET['vnp_TxnRef'] ?? '';
            
            // Parse orderid
            preg_match('/^(\d+)/', $txnRef, $matches);
            $orderId = $matches[1] ?? null;
            
            if (!$orderId) {
                error_log("[PaymentController::vnpay_ipn] Failed to parse orderid from txnRef: " . $txnRef);
                echo json_encode($response);
                exit;
            }
            
            error_log("[PaymentController::vnpay_ipn] OrderID: {$orderId}, ResponseCode: {$responseCode}");
            
            // Lấy order
            $orderModel = $this->model('Order');
            $order = $orderModel->getOrderById($orderId);
            
            if (!$order) {
                error_log("[PaymentController::vnpay_ipn] Order not found: {$orderId}");
                echo json_encode($response);
                exit;
            }
            
            // Xử lý theo response code
            if ($responseCode == '00') {
                // Thanh toán thành công
                $orderModel->updatePaymentStatus($orderId, 'paid');
                $orderModel->updateOrderStatus($orderId, 'processing');
                
                error_log("[PaymentController::vnpay_ipn] Payment success for order: {$orderId}");
                
                $response = ['RspCode' => '00', 'Message' => 'Confirm Success'];
            } else {
                // Thanh toán thất bại
                $orderModel->updatePaymentStatus($orderId, 'failed');
                
                error_log("[PaymentController::vnpay_ipn] Payment failed for order: {$orderId}");
                
                $response = ['RspCode' => '00', 'Message' => 'Confirm Fail'];
            }
            
        } catch (\Exception $e) {
            error_log("[PaymentController::vnpay_ipn] Exception: " . $e->getMessage());
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }
    
    /**
     * Ghi log transaction
     */
    private function logTransaction($orderId, $result) {
        $conn = Database::getConnection();
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO payment_transactions 
                (order_id, payment_method, status, transaction_code, amount, response_code, response_message, created_at)
                VALUES (:order_id, :payment_method, :status, :transaction_code, :amount, :response_code, :response_message, NOW())
            ");
            
            $stmt->execute([
                ':order_id' => $orderId,
                ':payment_method' => 'vnpay',
                ':status' => $result['success'] ? 'success' : 'failed',
                ':transaction_code' => $result['transactionCode'] ?? '',
                ':amount' => $result['amount'] ?? 0,
                ':response_code' => $result['responseCode'] ?? '',
                ':response_message' => $result['message'] ?? ''
            ]);
        } catch (\Exception $e) {
            error_log("Error logging transaction: " . $e->getMessage());
            // Không dừng quy trình nếu lỗi logging
        }
    }
}
