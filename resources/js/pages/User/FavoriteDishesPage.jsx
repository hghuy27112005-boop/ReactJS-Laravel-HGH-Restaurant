import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { dishAPI } from '../../services/api';
import { Loading, ErrorMessage, Card, Button, EmptyState } from '../../components/Shared';
import DishCard from '../../components/DishCard';

const FavoriteDishesPage = () => {
    const navigate = useNavigate();
    const [favorites, setFavorites] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [sortBy, setSortBy] = useState('recent');

    useEffect(() => {
        fetchFavorites();
    }, []);

    const fetchFavorites = async () => {
        try {
            setLoading(true);
            // Simulated favorites data
            const mockFavorites = [
                {
                    dish_id: 1,
                    name: 'Margherita Pizza',
                    price: 85000,
                    image: '/pics/pizza.jpg',
                    rating: 4.8,
                    reviews: 156,
                    added_date: '2024-06-01',
                },
                {
                    dish_id: 2,
                    name: 'Carbonara Pasta',
                    price: 95000,
                    image: '/pics/pasta.jpg',
                    rating: 4.7,
                    reviews: 142,
                    added_date: '2024-05-28',
                },
                {
                    dish_id: 3,
                    name: 'Caesar Salad',
                    price: 65000,
                    image: '/pics/salad.jpg',
                    rating: 4.5,
                    reviews: 98,
                    added_date: '2024-05-25',
                },
            ];
            setFavorites(mockFavorites);
        } catch (err) {
            setError('Lỗi tải danh sách yêu thích');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const removeFavorite = (dishId) => {
        setFavorites(favorites.filter(d => d.dish_id !== dishId));
    };

    const getSorted = () => {
        const sorted = [...favorites];
        if (sortBy === 'rating') {
            return sorted.sort((a, b) => b.rating - a.rating);
        }
        if (sortBy === 'price-low') {
            return sorted.sort((a, b) => a.price - b.price);
        }
        if (sortBy === 'price-high') {
            return sorted.sort((a, b) => b.price - a.price);
        }
        return sorted; // recent
    };

    if (loading) return <Loading />;

    const sorted = getSorted();

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-6xl mx-auto px-4">
                <div className="flex justify-between items-center mb-8">
                    <h1 className="text-4xl font-bold text-red-600">❤️ Yêu thích của tôi</h1>
                    <span className="bg-red-100 text-red-600 px-4 py-2 rounded-full font-semibold">
                        {favorites.length} món
                    </span>
                </div>

                {error && <ErrorMessage message={error} onClose={() => setError(null)} />}

                {/* Sort Options */}
                {favorites.length > 0 && (
                    <div className="flex gap-2 mb-8">
                        <select
                            value={sortBy}
                            onChange={(e) => setSortBy(e.target.value)}
                            className="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                        >
                            <option value="recent">Mới nhất</option>
                            <option value="rating">Đánh giá cao nhất</option>
                            <option value="price-low">Giá thấp nhất</option>
                            <option value="price-high">Giá cao nhất</option>
                        </select>
                    </div>
                )}

                {/* Favorites Grid */}
                {sorted.length === 0 ? (
                    <EmptyState
                        icon="❤️"
                        title="Chưa có món yêu thích"
                        description="Thêm những món ăn yêu thích của bạn để dễ tìm lại sau này"
                        action={<Button onClick={() => navigate('/menu')}>Xem thực đơn</Button>}
                    />
                ) : (
                    <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {sorted.map(dish => (
                            <div key={dish.dish_id} className="relative">
                                <DishCard dish={dish} />
                                <button
                                    onClick={() => removeFavorite(dish.dish_id)}
                                    className="absolute top-2 right-2 bg-red-600 text-white p-2 rounded-full hover:bg-red-700 transition"
                                    title="Xóa khỏi yêu thích"
                                >
                                    ❤️
                                </button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
};

export default FavoriteDishesPage;