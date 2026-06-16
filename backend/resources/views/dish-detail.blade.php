@extends('layout')

@section('content')
<style>
    .detail-section { 
        max-width: 900px; 
        margin: 40px auto; 
        display: flex; 
        gap: 40px; 
        align-items: flex-start; 
        padding: 0 20px; 
    }

    .detail-left { flex: 1; }
    .detail-left img { 
        width: 100%; 
        border-radius: 15px; 
        box-shadow: 0 8px 20px rgba(0,0,0,0.1); 
        display: block; 
        object-fit: cover; 
        height: 350px; 
    }

    .detail-right { flex: 1.2; text-align: left; }

    .dish-title { 
        color: #C0392B; font-size: 28px; margin-top: 0; margin-bottom: 12px; 
        border-bottom: 3px solid #C0392B; display: inline-block; padding-bottom: 5px;
        font-weight: 800;
    }

    .dish-price { font-size: 22px; font-weight: bold; margin: 15px 0; color: #2C3E50; }
    .dish-desc { 
        font-size: 15px; color: #555; line-height: 1.6; text-align: justify; 
        margin-bottom: 30px; background: #fdfdfd; padding: 12px; 
        border-radius: 10px; border-left: 3px solid #eee; 
    }
    
    .button-group { display: flex; gap: 15px; align-items: center; }
    
    .btn-back { 
        flex: 1; text-align: center; text-decoration: none; background: #f1f1f1; 
        color: #555; padding: 10px; border-radius: 8px; font-weight: bold; font-size: 14px;
        transition: 0.3s; border: 1px solid #ddd;
    }
    .btn-back:hover { background: #e0e0e0; }

    .btn-add { 
        flex: 1.5; background: #C0392B; color: white; border: none; 
        padding: 10px; border-radius: 8px; font-size: 15px; font-weight: bold; 
        cursor: pointer; transition: 0.3s; display: flex; align-items: center; 
        justify-content: center; gap: 8px; text-decoration: none;
    }
    .btn-add:hover { background: #a93226; transform: translateY(-2px); color: white; }

    .pagination-container { 
        display: grid;
        grid-template-columns: repeat(10, 35px);
        gap: 8px;
        justify-content: center;
        margin: 40px auto 60px auto;
        width: fit-content;
    }
    .page-number {
        display: flex; align-items: center; justify-content: center; 
        width: 35px; height: 35px; 
        text-decoration: none; background-color: #fff; color: #C0392B; 
        border: 1px solid #C0392B; border-radius: 6px; 
        font-weight: bold; font-size: 13px; transition: 0.3s;
    }
    .page-number:hover, .page-number.active { background-color: #C0392B; color: #fff; }

    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
    .modal-content { 
        background-color: white; margin: 10% auto; padding: 20px; 
        border-radius: 12px; width: 350px; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.3); position: relative; 
    }
    .close-btn { position: absolute; top: 5px; right: 15px; font-size: 24px; cursor: pointer; color: #aaa; }
    .form-group { margin-bottom: 12px; }
    .form-group label { font-size: 14px; font-weight: bold; margin-bottom: 4px; display: block; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
    .confirm-btn { background: #C0392B; color: white; border: none; width: 100%; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; }
</style>

<div class="detail-section">
    <div class="detail-left">
        <img src="{{ $mon->image_url }}" alt="{{ $mon->dish_name ?? 'Món ăn' }}">
    </div>

    <div class="detail-right">
        <h1 class="dish-title">{{ $mon->dish_name ?? 'Đang tải...' }}</h1>
        <div class="dish-price">{{ number_format($mon->price ?? 0, 0, ',', '.') }} VNĐ</div>
        
        {{-- Xóa bỏ mục mô tả theo yêu cầu --}}

        <div class="button-group">
            <a href="{{ url('/menu') }}" class="btn-back"> &larr; Quay về</a>
            
            @auth
                <button class="btn-add" onclick="openOrderModal('{{ $mon->dish_id }}', '{{ $mon->dish_name }}', {{ $mon->price }})">
                    <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                </button>
            @else
                <a href="{{ route('login') }}" class="btn-add">
                    <i class="fas fa-sign-in-alt"></i> Thêm vào giỏ
                </a>
            @endauth
        </div>
    </div>
</div>

<div class="pagination-container">
    @foreach ($allDishes as $index => $d)
        <a href="{{ url('/menu/' . $d->dish_id) }}" 
           class="page-number {{ $mon->dish_id == $d->dish_id ? 'active' : '' }}"
           title="{{ $d->dish_name }}">
            {{ $index + 1 }}
        </a>
    @endforeach
</div>

@auth
<div id="orderModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3 id="modalDishName" style="color: #C0392B; font-size: 18px; margin-top: 0;"></h3>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 10px 0;">
        
        <form id="orderForm">
            <div class="form-group">
                <label>Số lượng (1-10):</label>
                <input type="number" id="quantity" value="1" min="1" max="10">
            </div>
            <div class="form-group">
                <label>Hình thức nhận món:</label>
                <select id="orderType">
                    <option value="mang-ve">Mang về</option>
                    <option value="dat-ban">Ăn tại quán</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ghi chú:</label>
                <textarea id="note" rows="2" placeholder="Ví dụ: Không hành..."></textarea>
            </div>
            <button type="button" class="confirm-btn" onclick="submitOrder()">XÁC NHẬN THÊM</button>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById("orderModal");
    const qtyInput = document.getElementById("quantity");
    const CSRF_TOKEN = '{{ csrf_token() }}';
    let currentSelectedDish = { id: '', name: '', price: 0 };
    let isClickInside = false;

    function openOrderModal(id, name, price) {
        currentSelectedDish = { id, name, price };
        document.getElementById("modalDishName").innerText = "Đặt: " + name;
        qtyInput.value = 1;
        document.getElementById("note").value = "";
        modal.style.display = "block";
    }

    function closeModal() { modal.style.display = "none"; }

    modal.addEventListener('mousedown', function(e) { isClickInside = e.target.closest('.modal-content'); });
    modal.addEventListener('mouseup', function(e) {
        if (e.target == modal && !isClickInside) closeModal();
        isClickInside = false; 
    });

    qtyInput.addEventListener("input", function() {
        if (this.value > 10) this.value = 10;
        if (this.value < 1 && this.value !== "") this.value = 1;
    });

    function submitOrder() {
        let qty = parseInt(qtyInput.value);
        if (isNaN(qty) || qty < 1 || qty > 10) { 
            hghAlert("Số lượng 1-10!", "warning"); 
            return; 
        }

        fetch('{{ route('cart.add') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({
                dish_id: currentSelectedDish.id,
                dish_name: currentSelectedDish.name,
                price: currentSelectedDish.price,
                quantity: qty,
                order_type: document.getElementById("orderType").value,
                note: document.getElementById("note").value,
                _token: CSRF_TOKEN 
            })
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw err; });
            }
            return res.json();
        })
        .then(() => {
            const orderType = document.getElementById("orderType").value;
            const cartUrl = orderType === 'dat-ban' ? '{{ route('cart.booking') }}' : '{{ route('cart.order') }}';
            hghAlert(`Đã thêm ${qty} ${currentSelectedDish.name}!`, "success").then(() => {
                window.location.href = cartUrl;
            });
            closeModal();
        })
        .catch(err => {
            hghAlert(err.message || "Lỗi kết nối server!", "error");
        });
    }
</script>
@endauth
@endsection