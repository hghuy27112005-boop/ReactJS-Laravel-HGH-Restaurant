<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Thông báo xóa tài khoản</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 650px; margin: 20px auto; background: #fff; border-radius: 6px; overflow: hidden; }
        .header { background: #C0392B; color: #fff; text-align: center; padding: 20px; }
        .header h1 { margin: 0; font-size: 22px; }
        .body { padding: 25px; }
        .notice-title { background: #C0392B; color: #fff; padding: 10px 15px; font-weight: bold; border-radius: 4px; margin-bottom: 20px; }
        .bill-list { margin: 10px 0 20px 0; padding-left: 20px; }
        .bill-list li { margin-bottom: 4px; font-weight: bold; }
        .section-refunded { color: #27AE60; }
        .section-points { color: #E67E22; }
        .footer { text-align: center; padding: 15px; font-size: 11px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>HGH RESTAURANT</h1>
        </div>
        <div class="body">
            <p>Kính gửi quý khách <strong>{{ $targetUser->username }}</strong>,</p>

            <div class="notice-title">Thông báo xóa tài khoản người dùng vì hành vi vi phạm</div>

            <p>Nhà hàng HGH xin thông báo tài khoản của quý khách đã bị xóa khỏi hệ thống. Dưới đây là thông tin xử lý đối với các đơn hàng chưa hoàn tất tại thời điểm xóa tài khoản:</p>

            @if($refundedBills->isNotEmpty())
                <p class="section-refunded">Hóa đơn {{ $refundedBills->pluck('bill_id')->implode(', ') }} thanh toán bằng VNPay đã được hoàn tiền đầy đủ.</p>
            @endif

            @if($pointsBills->isNotEmpty())
                <p class="section-points">Hóa đơn {{ $pointsBills->pluck('bill_id')->implode(', ') }} thanh toán bằng điểm nên sẽ không hoàn tiền hóa đơn.</p>
            @endif

            <p>Quý khách vui lòng xem chi tiết từng hóa đơn trong các file PDF đính kèm email này.</p>

            <p>Mọi thắc mắc xin liên hệ nhà hàng qua số điện thoại 0907106674 để được hỗ trợ thêm.</p>

            <p>Trân trọng,<br>Nhà hàng HGH.</p>
        </div>
        <div class="footer">
            <p>Đây là email tự động, vui lòng không phản hồi trực tiếp email này.</p>
        </div>
    </div>
</body>
</html>