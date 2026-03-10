@extends('layout')

@section('content')
<style>
    /* CSS cho Modal */
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
    .modal-content { background-color: white; margin: 10% auto; padding: 25px; border-radius: 15px; width: 400px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); position: relative; }
    .close-btn { position: absolute; top: 10px; right: 20px; font-size: 28px; cursor: pointer; color: #aaa; }
    .close-btn:hover { color: black; }
    
    .form-group { margin-bottom: 15px; text-align: left; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
    
    .confirm-btn { background: #C0392B; color: white; border: none; width: 100%; padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: 0.3s; font-weight: bold; }
    .confirm-btn:hover { background: #a93226; }

    /* Hiệu ứng hover cho hình ảnh */
    .dish-card img { transition: 0.3s; cursor: pointer; width: 100%; height: 200px; object-fit: cover; border-radius: 10px; }
    .dish-card img:hover { opacity: 0.8; transform: scale(1.02); }
    .dish-card { border: 1px solid #eee; padding: 15px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: 0.3s; }
    .dish-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
</style>

<div style="padding: 50px; max-width: 1200px; margin: 0 auto;">
    <h2 style="text-align: center; color: #C0392B; font-size: 32px; margin-bottom: 40px; font-weight: 800;">THỰC ĐƠN NHÀ HÀNG</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px;">
        @foreach($danhSachMon as $mon)
            <div class="dish-card">
                <a href="{{ url('/menu/' . $mon->dish_id) }}">
                    <img src="{{ asset('pics/' . $mon->image_url) }}" alt="{{ $mon->dish_name }}">
                </a>

                <a href="{{ url('/menu/' . $mon->dish_id) }}" style="text-decoration: none; color: #333;">
                    <p style="font-weight: bold; font-size: 18px; margin: 15px 0 5px 0;">{{ $mon->dish_name }}</p>
                </a>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                    <p style="color: #C0392B; font-weight: bold; font-size: 17px;">{{ number_format($mon->price, 0, ',', '.') }} VNĐ</p>
                    
                    {{-- Truyền thêm giá (price) vào hàm openOrderModal --}}
                    <button onclick="openOrderModal('{{ $mon->dish_id }}', '{{ $mon->dish_name }}', {{ $mon->price }})" 
                            style="background: #C0392B; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold;">
                        + Thêm
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- MODAL --}}
<div id="orderModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3 id="modalDishName" style="color: #C0392B; margin-top: 0;"></h3>
        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">
        
        <form id="orderForm">
            <div class="form-group">
                <label>Số lượng (Tối đa 10):</label>
                <input type="number" id="quantity" value="1" min="1" max="10">
            </div>
            <div class="form-group">
                <label>Hình thức:</label>
                <select id="orderType">
                    <option value="mang-ve">Mang về (Giao hàng tận nơi)</option>
                    <option value="dat-ban">Ăn tại quán (Đặt bàn trước)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ghi chú:</label>
                <textarea id="note" rows="3" placeholder="Ví dụ: Không cay, nhiều hành..."></textarea>
            </div>
            <button type="button" class="confirm-btn" onclick="submitOrder()">Xác nhận thêm vào giỏ hàng</button>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById("orderModal");
    const qtyInput = document.getElementById("quantity");
    const CSRF_TOKEN = '{{ csrf_token() }}';
    
    // Biến lưu trữ món đang chọn
    let currentDish = { id: '', name: '', price: 0 };
    // Biến kiểm soát việc click chuột
    let isMouseDownInside = false;

    function openOrderModal(id, name, price) {
        currentDish = { id, name, price };
        document.getElementById("modalDishName").innerText = "Đặt món: " + name;
        qtyInput.value = 1;
        document.getElementById("note").value = ""; 
        modal.style.display = "block";
    }

    function closeModal() { 
        modal.style.display = "none"; 
    }

    // --- FIX LỖI KÉO CHUỘT RA NGOÀI BỊ THOÁT ---
    modal.addEventListener('mousedown', function(e) {
        // Kiểm tra xem lúc nhấn chuột xuống có nằm trong phần nội dung trắng không
        isMouseDownInside = e.target.closest('.modal-content');
    });

    modal.addEventListener('mouseup', function(e) {
        // Chỉ đóng nếu: nhấn chuột vào vùng xám VÀ thả chuột cũng ở vùng xám
        if (e.target == modal && !isMouseDownInside) {
            closeModal();
        }
        isMouseDownInside = false; 
    });
    // ------------------------------------------

    // Chặn nhập quá 10
    qtyInput.addEventListener("input", function() {
        if (this.value > 10) this.value = 10;
        if (this.value < 1 && this.value !== "") this.value = 1;
    });

    function submitOrder() {
        let qty = parseInt(qtyInput.value);

        if (isNaN(qty) || qty < 1 || qty > 10) {
            alert("Vui lòng nhập số lượng từ 1 đến 10!");
            return;
        }

        fetch('/add-to-cart', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({
                dish_id: currentDish.id,
                dish_name: currentDish.name,
                price: currentDish.price,
                quantity: qty,
                order_type: document.getElementById("orderType").value,
                note: document.getElementById("note").value
            })
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw err; });
            }
            return res.json();
        })
        .then(data => {
            alert(`Thành công! Đã thêm ${qty} ${currentDish.name} vào giỏ hàng.`);
            closeModal();
        })
        .catch(err => {
            console.error(err);
            alert(err.message || "Lỗi kết nối server!");
        });
    }
</script>
@endsection