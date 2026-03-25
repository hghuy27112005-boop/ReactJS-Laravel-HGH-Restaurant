@extends('layout')

@section('content')
    <style>
        .menu-mgmt-wrap {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            color: #333;
        }

        /* --- Header Section --- */
        .mgmt-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            background: linear-gradient(135deg, #fff 0%, #fdfdfd 100%);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }

        .mgmt-title {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .mgmt-title h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            color: #C0392B;
            letter-spacing: -0.5px;
        }

        .mgmt-title p {
            margin: 0;
            font-size: 14px;
            color: #777;
        }

        .btn-add-new {
            background: #C0392B;
            color: #fff;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(192, 57, 43, 0.2);
        }

        .btn-add-new:hover {
            background: #A93226;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(192, 57, 43, 0.3);
        }

        /* --- Table Styling --- */
        .table-container {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 25px rgba(0,0,0,0.04);
            border: 1px solid #eee;
        }

        .premium-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .premium-table thead {
            background: #f8f9fa;
        }

        .premium-table th {
            padding: 18px 24px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            font-weight: 700;
            border-bottom: 2px solid #eee;
        }

        .premium-table td {
            padding: 16px 24px;
            border-bottom: 1px solid #f1f1f1;
            font-size: 15px;
            vertical-align: middle;
        }

        .col-name {
            font-weight: 700;
            color: #2C3E50;
        }

        .premium-table tr:last-child td {
            border-bottom: none;
        }

        .premium-table tr:hover {
            background-color: #fdfdfd;
        }

        .col-stt { width: 80px; text-align: center; color: #aaa; font-weight: 600; }
        .col-price { font-weight: 700; color: #C0392B; }
        .col-type { color: #555; font-size: 13px; }

        .badge-best {
            background: #FFF9E6;
            color: #D4A017;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            margin-left: 8px;
            border: 1px solid #FFEBB3;
            text-transform: uppercase;
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            font-size: 16px;
        }

        .btn-edit { background: #EBF5FF; color: #007AFF; }
        .btn-edit:hover { background: #007AFF; color: #fff; }

        .btn-delete { background: #FFF0F0; color: #FF3B30; }
        .btn-delete:hover { background: #FF3B30; color: #fff; }

        /* --- Modal Redesign --- */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .modal-content {
            background: #fff;
            width: 550px;
            border-radius: 24px;
            padding: 25px 35px; /* Reduced vertical padding */
            position: relative;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .modal-header {
            text-align: center;
            margin-bottom: 15px; /* Reduced margin */
        }

        .modal-header h2 {
            margin: 0;
            color: #C0392B;
            font-size: 24px;
            font-weight: 800;
        }

        .modal-header p {
            margin: 5px 0 0;
            color: #888;
            font-size: 14px;
        }

        .form-group { margin-bottom: 14px; } /* Reduced from 24px */
        .form-group label {
            display: block;
            font-weight: 700;
            margin-bottom: 5px; /* Reduced from 10px */
            font-size: 14px;
            color: #2C3E50;
        }

        .form-group input[type="text"],
        .form-group select,
        .price-display {
            width: 100%;
            box-sizing: border-box;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .form-group input[type="text"],
        .form-group select {
            padding: 10px 18px; /* Reduced vertical padding */
            font-size: 15px;
            background: #fdfdfd;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: #C0392B;
            background: #fff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(192, 57, 43, 0.05);
        }

        .price-display {
            background: #f8f9fa;
            padding: 10px 18px; /* Reduced vertical padding */
            font-weight: 800;
            color: #C0392B;
            font-size: 16px;
        }

        .upload-area {
            border: 2px dashed #eee;
            padding: 12px; /* Reduced padding */
            border-radius: 16px;
            text-align: center;
            background: #fcfcfc;
            transition: 0.3s;
            width: fit-content;
            margin: 0 auto;
            min-width: 250px;
        }

        .upload-area:hover { border-color: #C0392B; background: #fff; }

        .current-img-preview {
            width: 100px; /* Reduced from 120px */
            height: 100px;
            object-fit: cover;
            border-radius: 16px;
            margin: 0 auto 10px;
            display: block;
            border: 3px solid #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .modal-footer {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .btn-modal {
            flex: 1;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            border: none;
            font-size: 15px;
        }

        .btn-modal-cancel { background: #f1f1f1; color: #666; }
        .btn-modal-cancel:hover { background: #e5e5e5; }
        .btn-modal-confirm { background: #C0392B; color: #fff; }
        .btn-modal-confirm:hover { background: #A93226; }

        /* Custom Checkbox */
        .check-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px; /* Reduced from 15px */
            background: #FAFBFF;
            border-radius: 12px;
            border: 1px solid #EBF0FF;
            cursor: pointer;
        }
        .check-group input { width: 20px; height: 20px; cursor: pointer; accent-color: #C0392B; }

        @media (max-width: 768px) {
            .mgmt-header { flex-direction: column; text-align: center; gap: 20px; }
            .modal-content { width: 90%; padding: 30px; }
        }
    </style>

    <div class="menu-mgmt-wrap">
        <div class="mgmt-header">
            <div class="mgmt-title">
                <h1>Quản lý thực đơn</h1>
            </div>
            <button class="btn-add-new" id="btnOpenModal">
                <i class="fas fa-plus-circle"></i>
                Thêm Món Ăn Mới
            </button>
        </div>

        <!-- Hidden Modal: ADD DISH -->
        <div class="modal-overlay" id="addDishModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Thêm Món Ăn</h2>
                    <p>Vui lòng điền đầy đủ các thông tin bên dưới</p>
                </div>
                
                <form id="formAddDish" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Tên món ăn</label>
                        <input type="text" name="dish_name" placeholder="Nhập tên món ăn ..." required>
                    </div>

                    <div class="form-group">
                        <label>Giá niêm yết</label>
                        <div class="price-display">30.000 VNĐ</div>
                        <input type="hidden" name="price" value="30000">
                    </div>

                    <div class="form-group">
                        <label>Phân loại món</label>
                        <select name="type_id" required>
                            <option value="" disabled selected>Chọn loại món ăn...</option>
                            @foreach($dishTypes as $type)
                                <option value="{{ $type->type_id }}">{{ $type->type_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Hình ảnh minh họa</label>
                        <div class="upload-area">
                            <input type="file" name="image" accept="image/*" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-modal btn-modal-cancel" id="btnCancel">Hủy bỏ</button>
                        <button type="submit" class="btn-modal btn-modal-confirm">Xác nhận thêm</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Hidden Modal: EDIT DISH -->
        <div class="modal-overlay" id="editDishModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Chỉnh Sửa Món</h2>
                    <p>Cập nhật lại thông tin cho món ăn đã chọn</p>
                </div>
                
                <form id="formEditDish" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="dish_id" id="edit_dish_id">
                    
                    <div class="form-group">
                        <label>Tên món ăn</label>
                        <input type="text" name="dish_name" id="edit_dish_name" required>
                    </div>

                    <div class="form-group">
                        <label>Giá tiền</label>
                        <div class="price-display">30.000 VNĐ</div>
                    </div>

                    <div class="form-group">
                        <label>Phân loại món</label>
                        <select name="type_id" id="edit_type_id" required>
                            @foreach($dishTypes as $type)
                                <option value="{{ $type->type_id }}">{{ $type->type_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Hình ảnh hiện tại</label>
                        <img id="edit_img_preview" src="" class="current-img-preview">
                        <div class="upload-area">
                            <input type="file" name="image" accept="image/*">
                            <p style="font-size: 12px; color: #999; margin: 5px 0 0;">Chọn ảnh mới nếu muốn thay đổi</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="check-group">
                            <input type="checkbox" name="is_bestseller" id="edit_is_bestseller" value="1">
                            <span>Đánh dấu là món nổi bật (Bestseller)</span>
                        </label>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-modal btn-modal-cancel" onclick="closeEditModal()">Hủy bỏ</button>
                        <button type="submit" class="btn-modal btn-modal-confirm">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-container">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th class="col-stt">STT</th>
                        <th>Tên Món Ăn</th>
                        <th>Loại</th>
                        <th>Giá Bán</th>
                        <th style="text-align: right;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dishes as $index => $dish)
                        <tr>
                            <td class="col-stt">{{ $index + 1 }}</td>
                            <td class="col-name">
                                {{ $dish->dish_name }}
                                @if($dish->is_bestseller)
                                    <span class="badge-best">Bestseller</span>
                                @endif
                            </td>
                            <td class="col-type">
                                {{ $dish->type->type_name ?? 'N/A' }}
                            </td>
                            <td class="col-price">{{ number_format($dish->price, 0, ',', '.') }}đ</td>
                            <td style="text-align: right;">
                                <div class="action-btns" style="justify-content: flex-end;">
                                    <button class="btn-action btn-edit" title="Chỉnh sửa" onclick="openEditModal({{ json_encode($dish) }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" title="Xóa món" onclick="deleteDish({{ $dish->dish_id }}, '{{ $dish->dish_name }}')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addModal = document.getElementById('addDishModal');
            const editModal = document.getElementById('editDishModal');
            const btnOpenAdd = document.getElementById('btnOpenModal');
            const btnCancelAdd = document.getElementById('btnCancel');
            const formAdd = document.getElementById('formAddDish');
            const formEdit = document.getElementById('formEditDish');

            // --- ADD DISH LOGIC ---
            btnOpenAdd.onclick = () => addModal.style.display = 'flex';
            
            const closeAddModal = () => {
                addModal.style.display = 'none';
                formAdd.reset();
            };

            btnCancelAdd.onclick = closeAddModal;

            formAdd.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch("{{ route('admin.add_dish') }}", {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        hghAlert(data.message, 'success').then(() => location.reload());
                    } else {
                        hghAlert('Lỗi: ' + data.message, 'error');
                    }
                });
            };

            // --- EDIT DISH LOGIC ---
            window.openEditModal = (dish) => {
                document.getElementById('edit_dish_id').value = dish.dish_id;
                document.getElementById('edit_dish_name').value = dish.dish_name;
                document.getElementById('edit_type_id').value = dish.type_id;
                document.getElementById('edit_is_bestseller').checked = !!dish.is_bestseller;
                
                const previewImg = document.getElementById('edit_img_preview');
                let imgUrl = dish.image_url;
                if (imgUrl && !imgUrl.startsWith('http')) {
                    imgUrl = "{{ asset('dishes') }}/" + imgUrl;
                }
                previewImg.src = imgUrl || "{{ asset('pics/default_avt.jpg') }}";
                
                editModal.style.display = 'flex';
            };

            window.closeEditModal = () => {
                editModal.style.display = 'none';
                formEdit.reset();
            };

            formEdit.onsubmit = function(e) {
                e.preventDefault();
                const id = document.getElementById('edit_dish_id').value;
                const formData = new FormData(this);
                
                fetch(`/admin/edit-dish/${id}`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        hghAlert(data.message, 'success').then(() => location.reload());
                    } else {
                        hghAlert('Lỗi: ' + data.message, 'error');
                    }
                });
            };

            // --- DELETE DISH LOGIC ---
            window.deleteDish = (id, name) => {
                hghConfirm(`Bạn có chắc chắn muốn xóa món "${name}" không? Hành động này không thể hoàn tác!`).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/admin/delete-dish/${id}`, {
                            method: 'POST',
                            headers: { 
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                hghAlert(data.message, 'success').then(() => location.reload());
                            } else {
                                hghAlert('Lỗi: ' + data.message, 'error');
                            }
                        });
                    }
                });
            };

            // --- PROTECTION AGAINST ACCIDENTAL CLOSE ---
            // Only close if BOTH mousedown and mouseup are on the overlay itself
            let isClickingOverlay = false;

            window.onmousedown = (e) => {
                isClickingOverlay = (e.target === addModal || e.target === editModal);
            };

            window.onmouseup = (e) => {
                if (isClickingOverlay && (e.target === addModal || e.target === editModal)) {
                    if (e.target === addModal) closeAddModal();
                    if (e.target === editModal) closeEditModal();
                }
                isClickingOverlay = false;
            };
        });
    </script>
@endsection