@extends('layout')

@section('content')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <style>
        .profile-main-wrapper {
            min-height: 80vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 50px 20px;
            background-color: #f8f8f8;
        }

        .profile-container {
            display: flex;
            width: 100%;
            max-width: 900px;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .profile-sidebar {
            width: 30%;
            text-align: center;
            border-right: 1px solid #eee;
            padding-right: 20px;
        }

        .profile-content {
            width: 70%;
            padding-left: 40px;
        }

        .profile-avatar-box {
            width: 120px;
            height: 120px;
            background: #C0392B;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 50px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
            background-color: #f9f9f9;
            transition: all 0.3s;
        }

        .form-group input:not([readonly]) {
            background-color: #fff;
            border-color: #C0392B;
        }

        .action-group {
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-profile {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-outline {
            background: white;
            color: #555;
            border: 1px solid #ddd;
        }

        .btn-logout-outline {
            background: white;
            color: #C0392B;
            border: 1px solid #C0392B;
        }

        .btn-save-red {
            background: #C0392B;
            color: white;
            border: 1px solid #C0392B;
        }

        .btn-profile:hover:not([disabled]) {
            background-color: #C0392B !important;
            color: white !important;
            border-color: #C0392B !important;
        }

        .btn-profile:disabled, .btn-profile[disabled] {
            opacity: 0.5;
            cursor: not-allowed !important;
        }

        .avatar-upload-box input[type="file"]:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .avatar-upload-box {
            margin-top: 15px;
            text-align: left;
        }

        .avatar-upload-box label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }

        .avatar-upload-box input[type="file"] {
            width: 100%;
            font-size: 12px;
            padding: 6px;
            border: 1px dashed #ccc;
            border-radius: 4px;
            background: #fafafa;
            cursor: pointer;
            box-sizing: border-box;
        }
    </style>

    <div class="profile-main-wrapper">
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-avatar-box" style="overflow: hidden; background: #f9f9f9; border: 1px solid #ddd;">
                    <img src="{{ $user->avatar_url }}" alt="User Avatar"
                        style="width: 100%; height: 100%; object-fit: {{ $user->role === 'admin' && !$user->getRawOriginal('avatar_url') ? 'contain' : 'cover' }};" 
                        referrerpolicy="no-referrer">
                </div>
                <h3 style="margin: 0 0 5px 0; color: #333;">{{ $user->username }}</h3>
                <p style="color: #C0392B; font-weight: bold; margin: 0; font-size: 14px;">
                    Vai trò: {{ $user->role }}
                </p>

                <div class="avatar-upload-box" id="avatarUploadBox">
                    <label>Đổi ảnh đại diện (Tuỳ chọn)</label>
                    <input type="file" id="profAvatar" accept="image/*">
                    <button type="button" id="btnSaveAvatar" class="btn-profile btn-save-red" style="display: none; margin-top: 10px; padding: 6px; font-size: 13px;" onclick="handleUpdateAvatar()">Lưu ảnh đại diện</button>
                </div>
            </div>

            <div class="profile-content">
                <h2
                    style="margin: 0 0 25px 0; color: #333; font-size: 24px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
                    Thông tin cá nhân
                </h2>

                <form id="profileForm">
                    <div class="form-group">
                        <label>Tên người dùng (*):</label>
                        <input type="text" id="profUsername" value="{{ $user->username }}" readonly maxlength="20">
                    </div>

                    <div class="form-group">
                        <label>Email (*):</label>
                        <input type="email" id="profEmail" value="{{ $user->email }}" readonly maxlength="50">
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại:</label>
                        <input type="text" id="profPhone" value="{{ $user->phone }}" readonly maxlength="10">
                    </div>
                </form>

                <div class="action-group">
                    <button type="button" id="btnEdit" class="btn-profile btn-outline" onclick="toggleEditMode(true)">
                        <i class="fas fa-edit"></i> Thay đổi thông tin cá nhân
                    </button>

                    <button type="button" id="btnSave" class="btn-profile btn-save-red" style="display: none;"
                        onclick="handleUpdateProfile()">
                        <i class="fas fa-save"></i> Lưu sửa đổi
                    </button>

                    <button type="button" id="btnChangePassword" class="btn-profile btn-outline" onclick="openChangePasswordModal()">
                        <i class="fas fa-key"></i> Đổi mật khẩu
                    </button>

                    <form action="{{ route('logout') }}" method="POST" id="logoutForm">
                        @csrf
                        <button type="submit" id="btnLogout" class="btn-profile btn-logout-outline">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal chọn vùng cắt ảnh -->
    <div id="cropperModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px;">
            <h3 style="margin-top: 0; margin-bottom: 20px; text-align: center; color: #333;">Cắt ảnh đại diện</h3>
            <div style="max-height: 400px; width: 100%; overflow: hidden; margin-bottom: 20px;">
                <img id="cropperImage" src="" style="max-width: 100%; display: block;">
            </div>
            <div style="text-align: right; gap: 10px; display: flex; justify-content: flex-end;">
                <button type="button" class="btn-profile btn-outline" style="width: auto; padding: 8px 16px;" onclick="closeCropperModal()">Huỷ</button>
                <button type="button" class="btn-profile btn-save-red" style="width: auto; padding: 8px 16px;" onclick="cropImage()">Xác nhận xong</button>
            </div>
        </div>
    </div>

    <!-- Modal đổi mật khẩu -->
    <div id="changePasswordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 25px; border-radius: 8px; width: 90%; max-width: 400px;">
            <h3 style="margin-top: 0; margin-bottom: 20px; text-align: center; color: #333;">Đổi mật khẩu</h3>
            <form id="changePasswordForm" onsubmit="event.preventDefault(); submitChangePassword();">
                <div class="form-group">
                    <label>Mật khẩu mới (*):</label>
                    <input type="password" id="newPassword" required minlength="6" placeholder="Nhập mật khẩu mới">
                </div>
                <div class="form-group">
                    <label>Xác nhận mật khẩu mới (*):</label>
                    <input type="password" id="confirmNewPassword" required minlength="6" placeholder="Nhập lại mật khẩu mới">
                </div>
                <div style="text-align: right; gap: 10px; display: flex; justify-content: flex-end; margin-top: 25px;">
                    <button type="button" class="btn-profile btn-outline" style="width: auto; padding: 10px 20px;" onclick="closeChangePasswordModal()">Huỷ</button>
                    <button type="submit" class="btn-profile btn-save-red" style="width: auto; padding: 10px 20px;">Xác nhận</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleEditMode(isEditing) {
            const inputs = ['profUsername', 'profEmail', 'profPhone'];
            const btnEdit = document.getElementById('btnEdit');
            const btnSave = document.getElementById('btnSave');
            const profAvatar = document.getElementById('profAvatar');
            const btnChangePassword = document.getElementById('btnChangePassword');
            const btnLogout = document.getElementById('btnLogout');

            inputs.forEach(id => {
                const input = document.getElementById(id);
                if (isEditing) {
                    input.removeAttribute('readonly');
                } else {
                    input.setAttribute('readonly', true);
                }
            });

            if (isEditing) {
                // Khóa 3 nút kia lại
                profAvatar.setAttribute('disabled', 'true');
                btnChangePassword.setAttribute('disabled', 'true');
                btnLogout.setAttribute('disabled', 'true');
            } else {
                profAvatar.removeAttribute('disabled');
                profAvatar.value = ''; // Xoá file đã chọn nếu huỷ
                croppedImageBlob = null; // Xoá blob cắt
                document.getElementById('btnSaveAvatar').style.display = 'none';

                btnChangePassword.removeAttribute('disabled');
                btnLogout.removeAttribute('disabled');
            }

            btnEdit.style.display = isEditing ? 'none' : 'flex';
            btnSave.style.display = isEditing ? 'flex' : 'none';
        }

        let cropper;
        let croppedImageBlob = null;

        document.getElementById('profAvatar').addEventListener('change', function (e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                const reader = new FileReader();

                reader.onload = function (event) {
                    document.getElementById('cropperImage').src = event.target.result;
                    document.getElementById('cropperModal').style.display = 'flex';

                    if (cropper) {
                        cropper.destroy();
                    }
                    cropper = new Cropper(document.getElementById('cropperImage'), {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        function closeCropperModal() {
            document.getElementById('cropperModal').style.display = 'none';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            document.getElementById('profAvatar').value = '';

            // Mở khóa lại nếu hủy cắt ảnh
            document.getElementById('btnEdit').removeAttribute('disabled');
            document.getElementById('btnChangePassword').removeAttribute('disabled');
            document.getElementById('btnLogout').removeAttribute('disabled');
        }

        function cropImage() {
            if (!cropper) return;

            cropper.getCroppedCanvas({
                width: 300,
                height: 300,
            }).toBlob((blob) => {
                croppedImageBlob = blob;
                document.getElementById('cropperModal').style.display = 'none';

                // Thay đổi ảnh đại diện tạm thời trên giao diện
                const avatarBox = document.querySelector('.profile-avatar-box');
                const url = URL.createObjectURL(blob);
                avatarBox.innerHTML = `<img src="${url}" alt="User Avatar" style="width: 100%; height: 100%; object-fit: cover;" referrerpolicy="no-referrer">`;
                
                // Hiện nút lưu riêng cho ảnh đại diện
                document.getElementById('btnSaveAvatar').style.display = 'block';

                // KHÓA các nút còn lại
                document.getElementById('btnEdit').setAttribute('disabled', 'true');
                document.getElementById('btnChangePassword').setAttribute('disabled', 'true');
                document.getElementById('btnLogout').setAttribute('disabled', 'true');

                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            }, 'image/jpeg');
        }

        async function handleUpdateAvatar() {
            if (!croppedImageBlob) {
                alert('Vui lòng chọn và cắt ảnh trước!');
                return;
            }

            const formData = new FormData();
            formData.append('avatar', croppedImageBlob, 'avatar.jpg');

            try {
                const response = await fetch("{{ route('profile.update_avatar') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    alert(result.message);
                    window.location.reload(); 
                } else {
                    alert(result.message || 'Cập nhật thất bại');
                }
            } catch (error) {
                alert('Không thể kết nối máy chủ!');
            }
        }

        async function handleUpdateProfile() {
            const formData = new FormData();
            formData.append('username', document.getElementById('profUsername').value);
            formData.append('email', document.getElementById('profEmail').value);
            formData.append('phone', document.getElementById('profPhone').value);

            try {
                const response = await fetch("{{ route('profile.update') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    alert(result.message);
                    toggleEditMode(false);
                    if (result.avatar_url) {
                        window.location.reload(); // Load lại trang để đổi ảnh
                    } else {
                        document.querySelector('.profile-sidebar h3').innerText = formData.get('username');
                    }
                } else {
                    alert(result.message || 'Cập nhật thất bại');
                }
            } catch (error) {
                alert('Không thể kết nối máy chủ!');
            }
        }

        function openChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'flex';
        }

        function closeChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'none';
            document.getElementById('changePasswordForm').reset();
        }

        async function submitChangePassword() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmNewPassword = document.getElementById('confirmNewPassword').value;

            if (newPassword !== confirmNewPassword) {
                alert('Mật khẩu xác nhận không khớp!');
                return;
            }

            try {
                const response = await fetch("{{ route('profile.change_password') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        new_password: newPassword,
                        new_password_confirmation: confirmNewPassword
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    alert(result.message);
                    closeChangePasswordModal();
                } else {
                    let errorMsg = result.message || 'Đổi mật khẩu thất bại';
                    if (result.errors) {
                        errorMsg += ": " + Object.values(result.errors).flat().join(', ');
                    }
                    alert(errorMsg);
                }
            } catch (error) {
                alert('Không thể kết nối máy chủ!');
            }
        }
    </script>
@endsection