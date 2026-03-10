@extends('layout')

@section('content')
<style>
    .about-container { padding: 60px 20px; max-width: 1100px; margin: 0 auto; }
    
    /* Căn đáy: Ép hình và chữ cùng nằm trên một đường cơ sở ở dưới */
    .about-flex { 
        display: flex; 
        gap: 50px; 
        align-items: flex-end; /* Đây là lệnh giúp đáy hình bằng đáy chữ */
        flex-wrap: wrap; 
    }
    
    .about-text { flex: 1; min-width: 300px; }
    .about-image { flex: 1; min-width: 300px; }
    .about-image img { 
        width: 100%; 
        border-radius: 15px; 
        box-shadow: 0 10px 20px rgba(0,0,0,0.1); 
        display: block; /* Khử khoảng hở dư thừa dưới ảnh */
    }

    /* Tiêu đề có gạch chân dài bằng cụm chữ */
    .about-text h2 { 
        color: #C0392B; 
        font-size: 36px; 
        margin-bottom: 25px; 
        display: inline-block; 
        border-bottom: 4px solid #C0392B; 
        padding-bottom: 5px; 
    }

    /* Văn bản căn đều 2 bên như Word */
    .about-text p { 
        line-height: 1.8; 
        color: #444; 
        font-size: 18px; 
        text-align: justify; 
        margin: 0 0 15px 0; /* Bỏ margin top để căn đáy chính xác hơn */
    }
    
    .about-text p:last-child { margin-bottom: 0; } /* Món quà nhỏ để dòng cuối cùng khít lề */
</style>

<div class="about-container">
    <div class="about-flex">
        <div class="about-text">
            <h2>Về Nhà Hàng HGH</h2>
            <p>
                Tọa lạc tại trung tâm TP. Cần Thơ, Nhà hàng HGH là điểm đến lý tưởng cho những ai yêu thích hương vị cơm gia đình thuần túy. 
            </p>
            <p>   
                Chúng tôi tự hào mang đến những món ăn không chỉ ngon miệng mà còn đảm bảo vệ sinh, được chế biến từ nguồn nguyên liệu tươi sạch đặc trưng của vùng sông nước miền Tây.
            </p>
            <p>
                Với không gian ấm cúng và đội ngũ nhân viên nhiệt tình, HGH mong muốn mỗi bữa ăn của quý khách đều mang lại cảm giác thân thuộc như tại chính ngôi nhà của mình.
            </p>
        </div>
        <div class="about-image">
            <img src="{{ asset('pics/13.jpg') }}" alt="Không gian nhà hàng HGH">
        </div>
    </div>
</div>
@endsection