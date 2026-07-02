import React, { useState, useEffect } from 'react';
import { deliveryService, billService, vnpayService, extractListData, userAPI, orderService } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge, EmptyState, Modal } from '../../components/Shared';
import { useAuthContext } from '../../context/AuthContext';

const DELIVERY_SESSION_KEY = 'delivery_checkout_session';

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

    // Payment locking: null | 'vnpay' | 'points'
    const [payingWith, setPayingWith] = useState(null);
    const [createdOrderId, setCreatedOrderId] = useState(null);

    // Points Payment states
    const { user, fetchUser } = useAuthContext();
    const [pointsModalType, setPointsModalType] = useState(null); // 'insufficient' | 'confirm' | null
    const [pointsNeeded, setPointsNeeded] = useState(0);
    const [currentPoints, setCurrentPoints] = useState(0);

    // Lưu trạng thái đã xác nhận vào sessionStorage
    const saveCheckoutSession = (stage, data) => {
        sessionStorage.setItem(DELIVERY_SESSION_KEY, JSON.stringify({ stage, ...data }));
    };

    // Xóa session khi hoàn tất / hủy
    const clearCheckoutSession = () => {
        sessionStorage.removeItem(DELIVERY_SESSION_KEY);
    };

    useEffect(() => {
        fetchDeliveries();
        const cart = JSON.parse(localStorage.getItem('delivery_cart')) || [];
        setDeliveryCart(cart);

        // Khôi phục session nếu đã xác nhận thông tin trước đó
        const savedSession = sessionStorage.getItem(DELIVERY_SESSION_KEY);
        if (savedSession) {
            try {
                const session = JSON.parse(savedSession);
                if (session.stage === 'payment') {
                    setCheckoutStage('payment');
                    if (session.address) setAddress(session.address);
                    if (session.orderId) setCreatedOrderId(session.orderId);
                }
            } catch (e) {
                sessionStorage.removeItem(DELIVERY_SESSION_KEY);
            }
        }
    }, []);

    const cartTotal = deliveryCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    const handleConfirmInfo = (e) => {
        e.preventDefault();
        if (deliveryCart.length === 0 || !address.trim()) return;
        setIsConfirmModalOpen(true);
    };

    const confirmOrder = async () => {
        setIsConfirmModalOpen(false);
        try {
            const orderData = {
                order_type: 'delivery',
                delivery: {
                    address: address,
                    phone: 'N/A',
                },
                items: deliveryCart.map(item => ({
                    dish_id: item.dish_id,
                    quantity: item.quantity,
                    price_at_order: item.price,
                })),
            };
            const orderRes = await orderService.storeOrder(orderData);
            const orderId = orderRes.data.data.order_id;
            setCreatedOrderId(orderId);

            // Lưu session để khóa trạng thái
            saveCheckoutSession('payment', { address, orderId });
            setCheckoutStage('payment');
        } catch (err) {
            setError(err.response?.data?.message || 'Lỗi tạo đơn hàng');
        }
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
        if (payingWith) return; // chặn double-click
        setPayingWith('vnpay');
        try {
            // 2. Lấy URL thanh toán VNPay
            const vnpayRes = await vnpayService.createPaymentUrl({
                order_id: createdOrderId,
                amount: cartTotal,
                order_type: 'delivery',
            });

            // 3. Xóa session vì sẽ chuyển sang trang VNPay
            clearCheckoutSession();
            // 4. Redirect sang VNPay
            window.location.href = vnpayRes.data.payment_url;

        } catch (err) {
            setError(err.response?.data?.message || 'Lỗi đặt hàng');
            setPayingWith(null);
        }
    };

    const handlePointsPaymentClick = async () => {
        try {
            // Lấy điểm mới nhất từ server thay vì cache ở AuthContext (localStorage)
            const res = await userAPI.getProfile();
            const freshPoints = res.data?.data?.points || 0;
            setCurrentPoints(freshPoints);

            const needed = Math.floor(cartTotal / 100);
            setPointsNeeded(needed);

            if (freshPoints < needed) {
                setPointsModalType('insufficient');
            } else {
                setPointsModalType('confirm');
            }
        } catch (err) {
            setError('Không thể lấy thông tin điểm: ' + (err.response?.data?.message || err.message));
        }
    };

    const handleConfirmPointsPayment = async () => {
        if (payingWith) return; // chặn double-click
        setPayingWith('points');
        try {
            // 2. Thanh toán bằng điểm
            const res = await orderService.payWithPoints(createdOrderId);
            const billId = res.data.data.bill_id;

            // 3. Xóa cart, session và redirect
            localStorage.removeItem('delivery_cart');
            clearCheckoutSession();
            setDeliveryCart([]);
            setPointsModalType(null);
            if (fetchUser) fetchUser(); // update user points
            window.location.href = `/payment-result?status=success&code=00&order_type=delivery&bill_id=${billId}`;
        } catch (err) {
            setError('Lỗi thanh toán bằng điểm: ' + (err.response?.data?.message || err.message));
            setPointsModalType(null);
            setPayingWith(null);
        }
    };

    const checkMembershipDowngrade = () => {
        if (!currentPoints) return false;

        // Bước 1: Tính bậc hiện tại từ số điểm đang có
        let currentMembership = 'bronze';
        if (currentPoints >= 10000) currentMembership = 'diamond';
        else if (currentPoints >= 6000) currentMembership = 'platinum';
        else if (currentPoints >= 3000) currentMembership = 'gold';
        else if (currentPoints >= 1000) currentMembership = 'silver';

        // Bước 2: Ngưỡng điểm tối thiểu để duy trì bậc đó
        const threshold = { bronze: 0, silver: 1000, gold: 3000, platinum: 6000, diamond: 10000 };
        const minRequired = threshold[currentMembership] ?? 0;

        // Bước 3: Điểm còn lại sau khi thanh toán
        const newPoints = currentPoints - pointsNeeded;

        // Hạ bậc nếu điểm còn lại dưới ngưỡng tối thiểu
        return newPoints < minRequired;
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
                                        {/* Nút VNPay */}
                                        <button
                                            onClick={handlePayment}
                                            disabled={!!payingWith}
                                            className={`w-full font-bold py-3 rounded transition flex items-center justify-center gap-2 mb-3 ${payingWith === 'vnpay'
                                                ? 'bg-blue-600 text-white cursor-not-allowed'
                                                : payingWith === 'points'
                                                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                                    : 'bg-blue-600 text-white hover:bg-blue-700'
                                                }`}
                                        >
                                            {payingWith === 'vnpay' ? (
                                                <><i className="fas fa-spinner fa-spin"></i> Đang xử lý...</>
                                            ) : (
                                                <><i className="fas fa-credit-card"></i> Thanh toán bằng VNPay</>
                                            )}
                                        </button>
                                        {/* Nút Điểm */}
                                        <button
                                            onClick={handlePointsPaymentClick}
                                            disabled={!!payingWith}
                                            className={`w-full font-bold py-3 rounded transition flex items-center justify-center gap-2 ${payingWith === 'points'
                                                ? 'bg-green-600 text-white cursor-not-allowed'
                                                : payingWith === 'vnpay'
                                                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                                    : 'bg-green-600 text-white hover:bg-green-700'
                                                }`}
                                        >
                                            {payingWith === 'points' ? (
                                                <><i className="fas fa-spinner fa-spin"></i> Đang xử lý...</>
                                            ) : (
                                                <><i className="fas fa-coins"></i> Thanh toán bằng Điểm</>
                                            )}
                                        </button>
                                        {/* Nút quay lại - chỉ khi chưa bấm thanh toán */}
                                        <button
                                            onClick={() => { clearCheckoutSession(); setCheckoutStage('info'); }}
                                            disabled={!!payingWith}
                                            className="w-full mt-2 text-gray-500 hover:text-red-600 text-sm font-bold py-2 disabled:opacity-40 disabled:cursor-not-allowed"
                                        >
                                            Quay lại sửa thông tin
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
                        Bạn có chắc chắn muốn đặt hàng? <br />
                        <span className="font-semibold text-red-600">Lưu ý:</span> Sau khi đồng ý sẽ không thể thay đổi thông tin đơn hàng.
                    </p>
                </Modal>

                {/* Points Modal: Insufficient */}
                {pointsModalType === 'insufficient' && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                        <div className="bg-white rounded-lg w-full max-w-md p-6 relative">
                            <button
                                onClick={() => setPointsModalType(null)}
                                className="absolute top-4 right-4 text-gray-500 hover:text-gray-700"
                            >
                                <i className="fas fa-times text-xl"></i>
                            </button>
                            <h3 className="text-xl font-bold text-red-600 mb-4 text-center">Không đủ điểm</h3>
                            <p className="text-gray-700 text-center mb-6">
                                Bạn không có đủ điểm để thanh toán hóa đơn này. <br />
                                Hiện bạn đang có <strong className="text-red-600">{currentPoints}</strong> điểm.
                            </p>
                            <button
                                onClick={() => setPointsModalType(null)}
                                className="w-full bg-red-600 text-white font-bold py-2 rounded hover:bg-red-700"
                            >
                                Ok
                            </button>
                        </div>
                    </div>
                )}

                {/* Points Modal: Confirm */}
                {pointsModalType === 'confirm' && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                        <div className="bg-white rounded-lg w-full max-w-md p-6 relative">
                            <button
                                onClick={() => setPointsModalType(null)}
                                className="absolute top-4 right-4 text-gray-500 hover:text-gray-700"
                            >
                                <i className="fas fa-times text-xl"></i>
                            </button>
                            <h3 className="text-xl font-bold text-gray-800 mb-4 text-center">Xác nhận thanh toán</h3>
                            <p className="text-gray-700 text-center mb-4">
                                Bạn có chắc muốn dùng điểm để thanh toán hóa đơn này? <br />
                                Hiện bạn đang có <strong className="text-red-600">{currentPoints}</strong> điểm.
                            </p>
                            {checkMembershipDowngrade() && (
                                <p className="text-yellow-700 bg-yellow-50 border border-yellow-200 p-2 rounded text-sm text-center mb-4">
                                    ⚠️ Thanh toán hóa đơn này có thể khiến bạn bị hạ bậc thành viên.
                                </p>
                            )}
                            <div className="flex gap-4">
                                <button
                                    onClick={() => setPointsModalType(null)}
                                    className="flex-1 bg-gray-200 text-gray-800 font-bold py-2 rounded hover:bg-gray-300"
                                >
                                    Hủy
                                </button>
                                <button
                                    onClick={handleConfirmPointsPayment}
                                    className="flex-1 bg-red-600 text-white font-bold py-2 rounded hover:bg-red-700"
                                >
                                    Xác nhận
                                </button>
                            </div>
                        </div>
                    </div>
                )}

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
                            className={`px-4 py-2 rounded font-semibold whitespace-nowrap ${filter === f.id
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
                            <Card key={bill.order_id || idx} title={`Đơn hàng ${bill.order_id || ''}`}>
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
                                        <p className="text-sm text-gray-600">Phương thức thanh toán</p>
                                        <p className="font-bold text-red-600">
                                            {bill.payment_method === 'Points' ? 'Điểm' : bill.payment_method === 'vnpay' ? 'VNPay' : (bill.payment_method || '—')}
                                        </p>
                                    </div>
                                </div>

                                {/* Chi tiết thanh toán */}
                                {bill.subtotal_price != null && (
                                    <div className="mt-2 text-sm space-y-1">
                                        <div className="flex justify-between text-gray-600">
                                            <span>Tổng tiền:</span>
                                            <span className="font-semibold">{Number(bill.subtotal_price).toLocaleString('vi-VN')}đ</span>
                                        </div>
                                        {bill.payment_method === 'Points' ? (
                                            <>
                                                <div className="flex justify-between text-green-700">
                                                    <span>Đã thanh toán bằng điểm</span>
                                                    <span className="font-semibold">-{Number(bill.subtotal_price).toLocaleString('vi-VN')}đ</span>
                                                </div>
                                                <div className="flex justify-between font-bold text-gray-800">
                                                    <span>Số tiền đã trả:</span>
                                                    <span className="text-red-600">{Number(bill.total_price || 0).toLocaleString('vi-VN')}đ</span>
                                                </div>
                                            </>
                                        ) : bill.payment_method === 'vnpay' ? (
                                            <div className="flex justify-between font-bold text-gray-800">
                                                <span>Số tiền đã trả:</span>
                                                <span className="text-red-600">{Number(bill.total_price || 0).toLocaleString('vi-VN')}đ</span>
                                            </div>
                                        ) : (
                                            <div className="flex justify-between font-bold text-gray-800">
                                                <span>Số tiền cần trả:</span>
                                                <span className="text-red-600">{Number(bill.total_price || 0).toLocaleString('vi-VN')}đ</span>
                                            </div>
                                        )}
                                    </div>
                                )}

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