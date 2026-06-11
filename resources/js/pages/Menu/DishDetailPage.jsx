import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { dishAPI } from '../../services/api';
import { useCart } from '../../context/CartContext';
import { Loading, ErrorMessage, Button, Card, Badge } from '../../components/Shared';
import { formatCurrency } from '../../utils/helpers';
import ReviewsSection from '../../components/ReviewsSection';

const DishDetailPage = () => {
    const { dishId } = useParams();
    const navigate = useNavigate();
    const { addToCart } = useCart();
    const [dish, setDish] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [quantity, setQuantity] = useState(1);

    useEffect(() => {
        fetchDish();
    }, [dishId]);

    const fetchDish = async () => {
        try {
            setLoading(true);
            // In a real app, you'd have a getDishById endpoint
            const response = await dishService.getDishes();
            const found = response.data.data.find(d => d.dish_id === parseInt(dishId));
            
            if (found) {
                setDish(found);
            } else {
                setError('Không tìm thấy món ăn');
            }
        } catch (err) {
            setError('Lỗi tải thông tin');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleAddToCart = () => {
        for (let i = 0; i < quantity; i++) {
            addToCart(dish);
        }
        navigate('/cart');
    };

    if (loading) return <Loading />;
    if (error || !dish) return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-4xl mx-auto px-4">
                <ErrorMessage message={error || 'Không tìm thấy'} />
                <Button onClick={() => navigate('/menu')} className="mt-4">← Quay lại</Button>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-4xl mx-auto px-4">
                <Button onClick={() => navigate('/menu')} variant="secondary" className="mb-6">
                    ← Quay lại thực đơn
                </Button>

                <div className="grid md:grid-cols-2 gap-8 mb-8">
                    {/* Image */}
                    <div>
                        {dish.image && (
                            <img
                                src={`/dishes/${dish.image}`}
                                alt={dish.dish_name}
                                className="w-full rounded-lg shadow-lg"
                            />
                        )}
                    </div>

                    {/* Info */}
                    <div>
                        <div className="flex items-start justify-between mb-4">
                            <div>
                                <h1 className="text-4xl font-bold mb-2">{dish.dish_name}</h1>
                                {dish.is_bestseller && (
                                    <Badge variant="danger">🔥 Bán chạy</Badge>
                                )}
                            </div>
                        </div>

                        <Card title="Giá" className="mb-6">
                            <p className="text-4xl font-bold text-red-600">{formatCurrency(dish.dish_price)}</p>
                        </Card>

                        <Card title="Mô tả" className="mb-6">
                            <p className="text-gray-700">{dish.description || 'Không có mô tả'}</p>
                        </Card>

                        <Card title="Thông tin" className="mb-6">
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <span>Loại:</span>
                                    <span className="font-semibold">{dish.dish_type}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Tình trạng:</span>
                                    {dish.stock_quantity > 0 ? (
                                        <span className="text-green-600">✓ Còn hàng</span>
                                    ) : (
                                        <span className="text-red-600">✕ Hết hàng</span>
                                    )}
                                </div>
                                <div className="flex justify-between">
                                    <span>Lượt bán:</span>
                                    <span className="font-semibold">{dish.total_sold || 0}</span>
                                </div>
                            </div>
                        </Card>

                        {/* Quantity & Add to Cart */}
                        <Card className="mb-6">
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-semibold mb-2">Số lượng</label>
                                    <div className="flex items-center gap-2">
                                        <button
                                            onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                            className="px-3 py-2 border rounded hover:bg-gray-200"
                                        >
                                            −
                                        </button>
                                        <input
                                            type="number"
                                            value={quantity}
                                            onChange={(e) => setQuantity(Math.max(1, parseInt(e.target.value) || 1))}
                                            className="w-16 text-center border rounded px-2 py-2"
                                        />
                                        <button
                                            onClick={() => setQuantity(quantity + 1)}
                                            className="px-3 py-2 border rounded hover:bg-gray-200"
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>

                                <Button
                                    onClick={handleAddToCart}
                                    disabled={dish.stock_quantity === 0}
                                    className="w-full"
                                >
                                    {dish.stock_quantity > 0 ? '🛒 Thêm vào giỏ' : '✕ Hết hàng'}
                                </Button>
                            </div>
                        </Card>
                    </div>
                </div>

                {/* Reviews */}
                <ReviewsSection dishId={dish.dish_id} dishName={dish.dish_name} />
            </div>
        </div>
    );
};

export default DishDetailPage;
