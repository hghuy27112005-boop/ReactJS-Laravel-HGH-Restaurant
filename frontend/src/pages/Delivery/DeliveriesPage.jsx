import React, { useState, useEffect } from 'react';
import { deliveryService, billService, extractListData } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge, EmptyState, Modal } from '../../components/Shared';

const DeliveriesPage = () => {
    const [deliveries, setDeliveries] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filter, setFilter] = useState('all');

    // Cart states
    const [deliveryCart, setDeliveryCart] = useState([]);
    const [address, setAddress] = useState('');
    const [checkoutStage, setCheckoutStage] = useState('info'); // info, payment, processing
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

    const handlePayment = async () => {
        setCheckoutStage('processing');
        
        // Giả bộ load load 1 tí (2 giây)
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        try {
            const orderData = {
                order_type: 'delivery',
                delivery: {
                    address: address,
                    phone: 'N/A', // Backend requires delivery.phone
                },
                payment_method: 'cash',
                items: deliveryCart.map(item => ({
                    dish_id: item.dish_id,
                    quantity: item.quantity,
                    price_at_order: item.price,
                })),
                total_amount: cartTotal,
            };

            await billService.storeBill(orderData);
            
            localStorage.removeItem('delivery_cart');
            setDeliveryCart([]);
            setAddress('');
            setCheckoutStage('info');
            
            fetchDeliveries();
            alert('Thanh toán thành công! Đơn hàng của bạn đã được chuyển xuống danh sách chờ.');
        } catch (err) {
            setError(err.response?.data?.message || 'Lỗi đặt hàng');
            setCheckoutStage('payment'); // Revert back to payment if failed
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
                    <h2 className="text-2xl font-bold mb-4"><i className="fas fa-motorcycle"></i> Quầy đặt hàng (Ship)</h2>
                    
                    {deliveryCart.length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            <i className="fas fa-shopping-basket text-4xl mb-3 text-gray-300 block"></i>
                            <p className="text-lg">Quầy đang trống, vui lòng quay lại Menu thêm món vào giỏ hàng.</p>
                        </div>
                    ) : (
                        <div className="grid md:grid-cols-2 gap-8">
                            <div>
                                <h3 className="font-semibold mb-2">Món đã chọn:</h3>
                                <ul className="space-y-2 mb-4">
                                    {deliveryCart.map((item, idx) => (
                                        <li key={idx} className="flex justify-between border-b pb-2">
                                            <span>{item.name} x{item.quantity}</span>
                                            <span className="font-bold text-red-600">{(item.price * item.quantity).toLocaleString('vi-VN')}đ</span>
                                        </li>
                                    ))}
                                </ul>
                                <div className="text-xl font-bold flex justify-between mt-4 border-t pt-4">
                                    <span>Tổng cộng:</span>
                                    <span className="text-red-600">{cartTotal.toLocaleString('vi-VN')}đ</span>
                                </div>
                                <button 
                                    onClick={() => { localStorage.removeItem('delivery_cart'); setDeliveryCart([]); }}
                                    className="mt-4 text-sm text-red-500 hover:underline"
                                >
                                    Xóa giỏ hàng
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
                                            disabled={checkoutStage === 'processing'} 
                                            className="w-full bg-green-600 text-white font-bold py-3 rounded hover:bg-green-700 transition disabled:opacity-50 flex items-center justify-center gap-2"
                                        >
                                            {checkoutStage === 'processing' ? (
                                                <><i className="fas fa-spinner fa-spin"></i> Đang xử lý thanh toán...</>
                                            ) : (
                                                <><i className="fas fa-credit-card"></i> Thanh toán</>
                                            )}
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
                                        <p className="font-bold text-red-600">{Number(bill.subtotal_price || 0).toLocaleString('vi-VN')}đ</p>
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
