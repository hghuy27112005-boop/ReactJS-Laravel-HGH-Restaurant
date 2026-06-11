import React, { useState, useEffect } from 'react';
import { dishAPI } from '../../services/api';
import { Loading, ErrorMessage, EmptyState, Button } from '../../components/Shared';

const MenuPage = () => {
    const [dishes, setDishes] = useState([]);
    const [filteredDishes, setFilteredDishes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [search, setSearch] = useState('');
    const [selectedType, setSelectedType] = useState('');
    const [types, setTypes] = useState([]);

    useEffect(() => {
        fetchDishes();
    }, []);

    useEffect(() => {
        filterDishes();
    }, [search, selectedType, dishes]);

    const fetchDishes = async () => {
        try {
            setLoading(true);
            const response = await dishAPI.getAll();
            setDishes(response.data);
        } catch (err) {
            setError('Lỗi tải danh sách món ăn');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const filterDishes = () => {
        let filtered = dishes;

        if (search) {
            filtered = filtered.filter(dish =>
                dish.dish_name.toLowerCase().includes(search.toLowerCase())
            );
        }

        if (selectedType) {
            filtered = filtered.filter(dish => dish.type_id === parseInt(selectedType));
        }

        setFilteredDishes(filtered);
    };

    const handleAddToCart = (dish) => {
        // TODO: Implement cart functionality
        alert(`Thêm ${dish.dish_name} vào giỏ hàng`);
    };

    if (loading) return <Loading />;

    return (
        <div className="min-h-screen bg-gray-50">
            <div className="max-w-7xl mx-auto px-4 py-8">
                <h1 className="text-4xl font-bold mb-8 text-red-600">Thực đơn</h1>

                {error && <ErrorMessage message={error} />}

                {/* Filters */}
                <div className="bg-white rounded-lg shadow p-6 mb-8">
                    <div className="grid md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-semibold mb-2">Tìm kiếm</label>
                            <input
                                type="text"
                                placeholder="Tìm kiếm món ăn..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-red-600"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-semibold mb-2">Loại món</label>
                            <select
                                value={selectedType}
                                onChange={(e) => setSelectedType(e.target.value)}
                                className="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-red-600"
                            >
                                <option value="">Tất cả loại</option>
                                <option value="1">Pizza</option>
                                <option value="2">Pasta</option>
                                <option value="3">Burger</option>
                                <option value="4">Salad</option>
                                <option value="5">Dessert</option>
                                <option value="6">Beverage</option>
                                <option value="7">Appetizer</option>
                                <option value="8">Asian Cuisine</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Dishes Grid */}
                {filteredDishes.length === 0 ? (
                    <EmptyState
                        icon="🔍"
                        title="Không tìm thấy"
                        description="Không có món ăn phù hợp với tìm kiếm của bạn"
                    />
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {filteredDishes.map(dish => (
                            <div key={dish.dish_id} className="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                                <img
                                    src={dish.image_url}
                                    alt={dish.dish_name}
                                    className="w-full h-48 object-cover"
                                />
                                <div className="p-4">
                                    <h3 className="font-semibold text-lg mb-2">{dish.dish_name}</h3>
                                    <p className="text-red-600 font-bold text-xl mb-4">
                                        {Number(dish.price).toLocaleString('vi-VN')}đ
                                    </p>
                                    {dish.is_bestseller && (
                                        <span className="inline-block bg-yellow-200 text-yellow-800 text-xs font-semibold px-3 py-1 rounded-full mb-4">
                                            ⭐ Bán chạy
                                        </span>
                                    )}
                                    <div className="flex gap-2">
                                        <Button
                                            onClick={() => handleAddToCart(dish)}
                                            className="flex-1"
                                        >
                                            + Giỏ hàng
                                        </Button>
                                        <Button
                                            variant="secondary"
                                            onClick={() => window.location.href = `/dishes/${dish.dish_id}`}
                                            className="flex-1"
                                        >
                                            Chi tiết
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
};

export default MenuPage;
