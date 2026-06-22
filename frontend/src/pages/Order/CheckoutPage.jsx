import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useCart } from '../../context/CartContext';
import { useAuthContext } from '../../context/AuthContext';
import { billAPI } from '../../services/api';
import { Button, Card, ErrorMessage, SuccessMessage, Loading } from '../../components/Shared';
import { formatCurrency } from '../../utils/helpers';

const CheckoutPage = () => {
    const navigate = useNavigate();
    const { user } = useAuthContext();
    const { items, totalPrice, clearCart } = useCart();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [orderType, setOrderType] = useState('delivery');
    const [formData, setFormData] = useState({
        address: user?.address || '',
        phone: user?.phone || '',
        notes: '',
        payment_method: 'cash',
    });

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData({ ...formData, [name]: value });
    };

    const handleSubmitOrder = async (e) => {
        e.preventDefault();
        if (items.length === 0) {
            setError('Giỏ hàng trống');
            return;
        }

        try {
            setLoading(true);
            setError(null);

            const orderData = {
            order_type: orderType,
            address: formData.address,
            phone: formData.phone,
            notes: formData.notes,
            payment_method: formData.payment_method,
            items: items.map(item => ({
                dish_id: item.dish_id,
                quantity: item.quantity,
                price_at_order: item.dish_price,
            })),
        };

        const response = await billAPI.create(orderData);
        const billId = response.data.data.bill_id;

        // Luôn thanh toán ngay, vì giờ không còn lựa chọn "tiền mặt" để bỏ qua
        await billAPI.processPayment(billId, {
            payment_method: formData.payment_method, // 'credit_card' | 'bank_transfer' | 'momo' v.v.
            amount: totalPrice,
        });

        clearCart();
        navigate(`/order-confirmation/${billId}`);
        } catch (err) {
            setError(err.response?.data?.message || 'Lỗi tạo đơn hàng');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    if (items.length === 0) {
        return (
            <div className="min-h-screen bg-gray-50 py-8">
                <div className="max-w-4xl mx-auto px-4">
                    <h1 className="text-4xl font-bold mb-8 text-red-600">Thanh toán</h1>
                    <Card>
                        <p className="text-center text-gray-500 py-8">Giỏ hàng trống. <a href="/menu" className="text-red-600 hover:underline">Về thực đơn</a></p>
                    </Card>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-4xl mx-auto px-4">
                <h1 className="text-4xl font-bold mb-8 text-red-600">Thanh toán</h1>

                {error && <ErrorMessage message={error} onClose={() => setError(null)} />}

                <form onSubmit={handleSubmitOrder} className="grid lg:grid-cols-3 gap-8">
                    {/* Checkout Form */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Order Type */}
                        <Card title="Loại đơn hàng">
                            <div className="space-y-3">
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="orderType"
                                        value="delivery"
                                        checked={orderType === 'delivery'}
                                        onChange={(e) => setOrderType(e.target.value)}
                                    />
                                    <span>🚗 Giao hàng tại nhà</span>
                                </label>
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="orderType"
                                        value="pickup"
                                        checked={orderType === 'pickup'}
                                        onChange={(e) => setOrderType(e.target.value)}
                                    />
                                    <span>🏪 Lấy tại cửa hàng</span>
                                </label>
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="orderType"
                                        value="dine_in"
                                        checked={orderType === 'dine_in'}
                                        onChange={(e) => setOrderType(e.target.value)}
                                    />
                                    <span>🍽️ Ăn tại cửa hàng</span>
                                </label>
                            </div>
                        </Card>

                        {/* Delivery Info */}
                        {orderType === 'delivery' && (
                            <Card title="Địa chỉ giao hàng">
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-semibold mb-2">Địa chỉ</label>
                                        <input
                                            type="text"
                                            name="address"
                                            value={formData.address}
                                            onChange={handleInputChange}
                                            className="w-full border border-gray-300 rounded px-3 py-2"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold mb-2">Số điện thoại</label>
                                        <input
                                            type="tel"
                                            name="phone"
                                            value={formData.phone}
                                            onChange={handleInputChange}
                                            className="w-full border border-gray-300 rounded px-3 py-2"
                                            required
                                        />
                                    </div>
                                </div>
                            </Card>
                        )}

                        {/* Notes */}
                        <Card title="Ghi chú">
                            <textarea
                                name="notes"
                                value={formData.notes}
                                onChange={handleInputChange}
                                placeholder="Ghi chú thêm (không bắt buộc)"
                                rows="4"
                                className="w-full border border-gray-300 rounded px-3 py-2"
                            />
                        </Card>

                        {/* Payment Method */}
                        <Card title="Phương thức thanh toán">
                            <div className="space-y-3">
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="paymentMethod"
                                        value="cash"
                                        checked={formData.payment_method === 'cash'}
                                        onChange={handleInputChange}
                                    />
                                    <span>💵 Tiền mặt</span>
                                </label>
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="paymentMethod"
                                        value="momo"
                                        checked={formData.payment_method === 'momo'}
                                        onChange={handleInputChange}
                                    />
                                    <span>📱 Momo</span>
                                </label>
                                <label className="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="paymentMethod"
                                        value="vnpay"
                                        checked={formData.payment_method === 'vnpay'}
                                        onChange={handleInputChange}
                                    />
                                    <span>🏦 VNPay</span>
                                </label>
                            </div>
                        </Card>
                    </div>

                    {/* Order Summary */}
                    <div className="lg:col-span-1">
                        <Card title="Tóm tắt đơn hàng" className="sticky top-4">
                            <div className="space-y-3 text-sm mb-6">
                                {items.map(item => (
                                    <div key={item.dish_id} className="flex justify-between">
                                        <span>{item.dish_name} x{item.quantity}</span>
                                        <span className="font-bold">{formatCurrency(item.dish_price * item.quantity)}</span>
                                    </div>
                                ))}
                                <div className="border-t pt-3">
                                    <div className="flex justify-between">
                                        <span>Tổng tiền</span>
                                        <span className="font-bold text-red-600">{formatCurrency(totalPrice)}</span>
                                    </div>
                                </div>
                            </div>

                            <Button
                                type="submit"
                                disabled={loading}
                                className="w-full"
                            >
                                {loading ? 'Đang xử lý...' : 'Đặt hàng ngay'}
                            </Button>
                        </Card>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default CheckoutPage;
