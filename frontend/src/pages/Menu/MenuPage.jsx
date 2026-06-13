import React, { useState, useEffect } from 'react';
import { dishAPI } from '../../services/api';
import { Loading, ErrorMessage, EmptyState, Button, Modal } from '../../components/Shared';

const MenuPage = () => {
    const [dishes, setDishes] = useState([]);
    const [filteredDishes, setFilteredDishes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [search, setSearch] = useState('');
    const [selectedType, setSelectedType] = useState('');
    const [types, setTypes] = useState([]);
    
    // Modal states
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedDish, setSelectedDish] = useState(null);
    const [quantity, setQuantity] = useState(1);
    const [orderType, setOrderType] = useState('mang-ve');
    const [note, setNote] = useState('');

    useEffect(() => {
        fetchDishes();
        fetchTypes();
    }, []);

    useEffect(() => {
        filterDishes();
    }, [search, selectedType, dishes]);

    const fetchDishes = async () => {
        try {
            setLoading(true);
            const response = await dishAPI.getAll();
            // Xử lý trường hợp data bị bọc trong 'data' object (do paginate của Laravel)
            const items = response.data?.data || response.data || [];
            setDishes(items);
        } catch (err) {
            setError('Lỗi tải danh sách món ăn');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const fetchTypes = async () => {
        try {
            const response = await dishAPI.getDishTypes();
            // Xử lý trường hợp data bị bọc
            const items = response.data?.data || response.data || [];
            setTypes(items);
        } catch (err) {
            console.error('Lỗi tải danh mục món ăn:', err);
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
        setSelectedDish(dish);
        setQuantity(1);
        setOrderType('mang-ve');
        setNote('');
        setIsModalOpen(true);
    };

    const confirmAddToCart = async () => {
        let qty = parseInt(quantity);
        if (isNaN(qty) || qty < 1 || qty > 10) {
            alert('Vui lòng nhập số lượng từ 1 đến 10!');
            return;
        }

        // TODO: Implement actual cart API call here
        alert(`Thành công! Đã thêm ${qty} ${selectedDish.dish_name} vào giỏ hàng.`);
        setIsModalOpen(false);
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
                                {types.map(type => (
                                    <option key={type.type_id} value={type.type_id}>
                                        {type.type_name}
                                    </option>
                                ))}
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
                            <div key={dish.dish_id} className="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition flex flex-col h-full">
                                <img
                                    src={dish.image_url}
                                    alt={dish.dish_name}
                                    className="w-full h-48 object-cover"
                                />
                                <div className="p-4 flex flex-col flex-grow">
                                    <h3 className="font-semibold text-lg mb-2">{dish.dish_name}</h3>
                                    <p className="text-red-600 font-bold text-xl mb-4">
                                        {Number(dish.price).toLocaleString('vi-VN')}đ
                                    </p>
                                    {dish.is_bestseller && (
                                        <div className="mb-4">
                                            <span className="inline-block bg-yellow-200 text-yellow-800 text-xs font-semibold px-3 py-1 rounded-full">
                                                ⭐ Bán chạy
                                            </span>
                                        </div>
                                    )}
                                    <div className="mt-auto">
                                        <button
                                            onClick={() => handleAddToCart(dish)}
                                            className="w-full py-2 px-4 rounded border border-red-600 text-red-600 font-semibold bg-white hover:bg-red-600 hover:text-white transition-colors duration-300"
                                        >
                                            Thêm vào giỏ hàng
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Order Modal */}
            <Modal
                isOpen={isModalOpen}
                title={selectedDish ? `Đặt món: ${selectedDish.dish_name}` : 'Đặt món'}
                onClose={() => setIsModalOpen(false)}
                onConfirm={confirmAddToCart}
                confirmText="Xác nhận thêm vào giỏ hàng"
            >
                <div className="flex flex-col gap-4">
                    <div>
                        <label className="block font-semibold mb-1 text-gray-700">Số lượng (Tối đa 10):</label>
                        <input
                            type="number"
                            value={quantity}
                            onChange={(e) => setQuantity(e.target.value)}
                            min="1"
                            max="10"
                            className="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-red-600"
                        />
                    </div>
                    <div>
                        <label className="block font-semibold mb-1 text-gray-700">Hình thức:</label>
                        <select
                            value={orderType}
                            onChange={(e) => setOrderType(e.target.value)}
                            className="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-red-600"
                        >
                            <option value="mang-ve">Mang về (Giao hàng tận nơi)</option>
                            <option value="dat-ban">Ăn tại quán (Đặt bàn trước)</option>
                        </select>
                    </div>
                    <div>
                        <label className="block font-semibold mb-1 text-gray-700">Ghi chú:</label>
                        <textarea
                            value={note}
                            onChange={(e) => setNote(e.target.value)}
                            rows="3"
                            placeholder="Ví dụ: Không cay, nhiều hành..."
                            className="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-red-600"
                        ></textarea>
                    </div>
                </div>
            </Modal>
        </div>
    );
};

export default MenuPage;
