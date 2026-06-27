import React, { useState, useEffect } from 'react';
import { deliveryService, billService, vnpayService, extractListData } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge, EmptyState, Modal } from '../../components/Shared';

const DeliveriesPage = () => {
    const [deliveries, setDeliveries] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filter, setFilter] = useState('all');

    // Cart states
    const [deliveryCart, setDeliveryCart] = useState([]);
    const [address, setAddress] = useState('');
    const [checkoutStage, setCheckoutStage] = useState('info'); // info, payment
    const [isConfirmModalOpen, setIsConfirmModalOpen] = useState(false);

    useEffect(() => {
        fetchDeliveries();
        const cart = JSON.parse(localStorage.getItem('delivery_cart')) || [];
        setDeliveryCart(cart);
    }, []);

    const cartTotal = deliveryCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    const handleConfirmInfo = (e) => {
        e.preventDefault();
        if (deliveryCart.length === 0 || !address.trim()) return;
        setIsConfirmModalOpen(true);
    };

    const confirmOrder = () => {
        setIsConfirmModalOpen(false);
        setCheckoutStage('payment');
    };

    const handleCartQuantityChange = (idx, amount) => {
        setDeliveryCart(prev => {
            const updated = prev
                .map((item, i) => {
                    if (i !== idx) return item;
                    const newQty = item.quantity + amount;
                    return { ...item, quantity: newQty };
                })
                .filter(item => item.quantity > 0); // tự loại bỏ món đã về 0

            localStorage.setItem('delivery_cart', JSON.stringify(updated));
            return updated;
        });
    };

    const handlePayment = async () => {
        try {
            const orderData = {
                order_type: 'delivery',
                delivery: {
                    address: address,
                    phone: 'N/A',
                },
                payment_method: 'vnpay',
                items: deliveryCart.map(item => ({
                    dish_id: item.dish_id,
                    quantity: item.quantity,
                    price_at_order: item.price,
                })),
            };

            // 1. Tạo bill trước (chưa thanh toán)
            const billRes = await billService.storeBill(orderData);
            const billId = billRes.data.data.bill_id;

            // 2. Lấy URL thanh toán VNPay
            const vnpayRes = await vnpayService.createPaymentUrl({
                bill_id: billId,
                amount: cartTotal,
                order_type: 'delivery',
            });

            // 3. Redirect sang VNPay (rời khỏi app) ngay khi có URL —
            // không còn màn hình "Đang xử lý thanh toán..." giả nữa,
            // vì bước tiếp theo (trang VNPAY thật) đã đủ làm rõ là đang xử lý.
            window.location.href = vnpayRes.data.payment_url;

            // Không cần dọn cart/localStorage ở đây nữa,
            // vì sẽ làm ở PaymentResultPage sau khi quay về thành công
        } catch (err) {
            setError(err.response?.data?.message || 'Lỗi đặt hàng');
        }
    };

    const fetchDeliveries = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await deliveryService.getDeliveries();
            setDeliveries(extractListData(response));
        } catch (err) {
            if (err.response?.status === 401) return;
            setError(err.response?.data?.message || 'Không thể tải danh sách giao hàng. Vui lòng thử lại.');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const getFilteredDeliveries = () => {
        if (filter === 'all') return deliveries;
        if (filter === 'waiting_delivery') {
            return deliveries.filter(d => ['waiting_delivery', 'delivering'].includes(d.delivery?.delivery_status));
        }
        return deliveries.filter(d => d.delivery?.delivery_status === filter);
    };

    const getStatusColor = (bill) => {
        const status = bill.delivery?.delivery_status;
        if (status === 'cancelled') return 'danger';
        if (status === 'delivered') return 'success';
        return 'warning';
    };

    const getStatusLabel = (bill) => {
        const status = bill.delivery?.delivery_status;
        if (status === 'cancelled') return '✕ Đã hủy';
        if (status === 'delivered') return '✓ Đã giao hàng';
        if (status === 'waiting_delivery' || status === 'delivering') return '⏳ Đang chờ giao';
        return '⏳ Đang chờ duyệt';
    };

    if (loading) return <Loading />;

    const filtered = getFilteredDeliveries();

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-6xl mx-auto px-4">
                <h1 className="text-4xl font-bold mb-8 text-red-600">Giao hàng</h1>

                {error && <ErrorMessage message={error} onClose={() => setError(null)} />}

                {/* Upper Half: Đặt hàng cố định */}
                <div className="bg-white rounded-lg shadow p-6 mb-8 border-t-4 border-red-600">
                    <h2 className="text-2xl font-bold mb-4">Góc đặt hàng (Ship)</h2>
                    
                    {deliveryCart.length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            <i className="fas fa-shopping-basket text-4xl mb-3 text-gray-300 block"></i>
                            <p className="text-lg">Quầy đang trống, vui lòng quay lại Menu thêm món vào giỏ hàng.</p>
                            <button onClick={() => window.location.href = '/menu'} className="mt-4 bg-red-600 text-white px-4 py-2 rounded">Đến Menu</button>
                        </div>
                    ) : (
                        <div className="grid md:grid-cols-2 gap-8">
                            <div>
                                <h3 className="font-semibold mb-2">Món đã chọn:</h3>
                                <table className="w-full mb-4 text-sm bg-white border border-black border-t-4 border-t-red-600">
                                    <thead>
                                        <tr>
                                            <th className="text-left py-2 px-3 font-semibold text-gray-700 border border-black">Món</th>
                                            <th className="text-center py-2 px-3 font-semibold text-gray-700 border border-black">Số lượng</th>
                                            <th className="text-right py-2 px-3 font-semibold text-gray-700 border border-black">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {deliveryCart.map((item, idx) => (
                                            <tr key={idx}>
                                                <td className="py-2 px-3 border border-black">{item.name}</td>
                                                <td className="py-2 px-3 border border-black">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <button
                                                            onClick={() => handleCartQuantityChange(idx, -1)}
                                                            className="w-6 h-6 rounded-full border border-red-600 text-red-600 font-bold flex items-center justify-center hover:bg-red-600 hover:text-white transition"
                                                        >
                                                            -
                                                        </button>
                                                        <span className="w-6 text-center font-semibold">{item.quantity}</span>
                                                        <button
                                                            onClick={() => handleCartQuantityChange(idx, 1)}
                                                            className="w-6 h-6 rounded-full border border-red-600 text-red-600 font-bold flex items-center justify-center hover:bg-red-600 hover:text-white transition"
                                                        >
                                                            +
                                                        </button>
                                                    </div>
                                                </td>
                                                <td className="py-2 px-3 text-right font-bold text-red-600 border border-black">
                                                    {(item.price * item.quantity).toLocaleString('vi-VN')}đ
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colSpan={2} className="py-2 px-3 font-bold text-lg border border-black">Tổng cộng:</td>
                                            <td className="py-2 px-3 text-right font-bold text-lg text-red-600 border border-black">
                                                {cartTotal.toLocaleString('vi-VN')}đ
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <button 
                                    onClick={() => { localStorage.removeItem('delivery_cart'); setDeliveryCart([]); }}
                                    className="mt-4 px-4 py-2 text-sm font-bold rounded border-2 border-red-600 text-red-600 bg-white hover:bg-red-600 hover:text-white transition"
                                >
                                    Xóa tất cả món
                                </button>
                            </div>
                            <div>
                                {checkoutStage === 'info' ? (
                                    <form onSubmit={handleConfirmInfo} className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-semibold mb-1">Địa chỉ giao hàng</label>
                                            <input 
                                                type="text" 
                                                required 
                                                value={address} 
                                                onChange={e => setAddress(e.target.value)} 
                                                className="w-full border p-2 rounded focus:border-red-600 focus:outline-none" 
                                                placeholder="Nhập địa chỉ của bạn..."
                                            />
                                        </div>
                                        <button 
                                            type="submit" 
                                            disabled={!address.trim() || deliveryCart.length === 0} 
                                            className="w-full bg-red-600 text-white font-bold py-3 rounded hover:bg-red-700 transition disabled:opacity-50 mt-4"
                                        >
                                            Xác nhận thông tin đặt hàng
                                        </button>
                                    </form>
                                ) : (
                                    <div className="space-y-4 border rounded p-4 bg-gray-50">
                                        <div className="text-center">
                                            <h3 className="font-bold text-lg mb-2">Thanh toán đơn hàng</h3>
                                            <p className="text-gray-600 text-sm mb-4">Mọi thông tin đã được chốt. Vui lòng tiến hành thanh toán để hoàn tất.</p>
                                        </div>
                                        <button 
                                            onClick={handlePayment}
                                            className="w-full bg-green-600 text-white font-bold py-3 rounded hover:bg-green-700 transition flex items-center justify-center gap-2"
                                        >
                                            <i className="fas fa-credit-card"></i> Thanh toán
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>

                <h2 className="text-3xl font-bold mb-6 text-gray-800">Các đơn đã đặt</h2>

                {/* Confirm Modal */}
                <Modal
                    isOpen={isConfirmModalOpen}
                    title="Xác nhận đặt hàng"
                    onClose={() => setIsConfirmModalOpen(false)}
                    onConfirm={confirmOrder}
                    confirmText="Đồng ý đặt hàng"
                >
                    <p className="text-gray-700">
                        Bạn có chắc chắn muốn đặt hàng? <br/>
                        <span className="font-semibold text-red-600">Lưu ý:</span> Sau khi đồng ý sẽ không thể thay đổi thông tin đơn hàng.
                    </p>
                </Modal>

                {/* Filter Buttons */}
                <div className="flex gap-2 mb-8 overflow-x-auto">
                    {[
                        { id: 'all', label: 'Tất cả' },
                        { id: 'waiting_confirmation', label: 'Đang chờ duyệt' },
                        { id: 'waiting_delivery', label: 'Đang chờ giao hàng' },
                        { id: 'delivered', label: 'Đã giao hàng' }
                    ].map(f => (
                        <button
                            key={f.id}
                            onClick={() => setFilter(f.id)}
                            className={`px-4 py-2 rounded font-semibold whitespace-nowrap ${
                                filter === f.id
                                    ? 'bg-red-600 text-white'
                                    : 'bg-white border border-gray-300 text-gray-700 hover:border-red-600'
                            }`}
                        >
                            {f.label}
                        </button>
                    ))}
                </div>

                {filtered.length === 0 ? (
                    <EmptyState
                        icon="📦"
                        title="Không có đơn giao hàng"
                        description={deliveries.length === 0
                            ? 'Bạn chưa có đơn mang về nào.'
                            : `Chưa có đơn với bộ lọc "${[
                                { id: 'all', label: 'Tất cả' },
                                { id: 'waiting_confirmation', label: 'Đang chờ duyệt' },
                                { id: 'waiting_delivery', label: 'Đang chờ giao hàng' },
                                { id: 'delivered', label: 'Đã giao hàng' }
                            ].find(f => f.id === filter)?.label || filter}"`}
                    />
                ) : (
                    <div className="space-y-4">
                        {filtered.map((bill, idx) => (
                            <Card key={bill.order_id || idx} title={`Đơn hàng ${bill.order_id ? bill.order_id.replace('_', '') : ''}`}>
                                <div className="grid md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <p className="text-sm text-gray-600">Địa chỉ</p>
                                        <p className="font-semibold">{bill.delivery?.address || '—'}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Ngày đặt</p>
                                        <p className="font-semibold">{bill.created_at ? new Date(bill.created_at).toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' }) : '—'}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Tổng tiền</p>
                                        <p className="font-bold text-red-600">{Number(bill.total_price || 0).toLocaleString('vi-VN')}đ</p>
                                    </div>
                                </div>

                                <div className="mt-4">
                                    <Badge variant={getStatusColor(bill)}>
                                        {getStatusLabel(bill)}
                                    </Badge>
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
};

export default DeliveriesPage;