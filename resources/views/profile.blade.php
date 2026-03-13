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

        .btn-profile:hover {
            background-color: #C0392B !important;
            color: white !important;
            border-color: #C0392B !important;
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
                @if($user->avatar_url)
                    <div class="profile-avatar-box" style="overflow: hidden;">
                        <img src="{{ $user->avatar_url }}" alt="User Avatar"
                            style="width: 100%; height: 100%; object-fit: cover;" referrerpolicy="no-referrer">
                    </div>
                @elseif($user->role === 'admin')
                    <div class="profile-avatar-box" style="background: #f9f9f9; border: 1px solid #ddd; overflow: hidden;">
                        <img src="{{ asset('hgh-apple.png') }}" alt="Admin Avatar"
                            style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                @else
                    <div class="profile-avatar-box">
                        <i class="fas fa-user-shield"></i>
                    </div>
                @endif
                <h3 style="margin: 0 0 5px 0; color: #333;">{{ $user->username }}</h3>
                <p style="color: #C0392B; font-weight: bold; margin: 0; font-size: 14px;">
                    Vai trò: {{ $user->role }}
                </p>

                <div class="avatar-upload-box" id="avatarUploadBox">
                    <label>Đổi ảnh đại diện (Tuỳ chọn)</label>
                    <input type="file" id="profAvatar" accept="image/*">
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

                    <button type="button" class="btn-profile btn-outline" onclick="alert('Chức năng đang phát triển')">
                        <i class="fas fa-key"></i> Đổi mật khẩu
                    </button>

                    <form action="{{ route('logout') }}" method="POST" id="logoutForm">
                        @csrf
                        <button type="submit" class="btn-profile btn-logout-outline">
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

    <script>
        function toggleEditMode(isEditing) {
            const inputs = ['profUsername', 'profEmail', 'profPhone'];
            const btnEdit = document.getElementById('btnEdit');
            const btnSave = document.getElementById('btnSave');
            const profAvatar = document.getElementById('profAvatar');

            inputs.forEach(id => {
                const input = document.getElementById(id);
                if (isEditing) {
                    input.removeAttribute('readonly');
                } else {
                    input.setAttribute('readonly', true);
                }
            });

            const avatarBox = document.getElementById('avatarUploadBox');

            if (isEditing) {
                // Lúc sửa thì không khoá profAvatar
            } else {
                profAvatar.value = ''; // Xoá file đã chọn nếu huỷ
                croppedImageBlob = null; // Xoá blob cắt
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
                toggleEditMode(true);
            }
        });

        function closeCropperModal() {
            document.getElementById('cropperModal').style.display = 'none';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            document.getElementById('profAvatar').value = '';
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
                
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            }, 'image/jpeg');
        }

        async function handleUpdateProfile() {
            const formData = new FormData();
            formData.append('username', document.getElementById('profUsername').value);
            formData.append('email', document.getElementById('profEmail').value);
            formData.append('phone', document.getElementById('profPhone').value);

            const fileInput = document.getElementById('profAvatar');
            if (croppedImageBlob) {
                formData.append('avatar', croppedImageBlob, 'avatar.jpg');
            } else if (fileInput.files.length > 0) {
                formData.append('avatar', fileInput.files[0]);
            }

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
    </script>
@endsection