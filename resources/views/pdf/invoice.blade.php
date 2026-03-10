<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hóa Đơn {{ $bill->bill_code }}</title>
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
    <div class="invoice-box">
        <div class="header">
            <div class="restaurant-name">HGH RESTAURANT</div>
            <p>Địa chỉ: Số 52, đường số 2, Khu CBGV DHCT, Phường An Khánh, Quận Ninh Kiều, TP.Cần Thơ</p>
            <p>Điện thoại: 0907106674</p>
        </div>

        <table class="invoice-info">
            <tr>
                <td style="width: 50%;">
                    <strong>Khách hàng:</strong> {{ $bill->customer_name }}<br>
                    <strong>Loại đơn:</strong> {{ $bill->order_type == 'mang-ve' ? 'Mang về' : 'Tại bàn' }}<br>
                    @if($bill->order_type == 'mang-ve')
                        <strong>Địa chỉ giao:</strong> {{ $bill->address ?? 'N/A' }}
                    @else
                        <strong>Số bàn:</strong> 
                        Bàn {{ $tables->pluck('table_number')->sort()->values()->implode(', ') }}
                    @endif
                </td>
                <td style="text-align: right;">
                    <strong>Mã hóa đơn:</strong> {{ $bill->bill_code }}<br>
                    <strong>Ngày đặt:</strong> {{ $bill->created_at->format('d/m/Y H:i') }}<br>
                    <strong>Thanh toán:</strong> 
                    @if($bill->is_paid)
                        <span class="status-paid">Đã thanh toán ({{ $bill->payment_method }})</span>
                    @else
                        <span class="status-pending">Chưa thanh toán</span>
                    @endif
                </td>
            </tr>
        </table>

        @if($bill->order_type == 'dat-ban')
        <div class="section-title">Thông tin đặt bàn</div>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; padding: 5px 0;"><strong>Số bàn:</strong> <span class="highlight">Bàn {{ $tables->pluck('table_number')->sort()->values()->implode(', ') }}</span></td>
                <td style="border: none; padding: 5px 0; text-align: right;">
                    <strong>Ngày đến:</strong> {{ \Carbon\Carbon::parse($tables->first()->start_time)->format('d/m/Y') }} | 
                    <strong>Giờ:</strong> {{ \Carbon\Carbon::parse($tables->first()->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($tables->first()->end_time)->format('H:i') }}
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
                @foreach($bill->details as $detail)
                <tr>
                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                    <td>
                        <div class="highlight">{{ $detail->dish->dish_name }}</div>
                        @if($detail->note && $detail->note !== 'Không có')
                            <small style="color: #888;">Ghi chú: {{ $detail->note }}</small>
                        @endif
                    </td>
                    <td style="text-align: center;">{{ $detail->quantity }}</td>
                    <td style="text-align: right;">{{ number_format($detail->price_at_time, 0, ',', '.') }}đ</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($detail->price_at_time * $detail->quantity, 0, ',', '.') }}đ</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <p>Tổng tiền món ăn: {{ number_format($bill->total_amount, 0, ',', '.') }}đ</p>
            <p>Phí vận chuyển: 0đ</p>
            <p class="total-amount">TỔNG CỘNG: {{ number_format($bill->total_amount, 0, ',', '.') }} VNĐ</p>
            @if($bill->paid_at)
                <p style="font-style: italic; color: #666; font-size: 11px;">
                    Thanh toán lúc: {{ \Carbon\Carbon::parse($bill->paid_at)->format('d/m/Y H:i:s') }}
                </p>
            @endif
        </div>

        <div class="footer">
            <p>Cảm ơn quý khách đã ủng hộ HGH Restaurant!</p>
            <p>Hóa đơn này có giá trị xác nhận giao dịch tại nhà hàng.</p>
            <p>--- Chúc quý khách ngon miệng ---</p>
        </div>
    </div>
</body>
</html>
