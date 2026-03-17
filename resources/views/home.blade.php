@extends('layout')

@section('content')
<style>
    /* Dán CSS riêng của trang chủ vào đây */
    .container { padding: 40px; max-width: 1200px; margin: 0 auto; }
    .carousel { height: 450px; background: #ddd; margin-bottom: 30px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
    .carousel img { width: 100%; height: 100%; object-fit: cover; }
    
    .dishes-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; }
    .dish-card { background: #fff; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.3s; border: 1px solid #eee; }
    .dish-card:hover { transform: translateY(-5px); }
    .dish-img { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 15px; }
    .price { color: #C0392B; font-weight: bold; font-size: 22px; margin: 10px 0; }
    .btn-detail { background: #333; color: #fff; border: none; padding: 12px 20px; width: 100%; cursor: pointer; border-radius: 25px; font-weight: bold; transition: background 0.3s; }
    .btn-detail:hover { background: #C0392B; }
    h2 { color: #333; border-left: 5px solid #C0392B; padding-left: 15px; margin-bottom: 25px; }
</style>

<main class="container">
    <div class="carousel">
        <img src="{{ asset('pics/01.jpg') }}" alt="Banner Nhà Hàng">
    </div>

    <h2><i class="fas fa-star" style="color: #FFD700;"></i> Món nổi bật</h2>
    
    <div class="dishes-grid">
        @foreach ($highlights as $dish)
            <div class="dish-card">
                <img src="{{ $dish->image_url }}" class="dish-img" alt="{{ $dish->dish_name }}">
                <h3>{{ $dish->dish_name }}</h3>
                <p class="price">{{ number_format($dish->price, 0, ',', '.') }}đ</p>
                
                <button class="btn-detail" onclick="window.location.href='{{ url('/menu/' . $dish->dish_id) }}'">
                    Xem chi tiết
                </button>
            </div>
        @endforeach
    </div>
</main>
@endsection