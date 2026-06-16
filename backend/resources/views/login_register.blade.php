@extends('layout')

@section('content')
    <style>
        .container.auth-page {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 80vh;
            padding: 50px 20px;
            background-color: #f8f8f8;
        }

        .auth-box {
            width: 100%;
            max-width: 450px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .tab-buttons {
            display: flex;
            border-bottom: 5px solid #eee;
        }

        .tab-btn {
            flex-grow: 1;
            padding: 15px 0;
            background: none;
            border: none;
            font-size: 18px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: color 0.3s, border-bottom 0.3s;
        }

        .tab-btn.active {
            color: #C0392B;
            border-bottom: 5px solid #C0392B;
        }

        .tab-content {
            padding: 30px;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .tab-content label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 14px;
        }

        .tab-content input[type="text"],
        .tab-content input[type="email"],
        .tab-content input[type="tel"],
        .tab-content input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }

        .tab-content input:focus {
            border-color: #C0392B;
            outline: none;
        }

        .btn-primary.large-btn {
            width: 100%;
            padding: 12px;
            background-color: #C0392B;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 17px;
            font-weight: 700;
            margin-top: 25px;
            transition: background-color 0.3s;
        }

        .btn-primary.large-btn:hover {
            background-color: #A93226;
        }

        .forgot-password {
            background: none;
            border: none;
            color: #C0392B;
            float: right;
            margin-top: 10px;
            font-size: 14px;
            cursor: pointer;
        }

        .social-login-divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
            color: #999;
            font-size: 14px;
        }

        .social-login-divider::before,
        .social-login-divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 35%;
            height: 1px;
            background: #eee;
        }

        .social-login-divider::before {
            left: 0;
        }

        .social-login-divider::after {
            right: 0;
        }

        .social-login-buttons {
            display: flex;
            gap: 10px;
        }

        .social-btn {
            flex-grow: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
            transition: opacity 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .social-btn:hover {
            opacity: 0.9;
        }

        .social-btn.facebook {
            background-color: #3b5998;
            color: white;
        }

        .social-btn.google {
            background-color: #db4437;
            color: white;
        }

        .switch-link {
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
        }

        .switch-link button {
            background: none;
            border: none;
            color: #C0392B;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
        }

        .auth-message {
            margin: 20px 30px 0;
            padding: 12px 16px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
        }

        .auth-message.error {
            background-color: #fdecea;
            border: 1px solid #f5c6cb;
            color: #c0392b;
        }

        .auth-message.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
    </style>

    <div class="container auth-page">
        <div class="auth-box">
            <div id="authMessage" class="auth-message" style="display: none;"></div>

            <div class="tab-buttons">
                <button class="tab-btn active" data-tab="login">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </button>
                <button class="tab-btn" data-tab="register">
                    <i class="fas fa-user-plus"></i> Đăng ký
                </button>
            </div>

            <div id="login" class="tab-content active">
                <form id="loginForm" onsubmit="event.preventDefault(); handleLogin();">
                    <label for="loginUsername">Tên người dùng / Email (*):</label>
                    <input type="text" id="loginUsername" required placeholder="Nhập tên người dùng hoặc email">

                    <label for="loginPassword">Mật khẩu (*):</label>
                    <input type="password" id="loginPassword" required placeholder="Nhập mật khẩu">

                    <button type="button" class="forgot-password" onclick="location.href='{{ route('password.request') }}'">
                        Quên mật khẩu?
                    </button>

                    <button type="submit" class="btn-primary large-btn" id="loginBtn">
                        Đăng nhập
                    </button>
                </form>

                <div class="switch-link">
                    Chưa có tài khoản? <button onclick="switchToTab('register')">Đăng ký ngay!</button>
                </div>

                <div class="social-login-divider">
                    Hoặc đăng nhập với
                </div>

                <div class="social-login-buttons">
                    <button class="social-btn google" onclick="location.href='/auth/google'">
                        <i class="fab fa-google"></i> Đăng nhập bằng Google
                    </button>
                </div>
            </div>

            <div id="register" class="tab-content">
                <form id="registerForm" onsubmit="event.preventDefault(); handleRegister();">
                    <label for="regUsername">Tên người dùng (*):</label>
                    <input type="text" id="regUsername" name="username" required maxlength="20"
                        placeholder="Nhập tên người dùng (tối đa 20 ký tự)">

                    <label for="regEmail">Email (*):</label>
                    <input type="email" id="regEmail" name="email" required maxlength="50"
                        placeholder="ví dụ: a@gmail.com (tối đa 50 ký tự)">

                    <label for="regPhone">Số điện thoại:</label>
                    <input type="text" id="regPhone" name="phone" maxlength="10" placeholder="Nhập số điện thoại (10 số)">

                    <label for="regPassword">Mật khẩu (*):</label>
                    <input type="password" id="regPassword" name="password" required placeholder="Tối thiểu 6 ký tự">

                    <label for="regConfirmPassword">Xác nhận mật khẩu (*):</label>
                    <input type="password" id="regConfirmPassword" required placeholder="Nhập lại mật khẩu">

                    <button type="submit" class="btn-primary large-btn" id="registerBtn">
                        Đăng ký
                    </button>
                </form>

                <div class="switch-link">
                    Đã có tài khoản? <button onclick="switchToTab('login')">Đăng nhập ngay!</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setupTabSwitching();
        });

        function showAuthMessage(message, type = 'error') {
            const box = document.getElementById('authMessage');
            box.textContent = message;
            box.className = `auth-message ${type}`;
            box.style.display = 'block';
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function hideAuthMessage() {
            const box = document.getElementById('authMessage');
            box.style.display = 'none';
            box.textContent = '';
            box.className = 'auth-message';
        }

        function switchToTab(targetId) {
            hideAuthMessage();
            const tabs = document.querySelectorAll('.tab-btn');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            const targetTab = document.querySelector(`.tab-btn[data-tab="${targetId}"]`);
            if (targetTab) {
                targetTab.classList.add('active');
                document.getElementById(targetId).classList.add('active');
            }
        }

        function setupTabSwitching() {
            const tabs = document.querySelectorAll('.tab-btn');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    switchToTab(tab.dataset.tab);
                });
            });
        }

        async function handleLogin() {
            const username = document.getElementById('loginUsername').value;
            const password = document.getElementById('loginPassword').value;
            const loginBtn = document.getElementById('loginBtn');

            hideAuthMessage();
            loginBtn.disabled = true;
            loginBtn.textContent = 'Đang xử lý...';

            try {
                const response = await fetch("{{ route('login.submit') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showAuthMessage(result.message || 'Đăng nhập thành công!', 'success');
                    setTimeout(() => {
                        if (result.role === 'admin') {
                            window.location.href = "{{ route('admin.menu_management') }}";
                        } else {
                            window.location.href = "{{ url('/') }}";
                        }
                    }, 1000);
                } else {
                    showAuthMessage(result.message || 'Tài khoản không tồn tại.', 'error');
                }
            } catch (error) {
                console.error('Lỗi kết nối:', error);
                showAuthMessage('Không thể kết nối đến máy chủ. Vui lòng thử lại sau!', 'error');
            } finally {
                loginBtn.disabled = false;
                loginBtn.textContent = 'Đăng nhập';
            }
        }

        async function handleRegister() {
            const username = document.getElementById('regUsername').value;
            const email = document.getElementById('regEmail').value;
            const phone = document.getElementById('regPhone').value;
            const pass = document.getElementById('regPassword').value;
            const confirmPass = document.getElementById('regConfirmPassword').value;
            const registerBtn = document.getElementById('registerBtn');

            hideAuthMessage();

            if (pass !== confirmPass) {
                showAuthMessage('Mật khẩu xác nhận không khớp. Vui lòng kiểm tra lại!', 'error');
                return;
            }

            registerBtn.disabled = true;
            registerBtn.textContent = 'Đang xử lý...';

            try {
                const response = await fetch("{{ route('register.submit') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        username: username,
                        email: email,
                        phone: phone,
                        password: pass,
                        password_confirmation: confirmPass
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showAuthMessage('Đăng ký thành công! Chuyển hướng về trang chủ...', 'success');
                    setTimeout(() => {
                        window.location.href = "{{ url('/') }}";
                    }, 1500);
                } else {
                    showAuthMessage(result.message || 'Tài khoản đã tồn tại.', 'error');
                }
            } catch (error) {
                console.error('Lỗi kết nối:', error);
                showAuthMessage('Không thể kết nối đến máy chủ. Vui lòng thử lại sau!', 'error');
            } finally {
                registerBtn.disabled = false;
                registerBtn.textContent = 'Đăng ký';
            }
        }


    </script>
@endsection