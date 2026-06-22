<?php
// test_vnpay.php — chạy độc lập: php test_vnpay.php
$vnp_TmnCode = "HLTDI0F2";
$vnp_HashSecret = "EAGULW3NF40N8FAA8R0LVJ1TX4B1CD5U"; // copy trực tiếp từ email, gõ tay không paste

$inputData = [
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => $vnp_TmnCode,
    "vnp_Amount" => 1000000,
    "vnp_Command" => "pay",
    "vnp_CreateDate" => date('YmdHis'),
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => "127.0.0.1",
    "vnp_Locale" => "vn",
    "vnp_OrderInfo" => "Thanh toan don hang test",
    "vnp_OrderType" => "other",
    "vnp_ReturnUrl" => "https://localhost/return",
    "vnp_TxnRef" => "TEST" . time(),
];

ksort($inputData);
$hashData = "";
$query = "";
$i = 0;
foreach ($inputData as $key => $value) {
    if ($i == 1) $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    else { $hashData .= urlencode($key) . "=" . urlencode($value); $i = 1; }
    $query .= urlencode($key) . "=" . urlencode($value) . '&';
}

$vnpSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
$url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?" . $query . "vnp_SecureHash=" . $vnpSecureHash;

echo "Hash secret length: " . strlen($vnp_HashSecret) . "\n"; // PHẢI ra đúng 32
echo $url . "\n";