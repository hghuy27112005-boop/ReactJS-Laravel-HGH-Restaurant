@extends('layout')

@section('content')
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
        box-shadow: 0 5px 20px rgba(0,0,0,0.05); 
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

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #333; }
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

    .btn-outline { background: white; color: #555; border: 1px solid #ddd; }
    .btn-logout-outline { background: white; color: #C0392B; border: 1px solid #C0392B; }
    .btn-save-red { background: #C0392B; color: white; border: 1px solid #C0392B; }

    .btn-profile:hover {
        background-color: #C0392B !important;
        color: white !important;
        border-color: #C0392B !important;
    }
</style>

<div class="profile-main-wrapper">
    <div class="profile-container">
        <div class="profile-sidebar">
            <div class="profile-avatar-box">
                <i class="fas fa-user-shield"></i>
            </div>
            <h3 style="margin: 0 0 5px 0; color: #333;">{{ $user->username }}</h3>
            <p style="color: #C0392B; font-weight: bold; margin: 0; font-size: 14px;">
                Vai trò: {{ $user->role }}
            </p>
        </div>

        <div class="profile-content">
            <h2 style="margin: 0 0 25px 0; color: #333; font-size: 24px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
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

                <button type="button" id="btnSave" class="btn-profile btn-save-red" style="display: none;" onclick="handleUpdateProfile()">
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

<script>
function toggleEditMode(isEditing) {
    const inputs = ['profUsername', 'profEmail', 'profPhone'];
    const btnEdit = document.getElementById('btnEdit');
    const btnSave = document.getElementById('btnSave');

    inputs.forEach(id => {
        const input = document.getElementById(id);
        if (isEditing) {
            input.removeAttribute('readonly');
        } else {
            input.setAttribute('readonly', true);
        }
    });

    btnEdit.style.display = isEditing ? 'none' : 'flex';
    btnSave.style.display = isEditing ? 'flex' : 'none';
}

async function handleUpdateProfile() {
    const data = {
        username: document.getElementById('profUsername').value,
        email: document.getElementById('profEmail').value,
        phone: document.getElementById('profPhone').value,
    };

    try {
        const response = await fetch("{{ route('profile.update') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        
        if (response.ok && result.success) {
            alert(result.message);
            toggleEditMode(false);
            document.querySelector('.profile-sidebar h3').innerText = data.username;
        } else {
            alert(result.message || 'Cập nhật thất bại');
        }
    } catch (error) {
        alert('Không thể kết nối máy chủ!');
    }
}
</script>
@endsection