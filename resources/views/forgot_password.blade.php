<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - NHÀ HÀNG HGH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f8f8; }
        .forgot-page { display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .forgot-box { width: 100%; max-width: 450px; background-color: white; border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .header { background-color: #C0392B; color: white; padding: 20px; text-align: center; }
        .header h2 { margin: 0; font-size: 20px; }
        .content { padding: 30px; }
        label { display: block; margin-top: 15px; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        input { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; box-sizing: border-box; font-size: 16px; margin-bottom: 5px; }
        input:focus { border-color: #C0392B; outline: none; }
        .btn-submit { width: 100%; padding: 12px; background-color: #C0392B; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 17px; font-weight: 700; margin-top: 25px; transition: 0.3s; }
        .btn-submit:hover { background-color: #A93226; }
        .btn-back { width: 100%; padding: 10px; background: none; border: 1px solid #ccc; color: #666; border-radius: 5px; cursor: pointer; font-size: 15px; margin-top: 15px; transition: 0.3s; }
        .btn-back:hover { background-color: #eee; }
        .step { display: none; animation: fadeIn 0.4s; }
        .step.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .info-msg { padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .info-msg.error { background-color: #ffeaea; color: #c0392b; border: 1px solid #ffcccc; }
        .info-msg.success { background-color: #eaffea; color: #27ae60; border: 1px solid #ccffcc; }
    </style>
</head>
<body>
    <div class="forgot-page">
        <div class="forgot-box">
            <div class="header">
                <h2><i class="fas fa-lock-open"></i> KHÔI PHỤC MẬT KHẨU</h2>
            </div>
            
            <div class="content">
                <div id="msg" class="info-msg" style="display:none;"></div>

                {{-- Bước 1: Xác thực thông tin --}}
                <div id="step-1" class="step active">
                    <p style="color: #666; font-size: 14px; margin-bottom: 20px;">Vui lòng nhập chính xác thông tin bạn đã đăng ký để đặt lại mật khẩu.</p>
                    
                    <label>Tên người dùng (*):</label>
                    <input type="text" id="username" placeholder="Nhập tên người dùng">

                    <label>Email (*):</label>
                    <input type="email" id="email" placeholder="Nhập email của bạn (@gmail.com)">

                    <label>Số điện thoại (*):</label>
                    <input type="tel" id="phone" placeholder="Nhập số điện thoại (10 số)">

                    <button class="btn-submit" onclick="verifyInfo()">Tiếp theo</button>
                    <button class="btn-back" onclick="location.href='{{ route('login') }}'">Quay lại đăng nhập</button>
                </div>

                {{-- Bước 2: Nhập mật khẩu mới --}}
                <div id="step-2" class="step">
                    <p style="color: #666; font-size: 14px; margin-bottom: 20px;">Xác thực thành công! Bây giờ bạn có thể nhập mật khẩu mới.</p>
                    <input type="hidden" id="verified_userId">

                    <label>Mật khẩu mới (*):</label>
                    <input type="password" id="new_password" placeholder="Tối thiểu 6 ký tự">

                    <label>Nhập lại mật khẩu mới (*):</label>
                    <input type="password" id="confirm_password" placeholder="Xác nhận lại mật khẩu mới">

                    <button class="btn-submit" onclick="resetPassword()">Đổi mật khẩu</button>
                    <button class="btn-back" onclick="goToStep(1)">Quay lại bước 1</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function hghAlert(message, icon = 'info') {
            return Swal.fire({
                title: 'NHÀ HÀNG HGH',
                text: message,
                icon: icon,
                confirmButtonColor: '#C0392B',
                confirmButtonText: 'Đồng ý'
            });
        }

        function showMsg(text, type = 'error') {
            const msg = document.getElementById('msg');
            msg.innerText = text;
            msg.className = 'info-msg ' + type;
            msg.style.display = 'block';
        }

        function goToStep(n) {
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
            document.getElementById('step-' + n).classList.add('active');
            document.getElementById('msg').style.display = 'none';
        }

        async function verifyInfo() {
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;

            if(!username || !email || !phone) {
                showMsg('Vui lòng nhập đầy đủ thông tin!');
                return;
            }

            try {
                const res = await fetch("{{ route('password.verify') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ username, email, phone })
                });

                const data = await res.json();
                if(res.ok && data.success) {
                    document.getElementById('verified_userId').value = data.user_id;
                    goToStep(2);
                } else {
                    showMsg(data.message || 'Thông tin không chính xác!');
                }
            } catch (err) {
                showMsg('Lỗi kết nối máy chủ!');
            }
        }

        async function resetPassword() {
            const userId = document.getElementById('verified_userId').value;
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;

            if(password.length < 6) {
                showMsg('Mật khẩu mới phải có ít nhất 6 ký tự!');
                return;
            }

            if(password !== confirm) {
                showMsg('Mật khẩu xác nhận không khớp!');
                return;
            }

            try {
                const res = await fetch("{{ route('password.update') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ user_id: userId, password })
                });

                const data = await res.json();
                if(res.ok && data.success) {
                    hghAlert('Đổi mật khẩu thành công!', 'success').then(() => {
                        window.location.href = "{{ route('login') }}";
                    });
                } else {
                    showMsg(data.message || 'Lỗi xử lý!');
                }
            } catch (err) {
                showMsg('Lỗi kết nối máy chủ!');
            }
        }
    </script>
</body>
</html>
