import React, { useState, useEffect } from 'react';
import { billService, extractListData } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge, Button, Modal } from '../../components/Shared';

const OrdersPage = () => {
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedOrder, setSelectedOrder] = useState(null);
    const [showDetailModal, setShowDetailModal] = useState(false);
    const [paymentMethod, setPaymentMethod] = useState('cash');

    useEffect(() => {
        fetchOrders();
    }, []);

    const fetchOrders = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await billService.getBills();
            setOrders(extractListData(response));
        } catch (err) {
            if (err.response?.status === 401) return;
            setError(err.response?.data?.message || 'Không thể tải danh sách đơn hàng. Vui lòng thử lại.');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handlePayment = async () => {
        if (!selectedOrder) return;
        try {
            await billService.processPayment(selectedOrder.bill_id, { payment_method: paymentMethod });
            setShowDetailModal(false);
            setError(null);
            await fetchOrders();
        } catch (err) {
            setError('Lỗi thanh toán');
        }
    };

    const getStatusColor = (status) => {
        const colors = {
            pending: 'warning',
            completed: 'success',
            cancelled: 'danger',
        };
        return colors[status] || 'default';
    };

    if (loading) return <Loading />;

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-6xl mx-auto px-4">
                <h1 className="text-4xl font-bold mb-8 text-red-600">Đơn hàng của tôi</h1>

                {error && <ErrorMessage message={error} onClose={() => setError(null)} />}

                {/* Detail Modal */}
                {selectedOrder && (
                    <Modal
                        isOpen={showDetailModal}
                        title={`Đơn hàng ${selectedOrder.bill_code}`}
                        onClose={() => setShowDetailModal(false)}
                        onConfirm={!selectedOrder.is_paid ? handlePayment : null}
                        confirmText={selectedOrder.is_paid ? 'Đã thanh toán' : 'Thanh toán'}
                    >
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-600">Loại</p>
                                    <Badge variant={selectedOrder.order_type === 'delivery' ? 'info' : 'success'}>
                                        {selectedOrder.order_type === 'delivery' ? 'Giao hàng' : 'Đặt bàn'}
                                    </Badge>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Trạng thái</p>
                                    <Badge variant={getStatusColor(selectedOrder.status)}>
                                        {selectedOrder.status}
                                    </Badge>
                                </div>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600 mb-2">Chi tiết</p>
                                <div className="bg-gray-100 p-4 rounded space-y-2 text-sm">
                                    <div>Tổng tiền: <span className="font-bold">{Number(selectedOrder.total_amount).toLocaleString('vi-VN')}đ</span></div>
                                    <div>Chiết khấu: <span className="font-bold">{Number(selectedOrder.discount_amount || 0).toLocaleString('vi-VN')}đ</span></div>
                                    <div>Phí giao: <span className="font-bold">{Number(selectedOrder.shipping_fee || 0).toLocaleString('vi-VN')}đ</span></div>
                                </div>
                            </div>
                            {!selectedOrder.is_paid && (
                                <div>
                                    <label className="block text-sm font-semibold mb-2">Phương thức thanh toán</label>
                                    <select
                                        value={paymentMethod}
                                        onChange={(e) => setPaymentMethod(e.target.value)}
                                        className="w-full border border-gray-300 rounded px-3 py-2"
                                    >
                                        <option value="cash">Tiền mặt</option>
                                        <option value="momo">Momo</option>
                                        <option value="vnpay">VNPay</option>
                                    </select>
                                </div>
                            )}
                        </div>
                    </Modal>
                )}

                {/* Orders List */}
                {orders.length === 0 ? (
                    <Card>
                        <p className="text-center text-gray-500 py-8">Chưa có đơn hàng nào</p>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        {orders.map(order => (
                            <Card key={order.bill_id} title={`Đơn #${order.bill_code}`}>
                                <div className="grid md:grid-cols-5 gap-4 items-center">
                                    <div>
                                        <p className="text-sm text-gray-600">Ngày</p>
                                        <p className="font-semibold">{new Date(order.created_at).toLocaleDateString('vi-VN')}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Loại</p>
                                        <Badge variant={order.order_type === 'delivery' ? 'info' : 'success'}>
                                            {order.order_type === 'delivery' ? '🚗 Giao hàng' : '🏪 Đặt bàn'}
                                        </Badge>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Thành tiền</p>
                                        <p className="font-bold text-red-600">{Number(order.total_amount).toLocaleString('vi-VN')}đ</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Thanh toán</p>
                                        <Badge variant={order.is_paid ? 'success' : 'warning'}>
                                            {order.is_paid ? '✓ Đã thanh toán' : '⏳ Chờ thanh toán'}
                                        </Badge>
                                    </div>
                                    <Button
                                        onClick={() => {
                                            setSelectedOrder(order);
                                            setShowDetailModal(true);
                                        }}
                                    >
                                        Chi tiết
                                    </Button>
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
};

export default OrdersPage;
