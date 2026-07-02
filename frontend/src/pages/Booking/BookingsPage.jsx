import React, { useState, useEffect } from 'react';
import { bookingService, billService, vnpayService, extractListData, userAPI, orderService } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge, EmptyState, Modal } from '../../components/Shared';
import { useAuthContext } from '../../context/AuthContext';


const BOOKING_SESSION_KEY = 'booking_checkout_session';

const BookingsPage = () => {
    const [bookings, setBookings] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Cart states
    const [bookingCart, setBookingCart] = useState([]);

    const [checkoutStage, setCheckoutStage] = useState('wizard'); // wizard, payment, processing
    const [wizardStep, setWizardStep] = useState(1);

    // Payment locking: null | 'vnpay' | 'points'
    const [payingWith, setPayingWith] = useState(null);
    const [createdOrderId, setCreatedOrderId] = useState(null);

    // Points Payment states
    const { user, fetchUser } = useAuthContext();
    const [pointsModalType, setPointsModalType] = useState(null); // 'insufficient' | 'confirm' | null
    const [pointsNeeded, setPointsNeeded] = useState(0);
    const [currentPoints, setCurrentPoints] = useState(0);

    // Booking Form State
    const [bookingDate, setBookingDate] = useState('');
    const [startH, setStartH] = useState('07');
    const [startM, setStartM] = useState('00');
    const [endH, setEndH] = useState('');
    const [endM, setEndM] = useState('');

    const [totalTables, setTotalTables] = useState(1);
    const [tableTypes, setTableTypes] = useState({ type5: 1, type10: 0, type15: 0 });
    const [selectedTables, setSelectedTables] = useState([]);

    // Availability
    const [unavailableTables, setUnavailableTables] = useState([]);
    const [isCheckingOverlap, setIsCheckingOverlap] = useState(false);

    // Lưu trạng thái đã xác nhận vào sessionStorage
    const saveCheckoutSession = (stage, formData) => {
        sessionStorage.setItem(BOOKING_SESSION_KEY, JSON.stringify({ stage, ...formData }));
    };

    // Xóa session khi hoàn tất / hủy
    const clearCheckoutSession = () => {
        sessionStorage.removeItem(BOOKING_SESSION_KEY);
    };

    useEffect(() => {
        fetchBookings();
        const cart = JSON.parse(localStorage.getItem('booking_cart')) || [];
        setBookingCart(cart);

        // Khôi phục session nếu đã xác nhận thông tin trước đó
        const savedSession = sessionStorage.getItem(BOOKING_SESSION_KEY);
        if (savedSession) {
            try {
                const session = JSON.parse(savedSession);
                if (session.stage === 'payment') {
                    setCheckoutStage('payment');
                    setWizardStep(5);
                    if (session.bookingDate) setBookingDate(session.bookingDate);
                    if (session.startH) setStartH(session.startH);
                    if (session.startM) setStartM(session.startM);
                    if (session.endH) setEndH(session.endH);
                    if (session.endM) setEndM(session.endM);
                    if (session.totalTables) setTotalTables(session.totalTables);
                    if (session.tableTypes) setTableTypes(session.tableTypes);
                    if (session.selectedTables) setSelectedTables(session.selectedTables);
                    if (session.orderId) setCreatedOrderId(session.orderId);
                }
            } catch (e) {
                sessionStorage.removeItem(BOOKING_SESSION_KEY);
            }
        } else {
            // Set default date to today only nếu không có session
            const today = new Date().toISOString().split('T')[0];
            setBookingDate(today);
        }
    }, []);

    const cartTotal = bookingCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    const handleNextStep = async () => {
        if (wizardStep === 1) {
            if (!bookingDate || !startH || !startM || !endH || !endM) {
                alert('Vui lòng chọn ngày và nhập đầy đủ giờ đến, giờ về.');
                return;
            }

            const hrRegex = /^([0-1]?[0-9]|2[0-3])$/;
            const minRegex = /^[0-5]?[0-9]$/;
            if (!hrRegex.test(startH) || !minRegex.test(startM) || !hrRegex.test(endH) || !minRegex.test(endM)) {
                alert('Định dạng giờ không hợp lệ.');
                return;
            }

            const sH = parseInt(startH);
            const sM = parseInt(startM);
            const eH = parseInt(endH);
            const eM = parseInt(endM);

            const startMinutes = sH * 60 + sM;
            const endMinutes = eH * 60 + eM;

            if (startMinutes < 7 * 60) {
                alert('Giờ mở cửa là từ 07:00 sáng. Vui lòng chọn giờ khác.');
                return;
            }
            if (endMinutes > 22 * 60) {
                alert('Giờ đóng cửa là 22:00. Vui lòng nhập giờ về không quá 22:00.');
                return;
            }
            if (endMinutes <= startMinutes) {
                alert('Giờ về phải sau giờ đến.');
                return;
            }
            if (endMinutes - startMinutes > 90) {
                alert('Chỉ được đặt bàn tối đa 90 phút. Vui lòng chỉnh lại thời gian.');
                return;
            }

            setWizardStep(2);
        } else if (wizardStep === 2) {
            setWizardStep(3);
        } else if (wizardStep === 3) {
            const currentTotal = tableTypes.type5 + tableTypes.type10 + tableTypes.type15;
            if (currentTotal !== totalTables) {
                alert(`Vui lòng chọn đúng ${totalTables} bàn. Bạn đang chọn ${currentTotal} bàn.`);
                return;
            }
            setWizardStep(4);
        } else if (wizardStep === 4) {
            if (selectedTables.length !== totalTables) {
                alert(`Vui lòng chọn đủ ${totalTables} bàn.`);
                return;
            }
            setWizardStep(5);
        } else if (wizardStep === 5) {
            try {
                const orderData = {
                    order_type: 'booking_table',
                    booking_table: {
                        tables: selectedTables,
                        start_date: bookingDate,
                        start_time: `${String(startH).padStart(2, '0')}:${String(startM).padStart(2, '0')}`,
                        end_time: `${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}`
                    },
                    items: bookingCart.map(item => ({
                        dish_id: item.dish_id,
                        quantity: item.quantity,
                    })),
                };
                const orderRes = await orderService.storeOrder(orderData);
                const orderId = orderRes.data.data.order_id;
                setCreatedOrderId(orderId);

                // Lưu toàn bộ trạng thái vào sessionStorage để khóa
                saveCheckoutSession('payment', {
                    bookingDate,
                    startH,
                    startM,
                    endH,
                    endM,
                    totalTables,
                    tableTypes,
                    selectedTables,
                    orderId,
                });
                setCheckoutStage('payment');
            } catch (err) {
                setError(err.response?.data?.message || 'Lỗi tạo đơn hàng');
            }
        }
    };

    const handlePrevStep = () => {
        if (wizardStep > 1) {
            setWizardStep(wizardStep - 1);
        }
    };

    const handleTableTypeChange = (type, amount) => {
        setTableTypes(prev => {
            const newValue = prev[type] + amount;
            if (newValue < 0) return prev;

            const newTypes = { ...prev, [type]: newValue };
            const newTotal = newTypes.type5 + newTypes.type10 + newTypes.type15;
            if (newTotal > totalTables) return prev;

            setSelectedTables([]); // reset bàn đã chọn vì loại bàn vừa thay đổi
            return newTypes;
        });
    };

    const handleCartQuantityChange = (idx, amount) => {
        setBookingCart(prev => {
            const updated = prev
                .map((item, i) => {
                    if (i !== idx) return item;
                    const newQty = item.quantity + amount;
                    if (newQty < 0) return item; // không cho âm
                    return { ...item, quantity: newQty };
                })
                .filter(item => item.quantity > 0); // tự xóa món khi về 0

            localStorage.setItem('booking_cart', JSON.stringify(updated));
            return updated;
        });
    };

    const handleTableSelect = async (tableNum) => {
        if (selectedTables.includes(tableNum)) {
            setSelectedTables(selectedTables.filter(t => t !== tableNum));
        } else {
            if (selectedTables.length >= totalTables) {
                setError(`Bạn chỉ được chọn tối đa ${totalTables} bàn.`);
                return;
            }

            try {
                await bookingService.checkOverlap({
                    date: bookingDate,
                    start_time: `${String(startH).padStart(2, '0')}:${String(startM).padStart(2, '0')}`,
                    end_time: `${String(endH).padStart(2, '0')}:${String(endM).padStart(2, '0')}`,
                    tables: [tableNum]
                });
                setSelectedTables([...selectedTables, tableNum]);
            } catch (err) {
                setError(err.response?.data?.message || `Bàn số ${tableNum} đã có người đặt trong khung giờ này.`);
            }
        }
    };

    const handlePayment = async () => {
        if (payingWith) return; // chặn double-click
        setPayingWith('vnpay');

        try {
            // 2. Lấy URL thanh toán VNPay
            const vnpayRes = await vnpayService.createPaymentUrl({
                order_id: createdOrderId,
                amount: cartTotal,
                order_type: 'booking_table',
            });

            // 3. Xóa session vì sẽ chuyển sang trang VNPay
            clearCheckoutSession();
            // 4. Redirect sang VNPay — dọn cart sau khi quay về ở PaymentResultPage
            window.location.href = vnpayRes.data.payment_url;

        } catch (err) {
            setError(err.response?.data?.message || 'Lỗi đặt bàn');
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
            localStorage.removeItem('booking_cart');
            clearCheckoutSession();
            setBookingCart([]);
            setPointsModalType(null);
            if (fetchUser) fetchUser(); // update user points
            window.location.href = `/payment-result?status=success&code=00&order_type=booking_table&bill_id=${billId}`;
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

    const fetchBookings = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await bookingService.getBookings();
            setBookings(extractListData(response));
        } catch (err) {
            if (err.response?.status === 401) return;
            setError(err.response?.data?.message || 'Không thể tải danh sách đặt bàn. Vui lòng thử lại.');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <Loading />;

    // Extract HH:MM from any format: "HH:MM", "YYYY-MM-DD HH:MM:SS", or "YYYY-MM-DDTHH:MM:SS.000Z"
    const formatTime = (t) => {
        if (!t) return '';
        // ISO format with T: "2026-06-20T07:30:00..."
        if (t.includes('T')) {
            return t.split('T')[1].substring(0, 5);
        }
        // Space-separated datetime: "2026-06-20 07:30:00"
        if (t.includes(' ') && t.length > 8) {
            return t.split(' ')[1].substring(0, 5);
        }
        // Pure time: "07:30" or "07:30:00"
        return t.substring(0, 5);
    };

    const formatted = bookings;

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-6xl mx-auto px-4">
                <h1 className="text-4xl font-bold mb-8 text-red-600">Đặt bàn</h1>

                {error && <ErrorMessage message={error} onClose={() => setError(null)} />}

                <div className="bg-white rounded-lg shadow p-6 mb-8 border-t-4 border-red-600">
                    <h2 className="text-2xl font-bold mb-4"> Góc đặt bàn</h2>

                    {bookingCart.length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            <i className="fas fa-shopping-basket text-4xl mb-3 text-gray-300 block"></i>
                            <p className="text-lg">Giỏ hàng trống, vui lòng quay lại Menu thêm món vào giỏ với hình thức "Ăn tại quán".</p>
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
                                        {bookingCart.map((item, idx) => (
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
                                            <td colSpan={2} className="py-2 px-3 font-bold text-lg border border-black">Tổng tiền món:</td>
                                            <td className="py-2 px-3 text-right font-bold text-lg text-red-600 border border-black">
                                                {cartTotal.toLocaleString('vi-VN')}đ
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <button
                                    onClick={() => { localStorage.removeItem('booking_cart'); setBookingCart([]); }}
                                    className="mt-4 px-4 py-2 text-sm font-bold rounded border-2 border-red-600 text-red-600 bg-white hover:bg-red-600 hover:text-white transition"
                                >
                                    Xóa tất cả món
                                </button>
                            </div>

                            <div>
                                {checkoutStage === 'wizard' ? (
                                    <div className="border-2 border-red-600 rounded-lg p-4 bg-white">
                                        <div className="flex justify-between mb-6 relative">
                                            <div className="absolute top-1/2 left-0 w-full h-1 bg-gray-200 -z-10 -translate-y-1/2"></div>
                                            {[1, 2, 3, 4, 5].map(step => (
                                                <div key={step} className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm ${wizardStep === step ? 'bg-red-600 text-white border-2 border-red-600' : wizardStep > step ? 'bg-red-200 text-red-800 border-2 border-red-200' : 'bg-white text-gray-400 border-2 border-gray-200'}`}>
                                                    {step}
                                                </div>
                                            ))}
                                        </div>

                                        {/* Step 1: Date & Time */}
                                        {wizardStep === 1 && (
                                            <div className="animate-fade-in">
                                                <h3 className="font-bold text-lg text-red-600 mb-2">Bước 1: Ngày & Giờ</h3>
                                                <p className="text-gray-600 mb-4 text-sm">Giờ hoạt động: 07:00 - 22:00. <strong className="text-red-600">Lưu ý chỉ được đặt bàn tối đa 90'.</strong></p>
                                                <div className="space-y-4 mb-6">
                                                    <div>
                                                        <label className="block text-sm font-semibold mb-1">Ngày đặt</label>
                                                        <input type="date" value={bookingDate} onChange={e => setBookingDate(e.target.value)} className="w-full border p-2 rounded" />
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <label className="block text-sm font-semibold mb-1">Giờ đến</label>
                                                            <div className="flex items-center gap-1 border p-2 rounded bg-white w-fit focus-within:ring-2 focus-within:ring-red-500">
                                                                <input type="text" maxLength="2" placeholder="07" value={startH} onChange={e => setStartH(e.target.value)} className="w-10 text-center outline-none bg-transparent" />
                                                                <span className="font-bold text-gray-500">:</span>
                                                                <input type="text" maxLength="2" placeholder="00" value={startM} onChange={e => setStartM(e.target.value)} className="w-10 text-center outline-none bg-transparent" />
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <label className="block text-sm font-semibold mb-1">Giờ về</label>
                                                            <div className="flex items-center gap-1 border p-2 rounded bg-white w-fit focus-within:ring-2 focus-within:ring-red-500">
                                                                <input type="text" maxLength="2" placeholder="08" value={endH} onChange={e => setEndH(e.target.value)} className="w-10 text-center outline-none bg-transparent" />
                                                                <span className="font-bold text-gray-500">:</span>
                                                                <input type="text" maxLength="2" placeholder="30" value={endM} onChange={e => setEndM(e.target.value)} className="w-10 text-center outline-none bg-transparent" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {/* Step 2: Total Tables */}
                                        {wizardStep === 2 && (
                                            <div className="animate-fade-in">
                                                <h3 className="font-bold text-lg text-red-600 mb-2">Bước 2: Số lượng bàn</h3>
                                                <p className="text-gray-600 mb-4 text-sm">Bạn muốn đặt bao nhiêu bàn? (Tối đa 3 bàn)</p>
                                                <div className="flex justify-center gap-4 mb-6">
                                                    {[1, 2, 3].map(num => (
                                                        <button
                                                            key={num}
                                                            onClick={() => {
                                                                setTotalTables(num);
                                                                if (num === 1) setTableTypes({ type5: 1, type10: 0, type15: 0 });
                                                                if (num === 2) setTableTypes({ type5: 2, type10: 0, type15: 0 });
                                                                if (num === 3) setTableTypes({ type5: 3, type10: 0, type15: 0 });
                                                                setSelectedTables([]); // reset bàn đã chọn vì tổng số bàn vừa thay đổi
                                                            }}
                                                            className={`w-16 h-16 text-2xl font-bold rounded-lg border-2 ${totalTables === num ? 'border-red-600 text-red-600 bg-red-50' : 'border-gray-200 text-gray-400 hover:border-red-300'}`}
                                                        >
                                                            {num}
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        {/* Step 3: Table Types */}
                                        {wizardStep === 3 && (
                                            <div className="animate-fade-in">
                                                <h3 className="font-bold text-lg text-red-600 mb-2">Bước 3: Loại bàn</h3>
                                                <p className="text-gray-600 mb-4 text-sm">Phân bổ số lượng theo sức chứa. Tổng: <b>{totalTables}</b> bàn.</p>

                                                <div className="space-y-3 mb-6">
                                                    <div className="flex justify-between items-center bg-white p-3 border rounded-lg">
                                                        <div>
                                                            <div className="font-bold">Bàn nhỏ (&lt;= 5 người)</div>
                                                            <div className="text-xs text-gray-500">STT từ 1 - 25</div>
                                                        </div>
                                                        <div className="flex items-center gap-3">
                                                            <button onClick={() => handleTableTypeChange('type5', -1)} className="w-8 h-8 rounded-full border border-red-600 text-red-600 font-bold">-</button>
                                                            <span className="font-bold w-4 text-center">{tableTypes.type5}</span>
                                                            <button onClick={() => handleTableTypeChange('type5', 1)} className="w-8 h-8 rounded-full border border-red-600 text-red-600 font-bold">+</button>
                                                        </div>
                                                    </div>
                                                    <div className="flex justify-between items-center bg-white p-3 border rounded-lg">
                                                        <div>
                                                            <div className="font-bold">Bàn vừa (&lt;= 10 người)</div>
                                                            <div className="text-xs text-gray-500">STT từ 26 - 45</div>
                                                        </div>
                                                        <div className="flex items-center gap-3">
                                                            <button onClick={() => handleTableTypeChange('type10', -1)} className="w-8 h-8 rounded-full border border-red-600 text-red-600 font-bold">-</button>
                                                            <span className="font-bold w-4 text-center">{tableTypes.type10}</span>
                                                            <button onClick={() => handleTableTypeChange('type10', 1)} className="w-8 h-8 rounded-full border border-red-600 text-red-600 font-bold">+</button>
                                                        </div>
                                                    </div>
                                                    <div className="flex justify-between items-center bg-white p-3 border rounded-lg">
                                                        <div>
                                                            <div className="font-bold">Bàn lớn (&lt;= 15 người)</div>
                                                            <div className="text-xs text-gray-500">STT từ 46 - 50</div>
                                                        </div>
                                                        <div className="flex items-center gap-3">
                                                            <button onClick={() => handleTableTypeChange('type15', -1)} className="w-8 h-8 rounded-full border border-red-600 text-red-600 font-bold">-</button>
                                                            <span className="font-bold w-4 text-center">{tableTypes.type15}</span>
                                                            <button onClick={() => handleTableTypeChange('type15', 1)} className="w-8 h-8 rounded-full border border-red-600 text-red-600 font-bold">+</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {/* Step 4: Select Tables */}
                                        {wizardStep === 4 && (
                                            <div className="animate-fade-in">
                                                <h3 className="font-bold text-lg text-red-600 mb-2">Bước 4: Chọn bàn trống</h3>
                                                <p className="text-gray-600 mb-2 text-sm">Vui lòng chọn <b>{totalTables}</b> bàn. Đã chọn {selectedTables.length}/{totalTables}.</p>

                                                <div className="max-h-60 overflow-y-auto border p-2 bg-white rounded">
                                                    {tableTypes.type5 > 0 && (
                                                        <div className="mb-4">
                                                            <div className="font-bold text-sm mb-2 text-gray-700 border-b pb-1">Bàn nhỏ (1-25) - Cần chọn {tableTypes.type5} bàn</div>
                                                            <div className="grid grid-cols-5 gap-2">
                                                                {Array.from({ length: 25 }, (_, i) => i + 1).map(num => (
                                                                    <button
                                                                        key={num}
                                                                        onClick={() => handleTableSelect(num)}
                                                                        className={`p-2 text-xs border rounded transition ${selectedTables.includes(num) ? 'bg-red-600 text-white border-red-600 font-bold' : 'bg-white hover:border-red-600'}`}
                                                                    >
                                                                        {num}
                                                                    </button>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}

                                                    {tableTypes.type10 > 0 && (
                                                        <div className="mb-4">
                                                            <div className="font-bold text-sm mb-2 text-gray-700 border-b pb-1">Bàn vừa (26-45) - Cần chọn {tableTypes.type10} bàn</div>
                                                            <div className="grid grid-cols-5 gap-2">
                                                                {Array.from({ length: 20 }, (_, i) => i + 26).map(num => (
                                                                    <button
                                                                        key={num}
                                                                        onClick={() => handleTableSelect(num)}
                                                                        className={`p-2 text-xs border rounded transition ${selectedTables.includes(num) ? 'bg-red-600 text-white border-red-600 font-bold' : 'bg-white hover:border-red-600'}`}
                                                                    >
                                                                        {num}
                                                                    </button>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}

                                                    {tableTypes.type15 > 0 && (
                                                        <div className="mb-4">
                                                            <div className="font-bold text-sm mb-2 text-gray-700 border-b pb-1">Bàn lớn (46-50) - Cần chọn {tableTypes.type15} bàn</div>
                                                            <div className="grid grid-cols-5 gap-2">
                                                                {Array.from({ length: 5 }, (_, i) => i + 46).map(num => (
                                                                    <button
                                                                        key={num}
                                                                        onClick={() => handleTableSelect(num)}
                                                                        className={`p-2 text-xs border rounded transition ${selectedTables.includes(num) ? 'bg-red-600 text-white border-red-600 font-bold' : 'bg-white hover:border-red-600'}`}
                                                                    >
                                                                        {num}
                                                                    </button>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        )}

                                        {/* Step 5: Confirm */}
                                        {wizardStep === 5 && (
                                            <div className="animate-fade-in">
                                                <h3 className="font-bold text-lg text-red-600 mb-2">Bước 5: Xác nhận & Thanh toán</h3>
                                                <div className="bg-white p-4 border rounded-lg mb-6 text-sm space-y-2">
                                                    <div className="flex justify-between border-b pb-2">
                                                        <span className="text-gray-600">Ngày đặt:</span>
                                                        <span className="font-bold">{bookingDate}</span>
                                                    </div>
                                                    <div className="flex justify-between border-b pb-2">
                                                        <span className="text-gray-600">Thời gian:</span>
                                                        <span className="font-bold">{String(startH).padStart(2, '0')}:{String(startM).padStart(2, '0')} - {String(endH).padStart(2, '0')}:{String(endM).padStart(2, '0')}</span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="text-gray-600">Bàn đã chọn:</span>
                                                        <span className="font-bold text-red-600">Bàn {selectedTables.join(', ')}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        <div className="flex justify-between mt-4 border-t pt-4">
                                            <button
                                                onClick={handlePrevStep}
                                                className={`px-4 py-2 text-sm font-bold text-gray-600 hover:text-red-600 ${wizardStep === 1 ? 'invisible' : ''}`}
                                            >
                                                Quay lại
                                            </button>
                                            <button
                                                onClick={handleNextStep}
                                                className="bg-red-600 text-white px-6 py-2 rounded font-bold hover:bg-red-700"
                                            >
                                                {wizardStep === 5 ? 'Xác nhận thông tin đặt bàn' : 'Tiếp tục'}
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-4 border rounded p-4 bg-gray-50">
                                        <div className="text-center">
                                            <h3 className="font-bold text-lg mb-2">Thanh toán đơn đặt bàn</h3>
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
                                        <button
                                            onClick={() => { clearCheckoutSession(); setCheckoutStage('wizard'); }}
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

                <h2 className="text-3xl font-bold mb-6 text-gray-800">Các đơn đã đặt</h2>

                {formatted.length === 0 ? (
                    <EmptyState
                        icon="📅"
                        title="Không có đơn đặt bàn"
                        description="Bạn chưa có đơn đặt bàn nào."
                    />
                ) : (
                    <div className="space-y-4">
                        {formatted.map((bill, idx) => {
                            const booking = bill.booking_table;
                            return (
                                <Card key={bill.bill_id || idx} title={`Đơn hàng ${bill.order_id || bill.bill_id || ''}`}>
                                    <div className="grid md:grid-cols-4 gap-4 mb-4">
                                        <div>
                                            <p className="text-sm text-gray-600">Bàn</p>
                                            <p className="font-semibold text-lg">
                                                {booking?.table_numbers?.length > 0
                                                    ? booking.table_numbers.join(', ')
                                                    : (booking?.table_number ?? '—')}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-600">Ngày</p>
                                            <p className="font-semibold">{booking?.booking_date ? new Date(booking.booking_date).toLocaleDateString('vi-VN') : '—'}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-600">Giờ</p>
                                            <p className="font-semibold">
                                                {booking?.start_time ? formatTime(booking.start_time) : '—'}
                                                {' - '}
                                                {booking?.end_time ? formatTime(booking.end_time) : '—'}
                                            </p>
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
                                        <Badge variant={bill.status === 'paid' ? 'success' : 'warning'}>
                                            {bill.status === 'paid' ? '✓ Đã thanh toán' : '⏳ Chờ thanh toán'}
                                        </Badge>
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>
        </div>
    );
};

export default BookingsPage;
