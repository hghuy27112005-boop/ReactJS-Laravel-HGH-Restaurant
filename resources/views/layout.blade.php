<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NHÀ HÀNG HGH</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('hgh-favicon.ico') }}?v=1">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('hgh-32x32.png') }}?v=1">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('hgh-16x16.png') }}?v=1">
    <link rel="apple-touch-icon" href="{{ asset('hgh-apple.png') }}?v=1">

    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        .main-header { display: flex; justify-content: space-between; align-items: center; background-color: #C0392B; padding: 15px 40px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .logo-section a { color: #fff; font-size: 26px; font-weight: 700; text-decoration: none; }
        .main-nav { display: flex; gap: 30px; }
        .main-nav a { text-decoration: none; color: #fff; font-size: 16px; padding: 5px 0; transition: border-bottom 0.3s ease; border-bottom: 2px solid transparent; }
        .main-nav a:hover, .main-nav a.active { border-bottom: 2px solid #fff; }
        
        .user-actions { display: flex; align-items: center; gap: 20px; }
        .action-btn { background-color: transparent; color: #fff; border: 1px solid #fff; padding: 8px 15px; border-radius: 20px; transition: all 0.3s; text-decoration: none; display: flex; align-items: center; font-size: 14px; gap: 8px; cursor: pointer; }
        .action-btn:hover { background-color: #fff; color: #C0392B; }

        .avatar-circle { width: 40px; height: 40px; background-color: #fff; color: #C0392B; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; cursor: pointer; text-decoration: none; transition: all 0.3s; border: 2px solid transparent; }
        .avatar-circle:hover { transform: scale(1.1); background-color: #f8f8f8; }

        .main-footer { background-color: #C0392B; color: white; padding: 30px 100px; display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 50px; }
        .footer-left p { margin: 5px 0; font-size: 18px; }
        .social-links a { color: white; text-decoration: none; display: block; margin-top: 10px; }
        .social-links a:hover { color: #FFD700; }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo-section">
            <a href="{{ url('/') }}">NHÀ HÀNG HGH</a>
        </div>
        
        <nav class="main-nav">
            <a href="{{ url('/gioi-thieu') }}" class="{{ Request::is('gioi-thieu') ? 'active' : '' }}">Giới Thiệu</a>
            <a href="{{ url('/menu') }}" class="{{ Request::is('menu*') ? 'active' : '' }}">Menu</a> 
            <a href="{{ route('transaction_history') }}" class="{{ Request::is('transaction-history') || Request::is('booking-history') ? 'active' : '' }}">Lịch sử giao dịch</a>
        </nav>

        <div class="user-actions">
            <a href="{{ url('/gio-hang') }}" class="action-btn">
                <i class="fas fa-shopping-cart"></i> Giỏ Hàng
            </a>

            @auth
                <a href="{{ route('profile') }}" class="avatar-circle" title="Trang cá nhân của {{ Auth::user()->username }}">
                    <i class="fas fa-user"></i>
                </a>
            @else
                <a href="{{ route('login_register') }}" class="action-btn">
                    <i class="fas fa-user-circle"></i> Tài Khoản
                </a>
            @endauth
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    @if(!Request::is('menu/*'))
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