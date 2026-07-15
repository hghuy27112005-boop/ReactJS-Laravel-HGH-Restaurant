<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hóa Đơn {{ $bill->bill_id }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 13px; color: #333; line-height: 1.5; }
        .invoice-box { max-width: 800px; margin: auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #C0392B; padding-bottom: 10px; }
        .restaurant-name { font-size: 28px; font-weight: bold; color: #C0392B; margin-bottom: 5px; }
        .invoice-info { margin-bottom: 20px; width: 100%; }
        .invoice-info td { border: none; padding: 5px 0; vertical-align: top; }
        .section-title { font-weight: bold; color: #C0392B; margin-top: 15px; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }

        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th { background: #f8f9fa; border: 1px solid #eee; padding: 12px 10px; text-align: left; text-transform: uppercase; font-size: 11px; }
        table.items td { border: 1px solid #eee; padding: 12px 10px; }

        .total-section { margin-top: 30px; text-align: right; }
        .total-amount { font-size: 20px; font-weight: bold; color: #C0392B; }

        .footer { text-align: center; margin-top: 50px; font-size: 11px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
        .highlight { font-weight: bold; color: #333; }
        .status-paid { color: #27AE60; font-weight: bold; text-transform: uppercase; }
        .status-pending { color: #E67E22; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>
    @php
        $order = $bill->order;
        $isBooking = $order->order_type === 'booking_table';
        $isPaid = $bill->payment_method !== 'unpaid';

        $customerName = 'Khách hàng';
        try {
            $customerName = $order->user->name ?? $order->user->username ?? $customerName;
        } catch (\Throwable $e) {
            // không có relation user hoặc field tương ứng -> giữ giá trị mặc định
        }

        $bookings = $order->bookings ?? collect();
        $firstBooking = $bookings->first();
    @endphp

    <div class="invoice-box">
        <div class="header">
            <div class="restaurant-name">HGH RESTAURANT</div>
            <p>Địa chỉ: Số 52, đường số 2, Khu CBGV ĐHCT, Phường An Khánh, Quận Ninh Kiều, TP. Cần Thơ</p>
            <p>Điện thoại: 0907106674</p>
        </div>

        <table class="invoice-info">
            <tr>
                <td style="width: 50%;">
                    <strong>Khách hàng:</strong> {{ $customerName }}<br>
                    <strong>Loại đơn:</strong> {{ $isBooking ? 'Đặt bàn' : 'Giao hàng' }}<br>
                    @if(!$isBooking)
                        <strong>Địa chỉ giao:</strong> {{ $order->delivery->address ?? 'N/A' }}
                    @else
                        <strong>Số bàn:</strong> Bàn {{ $bookings->pluck('table_number')->sort()->values()->implode(', ') }}
                    @endif
                </td>
                <td style="text-align: right;">
                    <strong>Mã hóa đơn:</strong> {{ $bill->bill_id }}<br>
                    <strong>Ngày đặt:</strong> {{ \Carbon\Carbon::parse($bill->created_at)->format('d/m/Y H:i') }}<br>
                    <strong>Thanh toán:</strong>
                    @if($isPaid)
                        <span class="status-paid">Đã thanh toán ({{ $bill->payment_method }})</span>
                    @else
                        <span class="status-pending">Chưa thanh toán</span>
                    @endif
                </td>
            </tr>
        </table>

        @if($isBooking && $firstBooking)
        <div class="section-title">Thông tin đặt bàn</div>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; padding: 5px 0;">
                    <strong>Số bàn:</strong> <span class="highlight">Bàn {{ $bookings->pluck('table_number')->sort()->values()->implode(', ') }}</span>
                </td>
                <td style="border: none; padding: 5px 0; text-align: right;">
                    <strong>Ngày đến:</strong> {{ \Carbon\Carbon::parse($firstBooking->booking_date)->format('d/m/Y') }} |
                    <strong>Giờ:</strong> {{ \Carbon\Carbon::parse($firstBooking->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($firstBooking->end_time)->format('H:i') }}
                </td>
            </tr>
        </table>
        @endif

        <div class="section-title">Chi tiết đơn hàng</div>
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 5%;">STT</th>
                    <th>Tên Món Ăn</th>
                    <th style="width: 15%; text-align: center;">Số Lượng</th>
                    <th style="width: 20%; text-align: right;">Đơn Giá</th>
                    <th style="width: 20%; text-align: right;">Thành Tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                    <td><div class="highlight">{{ $item->dish->dish_name ?? 'N/A' }}</div></td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">{{ number_format($item->unit_price, 0, ',', '.') }}đ</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}đ</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            @php
                $subtotal = $order->subtotal_price ?? $bill->total_price;
                $total = $bill->total_price;
                $discount = $subtotal - $total;

                $user = $order->user ?? null;
                // Điểm tích lũy (basePoints) chỉ phát sinh ở luồng VNPay, và luôn tính
                // trên giá gốc (subtotal) — khớp đúng quy tắc "điểm luôn tính theo giá
                // gốc trước giảm" và khớp logic awardPointsAndStats() bên backend.
                // Luồng Points không phát sinh basePoints (giữ 0 như cũ).
                $basePoints = $bill->payment_method === 'vnpay' ? floor($subtotal / 1000) : 0;
                $bonusPoints = 0;
                
                if ($user && $subtotal >= 100000 && $user->role !== 'admin' && $user->membership !== 'administrator') {
                    $bonusMap = [
                        'bronze' => 10,
                        'silver' => 20,
                        'gold' => 30,
                        'platinum' => 40,
                        'diamond' => 50,
                    ];
                    $bonusPoints = $bonusMap[$user->membership] ?? 0;
                }
                $totalPoints = $basePoints + $bonusPoints;
            @endphp
            
            @if($bill->sale_off_percentage !== null)
                {{-- Hóa đơn có áp dụng giảm giá sự kiện --}}
                <p style="font-size: 16px; margin-bottom: 5px;">Số tiền phải trả: {{ number_format($subtotal, 0, ',', '.') }} VNĐ</p>
                <p style="font-size: 14px; color: #E67E22; margin-bottom: 5px;">Giảm giá sự kiện: {{ number_format($bill->sale_off_percentage, 0, ',', '.') }}%</p>
                @if($bill->payment_method === 'Points')
                    <p style="font-size: 14px; color: #27AE60; margin-bottom: 5px;">Đã thanh toán bằng điểm: -{{ number_format(floor($bill->sale_off_total_price / 100), 0, ',', '.') }} điểm</p>
                    <p class="total-amount">Số tiền đã trả: {{ number_format($total, 0, ',', '.') }} VNĐ</p>
                @else
                    <p class="total-amount">Số tiền đã trả: {{ number_format($total, 0, ',', '.') }} VNĐ</p>
                @endif
            @elseif($bill->payment_method === 'Points')
                <p style="font-size: 16px; margin-bottom: 5px;">Số tiền phải trả: {{ number_format($subtotal, 0, ',', '.') }} VNĐ</p>
                <p style="font-size: 14px; color: #27AE60; margin-bottom: 5px;">Đã thanh toán bằng điểm: -{{ number_format(floor($subtotal / 100), 0, ',', '.') }} điểm</p>
            @elseif($discount > 0 && $bill->payment_method === 'vnpay')
                <p style="font-size: 16px; margin-bottom: 5px;">Số tiền phải trả: {{ number_format($subtotal, 0, ',', '.') }} VNĐ</p>
                <p style="font-size: 14px; color: #E67E22; margin-bottom: 5px;">Hóa đơn đã áp mã giảm giá VNPay. Giảm: {{ number_format($discount, 0, ',', '.') }} VNĐ</p>
                <p class="total-amount">Còn: {{ number_format($total, 0, ',', '.') }} VNĐ</p>
            @else
                <p style="font-size: 16px; margin-bottom: 5px;">Số tiền phải trả: {{ number_format($subtotal, 0, ',', '.') }} VNĐ</p>
                @if($discount > 0)
                <p style="font-size: 14px; color: #E67E22; margin-bottom: 5px;">Giảm giá: -{{ number_format($discount, 0, ',', '.') }} VNĐ</p>
                @endif
                <p class="total-amount">Số tiền đã trả: {{ number_format($total, 0, ',', '.') }} VNĐ</p>
            @endif
        </div>
        
        <div style="margin-top: 20px; text-align: right; font-size: 13px; font-weight: bold; color: #333;">
            <p>Số điểm quý khách tích lũy được từ đơn hàng: {{ $basePoints }} + {{ $bonusPoints }} = {{ $totalPoints }}</p>
        </div>

        <div class="footer">
            <p>Cảm ơn quý khách đã ủng hộ HGH Restaurant!</p>
            <p>Hóa đơn này có giá trị xác nhận giao dịch tại nhà hàng.</p>
            <p>--- Chúc quý khách ngon miệng ---</p>
        </div>
    </div>
</body>
</html>