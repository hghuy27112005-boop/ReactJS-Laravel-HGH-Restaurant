@extends('layout')

@section('content')
<style>
    /* 1. LAYOUT TỔNG THỂ: Thu hẹp nội dung chính để tạo khoảng trắng bên ngoài */
    .detail-section { 
        max-width: 900px; /* Ép nội dung nhỏ lại (trước là 1100px) */
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
        height: 350px; /* Thu nhỏ chiều cao hình */
    }

    .detail-right { flex: 1.2; text-align: left; }

    /* 2. CHI TIẾT CHỮ: Giảm size cho cân đối với khung nhỏ */
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
        justify-content: center; gap: 8px;
    }
    .btn-add:hover { background: #a93226; transform: translateY(-2px); }

    /* 3. PHÂN TRANG: Fix lỗi nhảy dòng, thu nhỏ các nút */
    .pagination-container { 
        display: flex; 
        justify-content: center; 
        gap: 8px; 
        margin: 30px 0 60px 0; 
        flex-wrap: nowrap; /* Tuyệt đối không cho xuống dòng */
    }
    .page-number {
        display: flex; align-items: center; justify-content: center; 
        width: 35px; height: 35px; /* Thu nhỏ nút từ 42px xuống 35px */
        text-decoration: none; background-color: #fff; color: #C0392B; 
        border: 1px solid #C0392B; border-radius: 6px; 
        font-weight: bold; font-size: 13px; transition: 0.3s;
    }
    .page-number:hover, .page-number.active { background-color: #C0392B; color: #fff; }

    /* 4. MODAL: Thu gọn lại cho đẹp */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
    .modal-content { 
        background-color: white; margin: 10% auto; padding: 20px; 
        border-radius: 12px; width: 350px; /* Thu nhỏ modal ngang */
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
        <img src="{{ asset('pics/' . ($mon->image_url ?? 'default.jpg')) }}" alt="{{ $mon->dish_name ?? 'Món ăn' }}">
    </div>

    <div class="detail-right">
        <h1 class="dish-title">{{ $mon->dish_name ?? 'Đang tải...' }}</h1>
        <div class="dish-price">{{ number_format($mon->price ?? 0, 0, ',', '.') }} VNĐ</div>
        
        <div class="dish-desc">
            <strong>Mô tả:</strong><br>
            {{ $mon->description ?? 'Hiện chưa có mô tả chi tiết cho món ăn này.' }}
        </div>

        <div class="button-group">
            <a href="{{ url('/menu') }}" class="btn-back"> &larr; Quay về</a>
            <button class="btn-add" onclick="openOrderModal('{{ $mon->dish_id }}', '{{ $mon->dish_name }}', {{ $mon->price }})">
                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
            </button>
        </div>
    </div>
</div>

<div class="pagination-container">
    @for ($i = 1; $i <= 12; $i++)
        <a href="{{ url('/menu/' . $i) }}" class="page-number {{ (isset($mon) && $mon->dish_id == $i) ? 'active' : '' }}">
            {{ $i }}
        </a>
    @endfor
</div>

{{-- MODAL --}}
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
        if (isNaN(qty) || qty < 1 || qty > 10) { alert("Số lượng 1-10!"); return; }

        fetch('/add-to-cart', {
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
        .then(res => res.json())
        .then(() => {
            alert(`Đã thêm ${qty} ${currentSelectedDish.name}!`);
            closeModal();
        });
    }
</script>
@endsection