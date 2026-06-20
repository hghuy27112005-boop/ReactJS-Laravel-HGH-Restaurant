@extends('layout')

@section('content')
<style>
    .menu-wrap { padding: 40px 50px; max-width: 1200px; margin: 0 auto; font-family: 'Segoe UI', sans-serif; }
    .menu-title { text-align: center; color: #C0392B; font-size: 32px; margin-bottom: 40px; font-weight: 800; letter-spacing: 1px; }

    .dish-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 28px; }

    .dish-card {
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.07);
        background: #fff;
        transition: transform .2s, box-shadow .2s;
        border: 1px solid #f0f0f0;
    }
    .dish-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,0,0,0.12); }

    .dish-card img { width: 100%; height: 190px; object-fit: cover; display: block; transition: opacity .2s; }
    .dish-card img:hover { opacity: .88; }

    .dish-body { padding: 14px 16px 16px; }
    .dish-body .dish-name { font-size: 16px; font-weight: 700; color: #222; margin: 0 0 6px; line-height: 1.3; }
    .dish-body .dish-price { font-size: 17px; font-weight: 800; color: #C0392B; margin: 0 0 14px; }

    .btn-add-wrap { display: flex; gap: 8px; }
    .btn-add {
        flex: 1;
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 9px 10px; border: none; border-radius: 10px;
        font-size: 13px; font-weight: 700; cursor: pointer; transition: .2s;
        text-decoration: none; color: #fff;
    }
    .btn-ship   { background: #C0392B; }
    .btn-ship:hover  { background: #a93226; }
    .btn-table  { background: #2c3e50; }
    .btn-table:hover { background: #1a252f; }

    /* Guest buttons */
    .btn-login { background: #95a5a6; }
    .btn-login:hover { background: #7f8c8d; }

    /* ─── MODAL ─── */
    .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,.55); }
    .modal-box {
        background: #fff; margin: 9% auto; padding: 0; border-radius: 20px;
        width: 380px; box-shadow: 0 12px 40px rgba(0,0,0,.2); overflow: hidden; position: relative;
    }
    .modal-header {
        background: #C0392B; color: #fff; padding: 20px 24px 16px;
    }
    .modal-header h3 { margin: 0; font-size: 17px; font-weight: 700; }
    .modal-header .sub { font-size: 13px; opacity: .85; margin-top: 4px; }
    .modal-close {
        position: absolute; top: 14px; right: 18px;
        font-size: 22px; color: rgba(255,255,255,.8); cursor: pointer; line-height: 1;
    }
    .modal-close:hover { color: #fff; }
    .modal-body { padding: 22px 24px; }

    .qty-row { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
    .qty-label { font-size: 14px; font-weight: 600; color: #555; flex: 1; }
    .qty-ctrl { display: flex; align-items: center; gap: 10px; }
    .qty-btn {
        width: 34px; height: 34px; border-radius: 50%; border: 2px solid #C0392B;
        background: #fff; color: #C0392B; font-size: 18px; font-weight: 700;
        cursor: pointer; display: flex; align-items: center; justify-content: center; transition: .15s;
    }
    .qty-btn:hover { background: #C0392B; color: #fff; }
    .qty-val { font-size: 20px; font-weight: 800; min-width: 28px; text-align: center; color: #222; }

    .modal-actions { display: flex; gap: 10px; }
    .btn-modal {
        flex: 1; padding: 13px; border: none; border-radius: 12px;
        font-size: 14px; font-weight: 700; cursor: pointer; transition: .2s;
        display: flex; align-items: center; justify-content: center; gap: 7px; color: #fff;
    }
    .btn-modal-ship  { background: #C0392B; }
    .btn-modal-ship:hover  { background: #a93226; }
    .btn-modal-table { background: #2c3e50; }
    .btn-modal-table:hover { background: #1a252f; }
</style>

<div class="menu-wrap">
    <h2 class="menu-title">THỰC ĐƠN NHÀ HÀNG</h2>

    <div class="dish-grid">
        @foreach($danhSachMon as $mon)
            <div class="dish-card">
                <a href="{{ url('/menu/' . $mon->dish_id) }}">
                    <img src="{{ $mon->image_url }}" alt="{{ $mon->dish_name }}">
                </a>

                <div class="dish-body">
                    <a href="{{ url('/menu/' . $mon->dish_id) }}" style="text-decoration:none;">
                        <p class="dish-name">{{ $mon->dish_name }}</p>
                    </a>
                    <p class="dish-price">{{ number_format($mon->price, 0, ',', '.') }} VNĐ</p>

                    <div class="btn-add-wrap">
                        @auth
                            <button class="btn-add btn-ship"
                                onclick="openModal('{{ $mon->dish_id }}', '{{ addslashes($mon->dish_name) }}', {{ $mon->price }}, 'mang-ve')">
                                <i class="fas fa-motorcycle"></i> Đặt Ship
                            </button>
                            <button class="btn-add btn-table"
                                onclick="openModal('{{ $mon->dish_id }}', '{{ addslashes($mon->dish_name) }}', {{ $mon->price }}, 'dat-ban')">
                                <i class="fas fa-chair"></i> Đặt Bàn
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="btn-add btn-login" style="flex:1;">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập để đặt
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@auth
{{-- ─── MODAL ─── --}}
<div id="orderModal" class="modal">
    <div class="modal-box">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <div class="modal-header">
            <h3 id="modal-dish-name">Tên món</h3>
            <div class="sub" id="modal-dish-type">Đặt giao hàng</div>
        </div>
        <div class="modal-body">
            <div class="qty-row">
                <span class="qty-label">Số lượng:</span>
                <div class="qty-ctrl">
                    <button class="qty-btn" onclick="changeQty(-1)">−</button>
                    <span class="qty-val" id="qty-display">1</span>
                    <button class="qty-btn" onclick="changeQty(1)">+</button>
                </div>
            </div>
            <div class="modal-actions" id="modal-actions">
                {{-- Filled by JS --}}
            </div>
        </div>
    </div>
</div>

<script>
    const CSRF = '{{ csrf_token() }}';
    let dish = { id: '', name: '', price: 0, type: 'mang-ve' };
    let qty = 1;

    const modal = document.getElementById('orderModal');

    function openModal(id, name, price, type) {
        dish = { id, name, price, type };
        qty = 1;
        document.getElementById('qty-display').innerText = 1;
        document.getElementById('modal-dish-name').innerText = name;

        const typeLabel = type === 'mang-ve' ? 'Đặt giao hàng tận nơi' : 'Đặt ăn tại bàn';
        document.getElementById('modal-dish-type').innerText = typeLabel;

        const btnColor   = type === 'mang-ve' ? 'btn-modal-ship' : 'btn-modal-table';
        const btnIcon    = type === 'mang-ve' ? 'fa-motorcycle' : 'fa-chair';
        const btnText    = type === 'mang-ve' ? 'Thêm vào Đặt Ship' : 'Thêm vào Đặt Bàn';
        const redirectUrl = type === 'mang-ve' ? '{{ url("/delivery") }}' : '{{ url("/booking-table") }}';

        document.getElementById('modal-actions').innerHTML = `
            <button class="btn-modal ${btnColor}" onclick="submitOrder('${redirectUrl}')">
                <i class="fas ${btnIcon}"></i> ${btnText}
            </button>
        `;

        modal.style.display = 'block';
    }

    function closeModal() { modal.style.display = 'none'; }

    function changeQty(delta) {
        qty = Math.max(1, Math.min(10, qty + delta));
        document.getElementById('qty-display').innerText = qty;
    }

    // Close on backdrop click
    let _inContent = false;
    modal.addEventListener('mousedown', e => { _inContent = !!e.target.closest('.modal-box'); });
    modal.addEventListener('mouseup', e => {
        if (e.target === modal && !_inContent) closeModal();
        _inContent = false;
    });

    function submitOrder(redirectUrl) {
        if (qty < 1 || qty > 10) {
            hghAlert('Số lượng phải từ 1 đến 10!', 'warning');
            return;
        }

        fetch('{{ route("cart.add") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            credentials: 'same-origin',
            body: JSON.stringify({
                dish_id: dish.id,
                dish_name: dish.name,
                price: dish.price,
                quantity: qty,
                order_type: dish.type
            })
        })
        .then(res => {
            if (!res.ok) return res.json().then(err => { throw err; });
            return res.json();
        })
        .then(() => {
            closeModal();
            hghAlert(`Đã thêm ${qty} × ${dish.name} vào giỏ!`, 'success').then(() => {
                window.location.href = redirectUrl;
            });
        })
        .catch(err => {
            console.error(err);
            hghAlert(err.message || 'Lỗi kết nối server!', 'error');
        });
    }
</script>
@endauth
@endsection