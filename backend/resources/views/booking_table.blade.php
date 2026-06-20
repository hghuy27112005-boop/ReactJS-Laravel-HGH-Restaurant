@extends('layout')

@section('content')
@php
    $items = session()->get('cart', []);
    $dineInItems = array_filter($items, function ($item) {
        return ($item['order_type'] ?? '') === 'dat-ban';
    });
    if (!function_exists('sumTotal')) {
        function sumTotal($arr) {
            return array_reduce($arr, function ($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0);
        }
    }
    $totalTables = session('total_tables') ?? 1;
    $types = session('types') ?? null;
@endphp

<style>
    .cart-container { max-width: 1250px; margin: 20px auto; padding: 0 20px; font-family: 'Segoe UI', sans-serif; }
    .back-nav { margin-bottom: 15px; padding-top: 10px; }
    .btn-back { display: inline-flex; align-items: center; gap: 8px; color: #666; text-decoration: none; font-weight: 600; transition: 0.3s; font-size: 14px; }
    .btn-back:hover { color: #C0392B; transform: translateX(-5px); }
    .cart-title { font-size: 26px; color: #333; margin-bottom: 25px; font-weight: bold; border-left: 6px solid #C0392B; padding-left: 15px; line-height: 1.2; }
    .cart-section { background: #fff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); padding: 25px; margin-bottom: 40px; border-top: 5px solid #C0392B; }
    .section-header { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { text-align: left; padding: 15px; border-bottom: 2px solid #eee; color: #777; font-size: 13px; text-transform: uppercase; }
    td { padding: 15px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; font-size: 15px; }
    .dish-info .name { font-weight: bold; color: #C0392B; display: block; font-size: 16px; }
    .dish-info .note { font-size: 12px; color: #999; font-style: italic; margin-top: 4px; display: block; }
    .cart-footer { display: flex; justify-content: flex-end; align-items: flex-end; gap: 15px; margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee; flex-wrap: wrap; }
    .info-group { display: flex; gap: 10px; align-items: flex-end; }
    .info-item { min-width: 300px; }
    .total-box { background: #FDF2F2; color: #333; padding: 12px 25px; border-radius: 8px; font-size: 18px; font-weight: bold; border: 1px solid #FADBD8; text-align: center; }
    .total-box span { color: #C0392B; }
    .btn-action { padding: 12px 20px; border-radius: 8px; border: none; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 14px; display: flex; align-items: center; gap: 8px; text-decoration: none; color: white; justify-content: center; }
    .btn-confirm { background: #C0392B; min-width: 150px; }
    .btn-confirm:disabled, .btn-action:disabled { background: #ccc; cursor: not-allowed; }
    .btn-edit { background: #C0392B; height: 48px; }
    .btn-pdf { background: #27AE60; }
    .info-display-box { padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; width: 100%; font-size: 14px; cursor: pointer; background: #fff; transition: 0.3s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #555; box-sizing: border-box; height: 48px; display: flex; align-items: center; }
    .info-display-box:hover { border-color: #C0392B; background: #fff5f5; }
    .info-label { font-size: 12px; color: #C0392B; font-weight: bold; margin-bottom: 5px; display: block; }
    .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
    .modal-content { background-color: white; margin: 5% auto; padding: 25px; border-radius: 15px; width: 500px; position: relative; border-top: 5px solid #C0392B; }
    .modal-input { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ddd; margin-top: 8px; box-sizing: border-box; }
    .payment-option { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; cursor: pointer; padding: 15px; border: 2px solid #eee; border-radius: 8px; transition: 0.3s; }
    .payment-option:hover { border-color: #C0392B; background: #fff5f5; }
    .payment-option input[type="radio"] { width: 20px; height: 20px; cursor: pointer; }
    .payment-option span { font-size: 16px; font-weight: 500; color: #333; }
    .payment-option img { height: 40px; min-width: 70px; object-fit: contain; }
    .modal-success-content { background: #C0392B; color: white; text-align: center; padding: 40px; border-radius: 20px; width: 400px; margin: 10% auto; position: relative; box-shadow: 0 15px 30px rgba(192,57,43,0.4); }
    .success-icon { font-size: 60px; margin-bottom: 20px; display: block; }
    .success-title { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
    .success-msg { font-size: 16px; opacity: 0.9; margin-bottom: 25px; }
    .btn-success-close { background: white; color: #C0392B; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: bold; display: inline-block; transition: 0.3s; border: none; cursor: pointer; }
    .btn-success-close:hover { transform: scale(1.05); }
    .page-switch { display: flex; justify-content: flex-end; margin-bottom: 10px; }
    .page-switch a { color: #C0392B; font-weight: 600; text-decoration: none; font-size: 14px; }
    .page-switch a:hover { text-decoration: underline; }
    /* Wizard Styles */
    .wizard-step { display: none; }
    .wizard-step.active { display: block; animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .step-indicator { display: flex; justify-content: space-between; margin-bottom: 30px; position: relative; padding: 0 10px; }
    .step-indicator::before { content: ""; position: absolute; top: 15px; left: 0; right: 0; height: 2px; background: #eee; z-index: 1; }
    .step-dot { width: 30px; height: 30px; background: #fff; border: 2px solid #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #999; z-index: 2; font-size: 12px; transition: 0.3s; }
    .step-dot.active { border-color: #C0392B; color: #C0392B; box-shadow: 0 0 10px rgba(192,57,43,0.3); }
    .step-dot.completed { background: #C0392B; border-color: #C0392B; color: #fff; }
    .type-card { border: 2px solid #eee; padding: 15px; border-radius: 10px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; }
    .type-card:hover { border-color: #C0392B; background: #fff5f5; }
    .num-selector { display: flex; align-items: center; gap: 15px; }
    .num-btn { width: 30px; height: 30px; border-radius: 50%; border: 1px solid #C0392B; background: #fff; color: #C0392B; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
    .num-btn:hover { background: #C0392B; color: #fff; }
    .num-val { font-weight: bold; width: 20px; text-align: center; }
    .table-list-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-top: 10px; }
    .table-opt { border: 1px solid #ddd; padding: 8px; border-radius: 6px; text-align: center; cursor: pointer; font-size: 12px; transition: 0.2s; }
    .table-opt:hover { border-color: #C0392B; color: #C0392B; }
    .table-opt.selected { background: #C0392B; color: #fff; border-color: #C0392B; }
    .table-opt.occupied { background: #eee; color: #bbb; cursor: not-allowed; border-color: #ddd; }
</style>

<div class="cart-container">
    <div class="back-nav">
        <a href="{{ url('/menu') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Quay về Menu</a>
    </div>
    <div class="page-switch">
        <a href="{{ url('/delivery') }}"><i class="fas fa-shopping-bag"></i> Chuyển sang Đặt Hàng</a>
    </div>

    <h1 class="cart-title"><i class="fas fa-utensils"></i> Đặt Bàn (Ăn Tại Quán)</h1>

    <div class="cart-section" style="border-top-color: #C0392B;">
        <div class="section-header" style="color: #C0392B;">
            <span><i class="fas fa-utensils"></i> Đơn Tại Bàn (Dine-in)</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>STT</th><th>Tên Món</th><th>Số Lượng</th><th>Đơn Giá</th><th>Thành Tiền</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dineInItems as $id => $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="dish-info">
                            <span class="name">{{ $item['name'] }}</span>
                            <span class="note">Ghi chú: {{ $item['note'] ?? 'Không có' }}</span>
                        </td>
                        <td>{{ $item['quantity'] }} phần</td>
                        <td>{{ number_format($item['price'], 0, ',', '.') }}đ</td>
                        <td style="font-weight: bold;">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:20px; color:#999;">Trống - Hãy về Menu để thêm món</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="cart-footer">
            @if(!session('last_confirmed_dat-ban'))
                <div style="flex: 1;"></div>
                @if(count($dineInItems) > 0)
                    <div class="info-group">
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-chair"></i> Thông tin bàn:</span>
                            <div class="info-display-box" onclick="openModal('bookingModal')">
                                {{ session('table_numbers') ? 'Bàn ' . implode(', ', session('table_numbers')) . ' | ' . session('start_time') . ' - ' . session('end_time') : 'Nhấn để đặt...' }}
                            </div>
                            <input type="hidden" id="table-number" value="{{ is_array(session('table_numbers')) ? session('table_numbers')[0] : session('table_number') }}">
                        </div>
                        <button class="btn-action btn-edit" onclick="openModal('editQtyDatBanModal')" title="Chỉnh sửa số lượng">
                            <i class="fas fa-edit"></i> Sửa
                        </button>
                    </div>
                @endif
                <div class="total-box">Tổng: <span>{{ number_format(sumTotal($dineInItems), 0, ',', '.') }}đ</span></div>
                <button class="btn-action btn-confirm" onclick="handleConfirm(this, 'dat-ban')" {{ (session('table_numbers') && count($dineInItems) > 0) ? '' : 'disabled' }}>Xác Nhận</button>
            @else
                @if(session('paid_dat-ban'))
                    <button id="btn-pay-dat-ban" type="button" class="btn-action" style="background: #777;" disabled>Đã thanh toán</button>
                @else
                    <button id="btn-pay-dat-ban" type="button" onclick="openPaymentModal('dat-ban')" class="btn-action" style="background: #27AE60;">Thanh Toán</button>
                @endif
                <a href="{{ url('/export-pdf?type=dat-ban') }}" class="btn-action btn-pdf" target="_blank">Xuất PDF hóa đơn</a>
            @endif
        </div>
    </div>
</div>

{{-- MODAL: Wizard đặt bàn --}}
<div id="bookingModal" class="modal">
    <div class="modal-content" style="width: 550px;">
        <div class="step-indicator">
            <div class="step-dot active" id="dot-1">1</div>
            <div class="step-dot" id="dot-2">2</div>
            <div class="step-dot" id="dot-3">3</div>
            <div class="step-dot" id="dot-4">4</div>
            <div class="step-dot" id="dot-5">5</div>
        </div>

        <!-- Step 1: Total Tables -->
        <div class="wizard-step active" id="step-1">
            <h3 style="color:#C0392B; margin-bottom:20px;">Bước 1: Số lượng bàn</h3>
            <p style="margin-bottom:5px; color:#666;">Bạn muốn đặt bao nhiêu bàn? (Tối đa 3 bàn)</p>
            <p style="margin-bottom:15px; color:#C0392B; font-weight:500; font-size:14px;">Chúng tôi có 3 loại bàn: &lt;=5 người, &lt;=10 người, &lt;=20 người</p>
            <div style="display:flex; justify-content:center; gap:20px; margin:30px 0;">
                @foreach([1,2,3] as $n)
                <label style="cursor:pointer; text-align:center;">
                    <input type="radio" name="total-tables" value="{{ $n }}" {{ $n == 1 ? 'checked' : '' }} style="display:none;" onchange="updateTotalTables({{ $n }})">
                    <div class="table-count-opt" id="t-opt-{{ $n }}"
                        style="width:60px; height:60px; border:2px solid {{ $n == 1 ? '#C0392B' : '#eee' }}; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:bold; color:{{ $n == 1 ? '#C0392B' : '#ccc' }}; background:{{ $n == 1 ? '#fff5f5' : '#fff' }};">
                        {{ $n }}
                    </div>
                </label>
                @endforeach
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:20px;">
                <button class="btn-action" style="background:#eee; color:#333;" onclick="closeModal('bookingModal')">Quay lại</button>
                <button class="btn-action" style="background:#C0392B;" onclick="goToStep(2)">Tiếp tục <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <!-- Step 2: Table Types -->
        <div class="wizard-step" id="step-2">
            <h3 style="color:#C0392B; margin-bottom:20px;">Bước 2: Loại bàn</h3>
            <p style="margin-bottom:15px; color:#666;">Phân bổ số lượng theo sức chứa. Tổng: <b id="step2-total-display">1</b> bàn.</p>
            <div class="type-card">
                <div><b>Bàn nhỏ (&lt;= 5 người)</b><br><small style="color:#999;">STT từ 1 - 25</small></div>
                <div class="num-selector">
                    <button class="num-btn" onclick="changeQty('type5', -1)">-</button>
                    <span class="num-val" id="qty-type5">1</span>
                    <button class="num-btn" onclick="changeQty('type5', 1)">+</button>
                </div>
            </div>
            <div class="type-card">
                <div><b>Bàn vừa (&lt;= 10 người)</b><br><small style="color:#999;">STT từ 26 - 45</small></div>
                <div class="num-selector">
                    <button class="num-btn" onclick="changeQty('type10', -1)">-</button>
                    <span class="num-val" id="qty-type10">0</span>
                    <button class="num-btn" onclick="changeQty('type10', 1)">+</button>
                </div>
            </div>
            <div class="type-card">
                <div><b>Bàn lớn (&lt;= 20 người)</b><br><small style="color:#999;">STT từ 46 - 50</small></div>
                <div class="num-selector">
                    <button class="num-btn" onclick="changeQty('type20', -1)">-</button>
                    <span class="num-val" id="qty-type20">0</span>
                    <button class="num-btn" onclick="changeQty('type20', 1)">+</button>
                </div>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:20px;">
                <button class="btn-action" style="background:#eee; color:#333;" onclick="goToStep(1)"><i class="fas fa-arrow-left"></i> Quay lại</button>
                <button class="btn-action" style="background:#C0392B;" onclick="validateStep2()">Tiếp tục <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <!-- Step 3: Specific Tables -->
        <div class="wizard-step" id="step-3">
            <h3 style="color:#C0392B; margin-bottom:10px;">Bước 3: Chọn STT bàn</h3>
            <p style="margin-bottom:15px; color:#666; font-size:14px;">Vui lòng chọn bàn cụ thể cho từng vị trí bạn đã đặt.</p>
            <div id="table-selection-area" style="max-height: 300px; overflow-y: auto; padding-right:10px;"></div>
            <div style="display:flex; justify-content:space-between; margin-top:20px;">
                <button class="btn-action" style="background:#eee; color:#333;" onclick="goToStep(2)"><i class="fas fa-arrow-left"></i> Quay lại</button>
                <button class="btn-action" style="background:#C0392B;" onclick="validateStep3()">Tiếp tục <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <!-- Step 4: Date & Time -->
        <div class="wizard-step" id="step-4">
            <h3 style="color:#C0392B; margin-bottom:20px;">Bước 4: Thời gian</h3>
            <label>Ngày đến (trong vòng 60 ngày):</label>
            <input type="date" id="wizard-date" class="modal-input" oninput="checkOverlapDebounced()"
                value="{{ session('start_date') ?? date('Y-m-d') }}"
                min="{{ date('Y-m-d') }}" max="{{ date('Y-m-d', strtotime('+60 days')) }}">
            <div style="margin-top:15px; display:flex; gap:20px;">
                <div>
                    <label>Giờ tới (7h-22h):</label>
                    <div style="display:flex; gap:5px; align-items:center; margin-top:5px;">
                        <input type="number" id="w-start-h" min="7" max="22" placeholder="HH" oninput="checkOverlapDebounced()" style="width:50px; padding:10px; border:1px solid #ddd; border-radius:6px;">
                        <b>:</b>
                        <input type="number" id="w-start-m" min="0" max="59" placeholder="MM" oninput="checkOverlapDebounced()" style="width:50px; padding:10px; border:1px solid #ddd; border-radius:6px;">
                    </div>
                </div>
                <div>
                    <label>Giờ về:</label>
                    <div style="display:flex; gap:5px; align-items:center; margin-top:5px;">
                        <input type="number" id="w-end-h" min="7" max="22" placeholder="HH" oninput="checkOverlapDebounced()" style="width:50px; padding:10px; border:1px solid #ddd; border-radius:6px;">
                        <b>:</b>
                        <input type="number" id="w-end-m" min="0" max="59" placeholder="MM" oninput="checkOverlapDebounced()" style="width:50px; padding:10px; border:1px solid #ddd; border-radius:6px;">
                    </div>
                </div>
            </div>
            <div id="overlap-warning" style="margin-top:15px; color:#C0392B; font-size:13px; font-weight:500;"></div>
            <div style="display:flex; justify-content:space-between; margin-top:20px;">
                <button class="btn-action" style="background:#eee; color:#333;" onclick="goToStep(3)"><i class="fas fa-arrow-left"></i> Quay lại</button>
                <button class="btn-action" style="background:#C0392B;" id="btnNextStep4" onclick="goToStep(5)" disabled>Tiếp tục <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <!-- Step 5: Review -->
        <div class="wizard-step" id="step-5">
            <h3 style="color:#C0392B; margin-bottom:20px;">Bước 5: Xác nhận</h3>
            <div style="background:#f9f9f9; padding:20px; border-radius:10px; line-height:1.8;">
                <div><i class="fas fa-calendar-alt"></i> Ngày: <b id="review-date">...</b></div>
                <div><i class="fas fa-clock"></i> Thời gian: <b id="review-time">...</b></div>
                <div><i class="fas fa-chair"></i> Danh sách bàn: <b id="review-tables">...</b></div>
                <div style="border-top:1px dashed #ddd; margin-top:10px; padding-top:10px;">Tối đa khách: <b id="review-guests">...</b> người</div>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:20px;">
                <button class="btn-action" style="background:#eee; color:#333;" onclick="goToStep(4)"><i class="fas fa-arrow-left"></i> Quay lại</button>
                <button class="btn-action" style="background:#C0392B;" onclick="saveBooking()">Xác nhận thông tin <i class="fas fa-check"></i></button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: Sửa số lượng --}}
<div id="editQtyDatBanModal" class="modal">
    <div class="modal-content" style="width: 600px; border-top-color: #C0392B;">
        <h3>Sửa số lượng (Tại bàn)</h3>
        <table>
            @foreach($dineInItems as $id => $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td><input type="number" class="modal-input qty-datban" data-id="{{ $id }}" value="{{ $item['quantity'] }}" min="0" oninput="if(this.value < 0) this.value = 0;"></td>
                </tr>
            @endforeach
        </table>
        <div style="display:flex; justify-content: flex-end; gap: 10px; margin-top:15px;">
            <button class="btn-action" style="background:#eee;color:#333;" onclick="closeModal('editQtyDatBanModal')">Hủy</button>
            <button class="btn-action" style="background:#C0392B;" onclick="updateQty()">Cập Nhật</button>
        </div>
    </div>
</div>

{{-- MODAL: Thanh toán --}}
<div id="paymentModal" class="modal">
    <div class="modal-content" style="border-top: 5px solid #C0392B;">
        <h3>Thanh Toán</h3>
        <p>Mã hóa đơn: <b id="pay-bill-code-display"></b></p>
        <div style="margin-top:20px; padding-top:15px; border-top:1px solid #eee;">
            <label style="display:block; font-weight:bold; color:#C0392B; margin-bottom:15px; font-size:16px;">Phương thức thanh toán:</label>
            <label class="payment-option">
                <input type="radio" name="payment_method" value="tienmat" onchange="enablePaymentBtn()">
                <img src="{{ url('/pics/icon_tien_VN.jpg') }}" alt="Tiền mặt" style="height:40px; object-fit:contain;">
                <span>Tiền mặt</span>
            </label>
            <label class="payment-option">
                <input type="radio" name="payment_method" value="momo" onchange="enablePaymentBtn()">
                <img src="{{ url('/pics/MOMO-Logo-App.png') }}" alt="Momo">
                <span>Momo</span>
            </label>
            <label class="payment-option">
                <input type="radio" name="payment_method" value="vnpay" onchange="enablePaymentBtn()">
                <img src="{{ url('/pics/Logo VNPAY-QR.svg') }}" alt="VNPay" style="height:40px; max-width:50px; object-fit:contain;">
                <span>VNPay</span>
            </label>
        </div>
        <div style="display:flex; justify-content: flex-end; gap: 10px; margin-top:20px;">
            <button id="paymentCancelBtn" class="btn-action" style="background:#eee;color:#333;" onclick="closeModal('paymentModal')">Hủy</button>
            <button class="btn-action" style="background:#C0392B;" id="paymentConfirmBtn" onclick="confirmPayment()" disabled>Xác Nhận</button>
        </div>
    </div>
</div>

{{-- MODAL: Thành công --}}
<div id="successModal" class="modal" style="background: rgba(0,0,0,0.7);">
    <div class="modal-success-content">
        <span style="position:absolute;top:10px;right:15px;font-size:24px;cursor:pointer;color:white;" onclick="closeModal('successModal')">&times;</span>
        <span class="success-icon"><i class="fas fa-check-circle"></i></span>
        <div class="success-title">Giao Dịch Thành Công!</div>
        <div class="success-msg">Cảm ơn bạn đã tin tưởng dịch vụ của chúng tôi. Hóa đơn của bạn đã được hệ thống ghi nhận.</div>
        <button class="btn-success-close" onclick="goToHistory()">Xem Lịch Sử</button>
    </div>
</div>

<script>
    const sessionVars = {
        token: "{{ csrf_token() }}",
        billDatBan: @json(session('last_bill_code_dat-ban')),
        totalTables: {{ $totalTables }},
        types: @json($types),
        tableNumbers: @json(session('table_numbers')),
        startDate: "{{ session('start_date') }}",
        startTime: "{{ session('start_time') }}",
        endTime: "{{ session('end_time') }}",
        today: "{{ date('Y-m-d') }}",
        maxDate: "{{ date('Y-m-d', strtotime('+60 days')) }}"
    };

    let currentPayType = 'dat-ban';
    let isMouseDownInsideModalContent = false;

    let bookingData = {
        currentStep: 1,
        totalTables: parseInt(sessionVars.totalTables) || 1,
        types: sessionVars.types && typeof sessionVars.types === 'object' ? sessionVars.types : { type5: 1, type10: 0, type20: 0 },
        selectedTables: Array.isArray(sessionVars.tableNumbers) ? sessionVars.tableNumbers.map(n => ({ tableNumber: n, type: 0 })) : [],
        date: sessionVars.startDate || '',
        startTime: sessionVars.startTime || '',
        endTime: sessionVars.endTime || ''
    };

    function openModal(id) {
        document.getElementById(id).style.display = "block";
        if (id === 'bookingModal') initWizardSelection();
    }
    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.style.display = 'none';
    }

    window.addEventListener('mousedown', function (e) {
        isMouseDownInsideModalContent = e.target.closest('.modal-content');
    });
    window.addEventListener('mouseup', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('modal') && !isMouseDownInsideModalContent) {
            e.target.style.display = 'none';
        }
        isMouseDownInsideModalContent = false;
    });

    function initWizardSelection() {
        const n = bookingData.totalTables;
        for (let i = 1; i <= 3; i++) {
            const opt = document.getElementById('t-opt-' + i);
            const radio = document.querySelector(`input[name="total-tables"][value="${i}"]`);
            if (i === n) {
                opt.style.borderColor = "#C0392B"; opt.style.color = "#C0392B"; opt.style.background = "#fff5f5";
                if (radio) radio.checked = true;
            } else {
                opt.style.borderColor = "#eee"; opt.style.color = "#ccc"; opt.style.background = "#fff";
                if (radio) radio.checked = false;
            }
        }
        updateTypeDisplays();
    }
    window.addEventListener('load', initWizardSelection);

    function updateTotalTables(n) {
        bookingData.totalTables = n;
        bookingData.types = { type5: n, type10: 0, type20: 0 };
        bookingData.selectedTables = [];
        initWizardSelection();
        updateTypeDisplays();
    }

    function changeQty(type, delta) {
        const newVal = bookingData.types[type] + delta;
        const currentSum = Object.values(bookingData.types).reduce((a, b) => a + b, 0) - bookingData.types[type] + newVal;
        if (newVal >= 0 && currentSum <= bookingData.totalTables) {
            bookingData.types[type] = newVal;
            bookingData.selectedTables = [];
            updateTypeDisplays();
        }
    }

    function updateTypeDisplays() {
        const sum = Object.values(bookingData.types).reduce((a, b) => a + b, 0);
        document.getElementById('step2-total-display').innerText = sum + " / " + bookingData.totalTables;
        document.getElementById('qty-type5').innerText = bookingData.types['type5'];
        document.getElementById('qty-type10').innerText = bookingData.types['type10'];
        document.getElementById('qty-type20').innerText = bookingData.types['type20'];
    }

    function goToStep(n) {
        document.querySelectorAll('.wizard-step').forEach(s => s.classList.remove('active'));
        document.getElementById('step-' + n).classList.add('active');
        for (let i = 1; i <= 5; i++) {
            const dot = document.getElementById('dot-' + i);
            dot.classList.remove('active', 'completed');
            if (i < n) dot.classList.add('completed');
            if (i === n) dot.classList.add('active');
        }
        if (n === 3) renderTableSelection();
        if (n === 5) renderReview();
    }

    function validateStep2() {
        const sum = Object.values(bookingData.types).reduce((a, b) => a + b, 0);
        if (sum <= 0) { hghAlert("Vui lòng chọn ít nhất 1 bàn!", "warning"); return; }
        if (sum > bookingData.totalTables) { hghAlert("Tổng số bàn vượt quá giới hạn!", "error"); return; }
        goToStep(3);
    }

    function renderTableSelection() {
        const container = document.getElementById('table-selection-area');
        container.innerHTML = '';
        const types = [
            { key: 'type5', label: 'Bàn nhỏ (<= 5 người)', range: [1, 25], typeVal: 5 },
            { key: 'type10', label: 'Bàn vừa (<= 10 người)', range: [26, 45], typeVal: 10 },
            { key: 'type20', label: 'Bàn lớn (<= 20 người)', range: [46, 50], typeVal: 20 }
        ];
        types.forEach(t => {
            const qty = bookingData.types[t.key];
            if (qty > 0) {
                const typeWrapper = document.createElement('div');
                typeWrapper.style.marginBottom = "25px";
                typeWrapper.innerHTML = `<b style="font-size:15px; color:#333;">${t.label} (Chọn ${qty} bàn):</b>`;
                const grid = document.createElement('div');
                grid.className = 'table-list-grid';
                for (let num = t.range[0]; num <= t.range[1]; num++) {
                    const opt = document.createElement('div');
                    opt.className = 'table-opt';
                    opt.innerText = num;
                    opt.onclick = () => selectTypeTable(t.key, num, opt, qty, t.typeVal);
                    const isSelected = bookingData.selectedTables.some(st => st.tableNumber === num);
                    if (isSelected) opt.classList.add('selected');
                    grid.appendChild(opt);
                }
                typeWrapper.appendChild(grid);
                container.appendChild(typeWrapper);
            }
        });
    }

    function selectTypeTable(typeKey, num, el, quota, typeVal) {
        const existingIdx = bookingData.selectedTables.findIndex(st => st.tableNumber === num);
        if (existingIdx > -1) {
            bookingData.selectedTables.splice(existingIdx, 1);
            el.classList.remove('selected');
        } else {
            const currentSelectedInType = bookingData.selectedTables.filter(st => st.type === typeVal).length;
            if (currentSelectedInType >= quota) {
                hghAlert(`Bạn chỉ được chọn tối đa ${quota} bàn loại này`, 'warning');
                return;
            }
            bookingData.selectedTables.push({ tableNumber: num, type: typeVal });
            el.classList.add('selected');
        }
    }

    function validateStep3() {
        const totalRequired = Object.values(bookingData.types).reduce((a, b) => a + b, 0);
        if (bookingData.selectedTables.length < totalRequired) {
            hghAlert("Vui lòng chọn đủ " + totalRequired + " bàn cụ thể!", "warning");
            return;
        }
        goToStep(4);
        checkOverlapDebounced();
    }

    let overlapTimeout;
    function checkOverlapDebounced() {
        clearTimeout(overlapTimeout);
        overlapTimeout = setTimeout(checkOverlap, 500);
    }

    async function checkOverlap() {
        const date = document.getElementById('wizard-date').value;
        const sh = document.getElementById('w-start-h').value;
        const sm = document.getElementById('w-start-m').value || '0';
        const eh = document.getElementById('w-end-h').value;
        const em = document.getElementById('w-end-m').value || '0';

        if (!date || !sh || !eh) {
            document.getElementById('overlap-warning').innerText = "";
            document.getElementById('btnNextStep4').disabled = true;
            return;
        }
        if (date < sessionVars.today) {
            document.getElementById('overlap-warning').innerHTML = `<i class="fas fa-exclamation-triangle"></i> Bạn không thể chọn ngày trong quá khứ!`;
            document.getElementById('btnNextStep4').disabled = true; return;
        }
        if (date > sessionVars.maxDate) {
            document.getElementById('overlap-warning').innerHTML = `<i class="fas fa-exclamation-triangle"></i> Bạn chỉ có thể đặt bàn trong vòng 60 ngày tới!`;
            document.getElementById('btnNextStep4').disabled = true; return;
        }

        const tables = bookingData.selectedTables.map(st => st.tableNumber);
        if (tables.length === 0) {
            document.getElementById('overlap-warning').innerText = ""; document.getElementById('btnNextStep4').disabled = true; return;
        }

        const startTime = String(sh).padStart(2, '0') + ':' + String(sm).padStart(2, '0');
        const endTime = String(eh).padStart(2, '0') + ':' + String(em).padStart(2, '0');
        const startTotal = (parseInt(sh) * 60) + parseInt(sm);
        const endTotal = (parseInt(eh) * 60) + parseInt(em);
        const duration = endTotal - startTotal;

        if (parseInt(sh) < 7 || endTotal > (22 * 60)) {
            document.getElementById('overlap-warning').innerHTML = `<i class="fas fa-exclamation-triangle"></i> Nhà hàng chỉ hoạt động từ 07:00 đến 22:00!`;
            document.getElementById('btnNextStep4').disabled = true; return;
        }
        if (duration <= 0) {
            document.getElementById('overlap-warning').innerHTML = `<i class="fas fa-exclamation-triangle"></i> Giờ về phải sau giờ đến!`;
            document.getElementById('btnNextStep4').disabled = true; return;
        }
        if (duration > 90) {
            document.getElementById('overlap-warning').innerHTML = `<i class="fas fa-exclamation-triangle"></i> Thời gian đặt bàn tối đa là 90 phút!`;
            document.getElementById('btnNextStep4').disabled = true; return;
        }

        document.getElementById('overlap-warning').innerHTML = `<span style="color:#C0392B;"><i class="fas fa-spinner fa-spin"></i> Đang kiểm tra trùng lịch...</span>`;
        document.getElementById('btnNextStep4').disabled = true;

        try {
            const res = await fetch("{{ url('/check-multi-overlap') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': sessionVars.token },
                body: JSON.stringify({ date, start_time: startTime, end_time: endTime, tables: tables })
            });
            const data = await res.json();
            if (data.status === 'error') {
                document.getElementById('overlap-warning').innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${data.message}`;
                document.getElementById('btnNextStep4').disabled = true;
            } else {
                document.getElementById('overlap-warning').innerHTML = `<span style="color:#27AE60;"><i class="fas fa-check-circle"></i> Thời gian này khả dụng cho tất cả bàn chọn!</span>`;
                document.getElementById('btnNextStep4').disabled = false;
                bookingData.date = date; bookingData.startTime = startTime; bookingData.endTime = endTime;
            }
        } catch (e) {
            document.getElementById('overlap-warning').innerHTML = `<span style="color:red;"><i class="fas fa-times-circle"></i> Lỗi: ${e.message}</span>`;
            document.getElementById('btnNextStep4').disabled = true;
        }
    }

    function renderReview() {
        document.getElementById('review-date').innerText = bookingData.date;
        document.getElementById('review-time').innerText = `${bookingData.startTime} - ${bookingData.endTime}`;
        document.getElementById('review-tables').innerText = bookingData.selectedTables.map(st => st.tableNumber).sort((a, b) => a - b).map(n => '#' + n).join(', ');
        const totalGuests = bookingData.selectedTables.reduce((sum, st) => sum + st.type, 0);
        document.getElementById('review-guests').innerText = totalGuests;
    }

    async function saveBooking() {
        const res = await fetch("{{ url('/save-booking') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': sessionVars.token },
            body: JSON.stringify({
                tables: bookingData.selectedTables.map(st => ({ number: st.tableNumber, type: st.type })),
                start_date: bookingData.date,
                start_time: bookingData.startTime,
                end_time: bookingData.endTime
            })
        });
        if (res.ok) { closeModal('bookingModal'); location.reload(); }
        else { const err = await res.json(); hghAlert(err.message || "Lỗi đặt bàn!", "error"); }
    }

    async function updateQty() {
        const updates = {};
        document.querySelectorAll('.qty-datban').forEach(input => { updates[input.dataset.id] = input.value; });
        const res = await fetch("{{ url('/update-cart-quantities') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': sessionVars.token },
            body: JSON.stringify({ quantities: updates })
        });
        if (res.ok) location.reload();
        else hghAlert("Lỗi cập nhật!", "error");
    }

    async function handleConfirm(btn, type) {
        hghConfirm("Chốt đơn đặt bàn?").then(async (result) => {
            if (!result.isConfirmed) return;
            btn.disabled = true;
            btn.innerText = "Chờ xử lý...";

            const res = await fetch("{{ url('/booking-table/checkout') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': sessionVars.token },
                body: JSON.stringify({ order_type: type })
            });

            if (res.ok) location.reload();
            else {
                const err = await res.json();
                hghAlert("Lỗi: " + (err.message || "Không thể lưu đơn"), "error");
                btn.disabled = false;
                btn.innerText = "Xác Nhận";
            }
        });
    }

    function openPaymentModal(type) {
        currentPayType = type;
        const billCode = sessionVars.billDatBan;
        if (!billCode) {
            hghAlert("Bạn chưa có mã hóa đơn. Vui lòng bấm 'Xác Nhận' trước khi thanh toán.", "warning");
            return;
        }
        document.getElementById('pay-bill-code-display').innerText = billCode;
        document.querySelectorAll('input[name="payment_method"]').forEach(r => r.checked = false);
        document.getElementById('paymentConfirmBtn').disabled = true;
        openModal('paymentModal');
    }

    function enablePaymentBtn() {
        document.getElementById('paymentConfirmBtn').disabled = !document.querySelector('input[name="payment_method"]:checked');
    }

    function goToHistory() { location.href = "{{ route('transaction_history') }}"; }

    async function confirmPayment() {
        const btn = document.getElementById('paymentConfirmBtn');
        const cancelBtn = document.getElementById('paymentCancelBtn');
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) return;
        const paymentMethod = selectedMethod.value;
        const billCode = (document.getElementById('pay-bill-code-display')?.innerText || '').trim();

        btn.disabled = true; cancelBtn.disabled = true; cancelBtn.style.opacity = '0.5';
        btn.innerText = "Chờ thanh toán...";

        try {
            const res = await fetch("{{ route('process_payment') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': sessionVars.token },
                body: JSON.stringify({ order_type: currentPayType, payment_method: paymentMethod, bill_code: billCode })
            });
            if (!res.ok) {
                let errMsg = "Không thể xử lý thanh toán";
                try { const err = await res.json(); errMsg = err.message || errMsg; } catch (_) { }
                hghAlert("Lỗi: " + errMsg, "error").finally(() => { if (res.status === 422) location.reload(); });
                if (res.status !== 422) { btn.disabled = false; cancelBtn.disabled = false; cancelBtn.style.opacity = '1'; btn.innerText = "Xác Nhận"; }
                return;
            }
            closeModal('paymentModal');
            openModal('successModal');
            const mainBtn = document.getElementById('btn-pay-dat-ban');
            if (mainBtn) { mainBtn.innerText = "Đã thanh toán"; mainBtn.style.background = "#777"; mainBtn.disabled = true; mainBtn.onclick = null; }
        } catch (e) {
            hghAlert("Lỗi kết nối: " + e.message, "error");
            btn.disabled = false; cancelBtn.disabled = false; btn.innerText = "Xác Nhận";
        }
    }
</script>
@endsection
