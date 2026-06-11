import React from 'react';

export default function HomeApp({ highlights = [] }) {
    return (
        <div className="container" style={{ padding: '40px', maxWidth: '1200px', margin: '0 auto' }}>
            {/* Carousel banner */}
            <div style={{ height: '450px', marginBottom: '30px', borderRadius: '12px', overflow: 'hidden', boxShadow: '0 4px 15px rgba(0,0,0,0.2)' }}>
                <img src="/pics/01.jpg" alt="Banner Nhà Hàng" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
            </div>

            <h2 style={{ color: '#333', borderLeft: '5px solid #C0392B', paddingLeft: '15px', marginBottom: '25px' }}>
                <i className="fas fa-star" style={{ color: '#FFD700', marginRight: '8px' }}></i>
                Món nổi bật
            </h2>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '25px' }}>
                {highlights.map((dish) => (
                    <div key={dish.dish_id} className="dish-card" style={{ background: '#fff', padding: '20px', borderRadius: '12px', textAlign: 'center', boxShadow: '0 2px 10px rgba(0,0,0,0.05)', border: '1px solid #eee', transition: 'transform 0.3s' }}>
                        <img src={dish.image_url} alt={dish.dish_name} style={{ width: '100%', height: '200px', objectFit: 'cover', borderRadius: '8px', marginBottom: '15px' }} />
                        <h3>{dish.dish_name}</h3>
                        <p style={{ color: '#C0392B', fontWeight: 'bold', fontSize: '22px', margin: '10px 0' }}>
                            {Number(dish.price).toLocaleString('vi-VN')}đ
                        </p>
                        <button
                            onClick={() => window.location.href = `/menu/${dish.dish_id}`}
                            style={{ background: '#333', color: '#fff', border: 'none', padding: '12px 20px', width: '100%', cursor: 'pointer', borderRadius: '25px', fontWeight: 'bold', transition: 'background 0.3s' }}
                            onMouseEnter={e => e.target.style.background = '#C0392B'}
                            onMouseLeave={e => e.target.style.background = '#333'}
                        >
                            Xem chi tiết
                        </button>
                    </div>
                ))}
            </div>
        </div>
    );
}
