<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NHÀ HÀNG HGH</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('hgh-favicon.ico') }}?v=1">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('hgh-32x32.png') }}?v=1">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('hgh-16x16.png') }}?v=1">
    <link rel="apple-touch-icon" href="{{ asset('hgh-apple.png') }}?v=1">

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #C0392B;
            padding: 15px 40px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo-section a {
            color: #fff;
            font-size: 26px;
            font-weight: 700;
            text-decoration: none;
        }

        .main-nav {
            display: flex;
            gap: 30px;
        }

        .main-nav a {
            text-decoration: none;
            color: #fff;
            font-size: 16px;
            padding: 5px 0;
            transition: border-bottom 0.3s ease;
            border-bottom: 2px solid transparent;
        }

        .main-nav a:hover,
        .main-nav a.active {
            border-bottom: 2px solid #fff;
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .action-btn {
            background-color: transparent;
            color: #fff;
            border: 1px solid #fff;
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 14px;
            gap: 8px;
            cursor: pointer;
        }

        .action-btn:hover {
            background-color: #fff;
            color: #C0392B;
        }

        .avatar-circle {
            width: 40px;
            height: 40px;
            background-color: #fff;
            color: #C0392B;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .avatar-circle:hover {
            transform: scale(1.1);
            background-color: #f8f8f8;
        }

        /* Admin Pill Style */
        .admin-pill {
            background: #fff;
            padding: 5px 15px 5px 6px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            border: 1px solid #ddd;
            transition: 0.2s;
        }

        .admin-pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .admin-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
        }

        .admin-avatar img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .admin-label {
            color: #C0392B;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.5px;
            text-transform: lowercase;
        }

        .main-footer {
            background-color: #C0392B;
            color: white;
            padding: 30px 100px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 50px;
        }

        .footer-left p {
            margin: 5px 0;
            font-size: 18px;
        }

        .social-links a {
            color: white;
            text-decoration: none;
            display: block;
            margin-top: 10px;
        }

        .social-links a:hover {
            color: #FFD700;
        }
    </style>
</head>

<body>

    <header class="main-header">
        <div class="logo-section">
            <a href="{{ url('/') }}">NHÀ HÀNG HGH</a>
        </div>

        <nav class="main-nav">
            @auth
                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('admin.menu_management') }}"
                        class="{{ Request::is('admin/menu-management') ? 'active' : '' }}">Quản lý thực đơn</a>
                    <a href="{{ route('admin.transaction_management') }}"
                        class="{{ Request::is('admin/transaction-management') ? 'active' : '' }}">Quản lý giao dịch</a>
                @else
                    <a href="{{ url('/gioi-thieu') }}" class="{{ Request::is('gioi-thieu') ? 'active' : '' }}">Giới Thiệu</a>
                    <a href="{{ url('/menu') }}" class="{{ Request::is('menu*') ? 'active' : '' }}">Menu</a>
                    <a href="{{ route('transaction_history') }}"
                        class="{{ Request::is('transaction-history') ? 'active' : '' }}">Lịch sử giao dịch</a>
                @endif
            @else
                <a href="{{ url('/gioi-thieu') }}" class="{{ Request::is('gioi-thieu') ? 'active' : '' }}">Giới Thiệu</a>
                <a href="{{ url('/menu') }}" class="{{ Request::is('menu*') ? 'active' : '' }}">Menu</a>
            @endauth
        </nav>

        <div class="user-actions">
            @auth
                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('profile') }}" class="admin-pill" title="Trang quản trị">
                        <div class="admin-avatar">
                            <img src="{{ asset('hgh-apple.png') }}" alt="Admin">
                        </div>
                        <span class="admin-label">admin</span>
                    </a>
                @else
                    <a href="{{ url('/gio-hang') }}" class="action-btn {{ Request::is('gio-hang') ? 'active-cart' : '' }}">
                        <i class="fas fa-shopping-cart"></i> Giỏ Hàng
                    </a>
                    <a href="{{ route('profile') }}" class="avatar-circle"
                        title="Trang cá nhân của {{ Auth::user()->username }}">
                        @if(Auth::user()->avatar_url)
                            <img src="{{ Auth::user()->avatar_url }}" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;" referrerpolicy="no-referrer">
                        @else
                            <i class="fas fa-user"></i>
                        @endif
                    </a>
                @endif
            @else
                <a href="{{ route('login') }}" class="action-btn">
                    <i class="fas fa-user-circle"></i> Đăng nhập/Đăng ký
                </a>
            @endauth
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    @if(session('success'))
        <script>
            alert("{{ session('success') }}");
        </script>
    @endif

    @if(session('error'))
        <script>
            alert("{{ session('error') }}");
        </script>
    @endif

    @if(!Request::is('menu/*') && (!Auth::check() || Auth::user()->role !== 'admin'))
        <footer class="main-footer">
            <div class="footer-left">
                <p>&copy; 2026 NHÀ HÀNG HGH. Mọi quyền được bảo lưu.</p>
                <p>Địa chỉ: Số 52, đường số 2, Khu CBGV DHCT, Phường An Khánh, Quận Ninh Kiều, TP.Cần Thơ</p>
            </div>
            <div class="footer-right">
                <div class="social-links">
                    <a href="#"><i class="fas fa-envelope"></i> Email hỗ trợ</a>
                    <a href="#"><i class="fab fa-facebook-f"></i> Facebook</a>
                </div>
            </div>
        </footer>
    @endif

</body>

</html>