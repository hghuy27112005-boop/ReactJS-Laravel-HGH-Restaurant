@extends('layout')

@section('content')
@php
    $items = session()->get('cart', []);
    $deliveryItems = array_filter($items, function ($item) {
        return ($item['order_type'] ?? '') === 'mang-ve';
    });
    if (!function_exists('sumTotal')) {
        function sumTotal($arr) {
            return array_reduce($arr, function ($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0);
        }
    }
    $statusLabels = [
        'waiting_confirmation' => ['label' => 'Chờ duyệt', 'color' => '#F39C12', 'icon' => 'fa-clock', 'bg' => '#FEF9E7'],
        'waiting_delivery'     => ['label' => 'Chờ giao',  'color' => '#3498DB', 'icon' => 'fa-box',   'bg' => '#EBF5FB'],
        'delivering'           => ['label' => 'Đang giao', 'color' => '#2980B9', 'icon' => 'fa-truck', 'bg' => '#EBF5FB'],
        'delivered'            => ['label' => 'Đã giao',   'color' => '#27AE60', 'icon' => 'fa-check-circle', 'bg' => '#EAFAF1'],
        'cancelled'            => ['label' => 'Đã hủy',   'color' => '#E74C3C', 'icon' => 'fa-times-circle',  'bg' => '#FDEDEC'],
    ];
@endphp
<!-- DEBUG CART: {{ json_encode(session()->get('cart', [])) }} -->

<style>
    * { box-sizing: border-box; }
    body { background: #f5f5f5; }

    /* ─── PAGE LAYOUT ─── */
    .page-wrap {
        max-width: 1100px;
        margin: 30px auto;
        padding: 0 20px;
        font-family: 'Segoe UI', sans-serif;
    }
    .back-nav { margin-bottom: 18px; }
    .btn-back { display: inline-flex; align-items: center; gap: 8px; color: #666; text-decoration: none; font-weight: 600; font-size: 14px; transition: .2s; }
    .btn-back:hover { color: #C0392B; transform: translateX(-4px); }
    .page-switch { text-align: right; margin-bottom: 12px; }
    .page-switch a { color: #C0392B; font-weight: 600; text-decoration: none; font-size: 14px; }
    .page-switch a:hover { text-decoration: underline; }

    /* ─── TOP SECTION: ORDER FORM ─── */
    .order-panel {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,.08);
        border-top: 5px solid #C0392B;
        padding: 28px;
        margin-bottom: 32px;
    }
    .panel-title {
        font-size: 20px;
        font-weight: 700;
        color: #C0392B;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .panel-title .badge {
        font-size: 11px;
        background: #C0392B;
        color: #fff;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 600;
        letter-spacing: .5px;
    }

    /* Cart table */
    .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .cart-table th { text-align: left; padding: 12px 14px; border-bottom: 2px solid #eee; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; }
    .cart-table td { padding: 14px; border-bottom: 1px solid #f5f5f5; font-size: 14px; vertical-align: middle; }
    .dish-name { font-weight: 600; color: #222; }
    .dish-note { font-size: 12px; color: #aaa; font-style: italic; }

    /* Address box */
    .addr-row { display: flex; gap: 12px; align-items: flex-end; margin-bottom: 18px; }
    .addr-field { flex: 1; }
    .addr-field label { font-size: 12px; font-weight: 700; color: #C0392B; display: block; margin-bottom: 6px; }
    .addr-box {
        padding: 12px 16px; border: 1.5px solid #ddd; border-radius: 10px;
        font-size: 14px; cursor: pointer; background: #fafafa; transition: .2s;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #555;
        height: 46px; display: flex; align-items: center; gap: 8px;
    }
    .addr-box:hover { border-color: #C0392B; background: #fff5f5; }
    .addr-placeholder { color: #bbb; }

    /* Action footer */
    .form-footer { display: flex; justify-content: flex-end; align-items: center; gap: 14px; padding-top: 16px; border-top: 1px dashed #eee; flex-wrap: wrap; }
    .total-chip {
        background: #FDF2F2; border: 1px solid #FADBD8; border-radius: 10px;
        padding: 10px 22px; font-size: 17px; font-weight: 700; color: #333;
    }
    .total-chip span { color: #C0392B; }

    /* Buttons */
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; border-radius: 10px; border: none; font-weight: 700; font-size: 14px; cursor: pointer; transition: .2s; text-decoration: none; color: #fff; }
    .btn-red { background: #C0392B; }
    .btn-red:hover { background: #a93226; }
    .btn-green { background: #27AE60; }
    .btn-green:hover { background: #219a52; }
    .btn-gray { background: #95a5a6; }
    .btn:disabled { background: #ccc !important; cursor: not-allowed; opacity: .7; }
    .btn-edit { background: #7f8c8d; height: 46px; padding: 0 16px; }

    /* Empty state */
    .empty-state {
        text-align: center; padding: 40px 20px; color: #bbb;
    }
    .empty-state i { font-size: 48px; margin-bottom: 14px; display: block; }
    .empty-state p { font-size: 15px; }
    .empty-state a { color: #C0392B; font-weight: 600; text-decoration: none; }
    .empty-state a:hover { text-decoration: underline; }

    /* ─── BOTTOM SECTION: ORDER LIST ─── */
    .orders-panel {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,.06);
        padding: 24px 28px;
    }
    .orders-panel-title {
        font-size: 18px; font-weight: 700; color: #333; margin-bottom: 18px;
        display: flex; align-items: center; gap: 10px;
    }

    /* Filter bar */
    .filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
    .filter-btn {
        padding: 7px 16px; border-radius: 20px; border: 1.5px solid #ddd;
        background: #fff; font-size: 13px; font-weight: 600; cursor: pointer;
        color: #666; transition: .2s;
    }
    .filter-btn:hover { border-color: #C0392B; color: #C0392B; }
    .filter-btn.active { background: #C0392B; color: #fff; border-color: #C0392B; }
    .filter-btn.all.active { background: #2c3e50; border-color: #2c3e50; }

    /* Order cards */
    .order-card {
        border: 1.5px solid #eee;
        border-radius: 12px;
        padding: 18px 20px;
        margin-bottom: 14px;
        transition: .2s;
        position: relative;
        overflow: hidden;
    }
    .order-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); border-color: #ddd; }
    .order-card-header {
        display: flex; justify-content: space-between; align-items: flex-start;
        margin-bottom: 12px; flex-wrap: wrap; gap: 8px;
    }
    .order-id { font-size: 13px; color: #999; font-weight: 600; }
    .order-id span { color: #555; }
    .status-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;
    }
    .order-items-list { margin-bottom: 12px; }
    .order-item-row { display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0; color: #444; }
    .order-item-name { color: #333; }
    .order-item-price { color: #C0392B; font-weight: 600; }
    .order-footer {
        display: flex; justify-content: space-between; align-items: center;
        padding-top: 12px; border-top: 1px dashed #eee; flex-wrap: wrap; gap: 8px;
    }
    .order-addr { font-size: 13px; color: #777; display: flex; align-items: center; gap: 6px; }
    .order-total { font-size: 16px; font-weight: 700; color: #C0392B; }
    .order-date { font-size: 12px; color: #bbb; }

    .no-orders { text-align: center; padding: 40px; color: #ccc; }
    .no-orders i { font-size: 40px; display: block; margin-bottom: 12px; }

    /* ─── MODALS ─── */
    .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,.5); }
    .modal-box { background: #fff; margin: 8% auto; padding: 28px; border-radius: 16px; width: 480px; position: relative; border-top: 5px solid #C0392B; box-shadow: 0 10px 40px rgba(0,0,0,.15); }
    .modal-box h3 { margin: 0 0 18px; font-size: 18px; color: #333; }
    .modal-input { width: 100%; padding: 12px 14px; border: 1.5px solid #ddd; border-radius: 10px; font-size: 14px; margin-top: 6px; }
    .modal-input:focus { outline: none; border-color: #C0392B; }
    .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }

    .payment-option { display: flex; align-items: center; gap: 14px; margin-bottom: 12px; cursor: pointer; padding: 14px 16px; border: 2px solid #eee; border-radius: 10px; transition: .2s; }
    .payment-option:hover, .payment-option.selected { border-color: #C0392B; background: #fff5f5; }
    .payment-option input { display: none; }
    .payment-option img { height: 36px; object-fit: contain; }
    .payment-option span { font-size: 15px; font-weight: 600; color: #333; }

    .success-modal-box { background: linear-gradient(135deg, #C0392B, #E74C3C); color: #fff; text-align: center; padding: 44px 32px; border-radius: 20px; width: 380px; margin: 10% auto; position: relative; box-shadow: 0 20px 50px rgba(192,57,43,.4); border: none; }
    .success-modal-box .icon { font-size: 64px; margin-bottom: 16px; display: block; }
    .success-modal-box h3 { font-size: 22px; margin: 0 0 10px; }
    .success-modal-box p { font-size: 15px; opacity: .9; margin: 0 0 24px; }
    .btn-white { background: #fff; color: #C0392B; padding: 12px 28px; border-radius: 30px; border: none; font-weight: 700; font-size: 14px; cursor: pointer; transition: .2s; }
    .btn-white:hover { transform: scale(1.04); }
</style>

<div class="page-wrap">
    <div class="back-nav">
        <a href="{{ url('/menu') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Quay về Menu</a>
    </div>
    <div class="page-switch">
        <a href="{{ url('/booking-table') }}"><i class="fas fa-chair"></i> Chuyển sang Đặt Bàn</a>
    </div>

    {{-- ═══ TOP: FORM ĐẶT HÀNG ═══ --}}
    <div class="order-panel">
        <div class="panel-title">
            <i class="fas fa-motorcycle"></i>
            Đặt Giao Hàng
            <span class="badge">DELIVERY</span>
        </div>

        @if(count($deliveryItems) > 0)
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tên Món</th>
                        <th>SL</th>
                        <th>Đơn Giá</th>
                        <th>Thành Tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deliveryItems as $id => $item)
                        <tr>
                            <td style="color:#bbb;">{{ $loop->iteration }}</td>
                            <td>
                                <div class="dish-name">{{ $item['name'] }}</div>
                                @if(!empty($item['note']) && $item['note'] !== 'Không có')
                                    <div class="dish-note">{{ $item['note'] }}</div>
                                @endif
                            </td>
                            <td>{{ $item['quantity'] }}</td>
                            <td>{{ number_format($item['price'], 0, ',', '.') }}đ</td>
                            <td style="font-weight:700; color:#C0392B;">{{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="addr-row">
                <div class="addr-field">
                    <label><i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng</label>
                    <div class="addr-box" onclick="openModal('addressModal')" id="addr-display">
                        @if(session('user_address'))
                            <i class="fas fa-map-marker-alt" style="color:#C0392B;"></i>
                            <span>{{ session('user_address') }}</span>
                        @else
                            <i class="fas fa-map-marker-alt" style="color:#ddd;"></i>
                            <span class="addr-placeholder">Nhấn để nhập địa chỉ...</span>
                        @endif
                    </div>
                    <input type="hidden" id="address-mang-ve" value="{{ session('user_address') }}">
                </div>
                <button class="btn btn-edit" onclick="openModal('editQtyModal')" title="Sửa số lượng">
                    <i class="fas fa-edit"></i> Sửa
                </button>
            </div>

            <div class="form-footer">
                <div class="total-chip">
                    Tổng: <span>{{ number_format(sumTotal($deliveryItems), 0, ',', '.') }}đ</span>
                </div>
                <button class="btn btn-red" id="btn-confirm" onclick="handleConfirm(this)"
                    {{ session('user_address') ? '' : 'disabled' }}>
                    <i class="fas fa-check"></i> Xác Nhận & Thanh Toán
                </button>
            </div>

        @else
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <p>Bạn chưa chọn món nào.<br>
                <a href="{{ url('/menu') }}">Về Menu để chọn món</a> rồi quay lại đây nhé!</p>
            </div>
        @endif
    </div>

    {{-- ═══ BOTTOM: DANH SÁCH ĐƠN HÀNG ═══ --}}
    <div class="orders-panel">
        <div class="orders-panel-title">
            <i class="fas fa-list-alt" style="color:#C0392B;"></i>
            Đơn Hàng Của Tôi
        </div>

        {{-- Filter bar --}}
        <div class="filter-bar">
            <button class="filter-btn all active" onclick="filterOrders('all', this)">
                <i class="fas fa-th-list"></i> Tất cả
            </button>
            <button class="filter-btn" onclick="filterOrders('waiting_confirmation', this)" data-status="waiting_confirmation">
                <i class="fas fa-clock"></i> Chờ duyệt
            </button>
            <button class="filter-btn" onclick="filterOrders('waiting_delivery', this)" data-status="waiting_delivery">
                <i class="fas fa-box"></i> Chờ giao
            </button>
            <button class="filter-btn" onclick="filterOrders('delivering', this)" data-status="delivering">
                <i class="fas fa-truck"></i> Đang giao
            </button>
            <button class="filter-btn" onclick="filterOrders('delivered', this)" data-status="delivered">
                <i class="fas fa-check-circle"></i> Đã giao
            </button>
            <button class="filter-btn" onclick="filterOrders('cancelled', this)" data-status="cancelled">
                <i class="fas fa-times-circle"></i> Đã hủy
            </button>
        </div>

        <div id="orders-list">
            @forelse($activeOrders as $order)
                @php
                    $ds = $order->delivery->delivery_status ?? 'waiting_confirmation';
                    $si = $statusLabels[$ds] ?? ['label' => $ds, 'color' => '#999', 'icon' => 'fa-circle', 'bg' => '#f5f5f5'];
                @endphp
                <div class="order-card" data-status="{{ $ds }}">
                    <div class="order-card-header">
                        <div>
                            <div class="order-id">Mã ĐH: <span>#{{ $order->order_id }}</span></div>
                            <div class="order-date"><i class="fas fa-calendar-alt"></i> {{ $order->created_at->format('H:i d/m/Y') }}</div>
                        </div>
                        <span class="status-badge" style="background:{{ $si['bg'] }}; color:{{ $si['color'] }}; border: 1px solid {{ $si['color'] }}20;">
                            <i class="fas {{ $si['icon'] }}"></i> {{ $si['label'] }}
                        </span>
                    </div>

                    <div class="order-items-list">
                        @foreach($order->items as $item)
                            <div class="order-item-row">
                                <span class="order-item-name">{{ $item->dish->dish_name ?? 'Món ăn' }} × {{ $item->quantity }}</span>
                                <span class="order-item-price">{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}đ</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="order-footer">
                        <div class="order-addr">
                            <i class="fas fa-map-marker-alt" style="color:#C0392B;"></i>
                            {{ $order->delivery->address ?? '—' }}
                        </div>
                        <div style="text-align:right;">
                            <div class="order-total">{{ number_format($order->bill->total_price ?? 0, 0, ',', '.') }}đ</div>
                            <div style="font-size:12px; color:#bbb; margin-top:2px;">
                                {{ $order->bill->payment_method ?? '' }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="no-orders">
                    <i class="fas fa-inbox"></i>
                    <p>Chưa có đơn hàng nào.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ─── MODAL: ĐỊA CHỈ ─── --}}
<div id="addressModal" class="modal">
    <div class="modal-box">
        <h3><i class="fas fa-map-marker-alt" style="color:#C0392B;"></i> Địa chỉ giao hàng</h3>
        <label style="font-size:13px; color:#666;">Nhập địa chỉ nhận hàng của bạn:</label>
        <textarea id="address-input-modal" class="modal-input" rows="3" placeholder="VD: 123 Nguyễn Huệ, Q.1, TP.HCM">{{ session('user_address') }}</textarea>
        <div class="modal-footer">
            <button class="btn btn-gray" onclick="closeModal('addressModal')">Hủy</button>
            <button class="btn btn-red" onclick="saveAddress()"><i class="fas fa-save"></i> Lưu</button>
        </div>
    </div>
</div>

{{-- ─── MODAL: SỬA SỐ LƯỢNG ─── --}}
<div id="editQtyModal" class="modal">
    <div class="modal-box" style="width: 560px;">
        <h3><i class="fas fa-edit" style="color:#C0392B;"></i> Sửa số lượng</h3>
        <table style="width:100%;">
            @foreach($deliveryItems as $id => $item)
                <tr>
                    <td style="padding:8px 0; font-size:14px;">{{ $item['name'] }}</td>
                    <td style="padding:8px 0; width:100px;">
                        <input type="number" class="modal-input qty-input" data-id="{{ $id }}"
                            value="{{ $item['quantity'] }}" min="0"
                            style="text-align:center; padding:8px;"
                            oninput="if(this.value < 0) this.value = 0;">
                    </td>
                    <td style="padding:8px 0; text-align:right; font-size:13px; color:#999; padding-left:10px;">
                        (0 = xóa)
                    </td>
                </tr>
            @endforeach
        </table>
        <div class="modal-footer">
            <button class="btn btn-gray" onclick="closeModal('editQtyModal')">Hủy</button>
            <button class="btn btn-red" onclick="updateQty()"><i class="fas fa-check"></i> Cập Nhật</button>
        </div>
    </div>
</div>

{{-- ─── MODAL: THANH TOÁN ─── --}}
<div id="paymentModal" class="modal">
    <div class="modal-box">
        <h3><i class="fas fa-credit-card" style="color:#C0392B;"></i> Chọn phương thức thanh toán</h3>
        <p style="font-size:13px; color:#999; margin:0 0 16px;">Mã HĐ: <b id="pay-bill-code-display" style="color:#333;"></b></p>

        <label class="payment-option" onclick="selectPayment(this, 'tienmat')">
            <input type="radio" name="payment_method" value="tienmat">
            <img src="{{ url('/pics/icon_tien_VN.jpg') }}" alt="Tiền mặt">
            <span>Tiền mặt</span>
        </label>
        <label class="payment-option" onclick="selectPayment(this, 'momo')">
            <input type="radio" name="payment_method" value="momo">
            <img src="{{ url('/pics/MOMO-Logo-App.png') }}" alt="Momo">
            <span>Momo</span>
        </label>
        <label class="payment-option" onclick="selectPayment(this, 'vnpay')">
            <input type="radio" name="payment_method" value="vnpay">
            <img src="{{ url('/pics/Logo VNPAY-QR.svg') }}" alt="VNPay" style="height:32px; max-width:50px;">
            <span>VNPay</span>
        </label>

        <div class="modal-footer">
            <button class="btn btn-gray" id="paymentCancelBtn" onclick="closeModal('paymentModal')">Hủy</button>
            <button class="btn btn-green" id="paymentConfirmBtn" onclick="confirmPayment()" disabled>
                <i class="fas fa-lock"></i> Xác Nhận Thanh Toán
            </button>
        </div>
    </div>
</div>

{{-- ─── MODAL: THÀNH CÔNG ─── --}}
<div id="successModal" class="modal" style="background:rgba(0,0,0,.7);">
    <div class="success-modal-box">
        <span class="icon"><i class="fas fa-check-circle"></i></span>
        <h3>Đặt hàng thành công!</h3>
        <p>Đơn hàng của bạn đang chờ duyệt.<br>Chúng tôi sẽ liên hệ xác nhận sớm nhất!</p>
        <button class="btn-white" onclick="location.reload()">
            <i class="fas fa-redo"></i> Đặt thêm
        </button>
    </div>
</div>

<script>
    const CSRF = "{{ csrf_token() }}";
    const billCode = @json(session('last_bill_code_mang-ve'));
    let selectedPayMethod = null;

    /* ── Modal helpers ── */
    function openModal(id) { document.getElementById(id).style.display = 'block'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    // Close on backdrop click
    let _mouseInContent = false;
    window.addEventListener('mousedown', e => { _mouseInContent = !!e.target.closest('.modal-box'); });
    window.addEventListener('mouseup', e => {
        if (e.target.classList.contains('modal') && !_mouseInContent) e.target.style.display = 'none';
        _mouseInContent = false;
    });

    /* ── Save address ── */
    async function saveAddress() {
        const addr = document.getElementById('address-input-modal').value.trim();
        if (!addr) { hghAlert('Vui lòng nhập địa chỉ!', 'warning'); return; }

        await fetch("{{ url('/save-address') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ address: addr })
        });
        location.reload();
    }

    /* ── Update quantities ── */
    async function updateQty() {
        const updates = {};
        document.querySelectorAll('.qty-input').forEach(inp => { updates[inp.dataset.id] = inp.value; });
        const res = await fetch("{{ url('/update-cart-quantities') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ quantities: updates })
        });
        if (res.ok) location.reload();
        else hghAlert('Lỗi cập nhật!', 'error');
    }

    /* ── Confirm order → open payment modal ── */
    async function handleConfirm(btn) {
        const addr = document.getElementById('address-mang-ve').value;
        if (!addr) { hghAlert('Vui lòng nhập địa chỉ giao hàng!', 'warning'); return; }

        const confirmed = await hghConfirm('Xác nhận đặt đơn giao hàng này?');
        if (!confirmed.isConfirmed) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

        try {
            const res = await fetch("{{ url('/delivery/checkout') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ order_type: 'mang-ve', address: addr })
            });
            const data = await res.json();

            if (res.ok && data.status === 'success') {
                // Lấy bill code mới từ response (nếu có), rồi mở modal thanh toán
                const newBillCode = data.bill_id ?? billCode;
                document.getElementById('pay-bill-code-display').innerText = newBillCode ?? '—';
                openModal('paymentModal');
            } else {
                hghAlert('Lỗi: ' + (data.message || 'Không thể lưu đơn'), 'error');
            }
        } catch (e) {
            hghAlert('Lỗi kết nối: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Xác Nhận & Thanh Toán';
        }
    }

    /* ── Payment modal ── */
    function selectPayment(label, method) {
        document.querySelectorAll('.payment-option').forEach(l => l.classList.remove('selected'));
        label.classList.add('selected');
        selectedPayMethod = method;
        document.getElementById('paymentConfirmBtn').disabled = false;
    }

    async function confirmPayment() {
        if (!selectedPayMethod) return;

        const btn = document.getElementById('paymentConfirmBtn');
        const cancelBtn = document.getElementById('paymentCancelBtn');
        const bc = document.getElementById('pay-bill-code-display').innerText.trim();

        btn.disabled = true; cancelBtn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang thanh toán...';

        try {
            const res = await fetch("{{ route('process_payment') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ order_type: 'mang-ve', payment_method: selectedPayMethod, bill_code: bc })
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                hghAlert('Lỗi: ' + (err.message || 'Không thể thanh toán'), 'error').finally(() => {
                    if (res.status === 422) location.reload();
                });
                btn.disabled = false; cancelBtn.disabled = false;
                btn.innerHTML = '<i class="fas fa-lock"></i> Xác Nhận Thanh Toán';
                return;
            }

            closeModal('paymentModal');
            openModal('successModal');
        } catch (e) {
            hghAlert('Lỗi kết nối: ' + e.message, 'error');
            btn.disabled = false; cancelBtn.disabled = false;
            btn.innerHTML = '<i class="fas fa-lock"></i> Xác Nhận Thanh Toán';
        }
    }

    /* ── Filter orders ── */
    function filterOrders(status, btn) {
        // Update active button
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Show/hide cards
        document.querySelectorAll('.order-card').forEach(card => {
            if (status === 'all') {
                card.style.display = '';
            } else {
                card.style.display = card.dataset.status === status ? '' : 'none';
            }
        });

        // Show empty message if all hidden
        const visible = [...document.querySelectorAll('.order-card')].filter(c => c.style.display !== 'none');
        const noEl = document.querySelector('.no-orders');
        if (noEl) noEl.style.display = visible.length === 0 ? '' : 'none';
    }
</script>
@endsection
