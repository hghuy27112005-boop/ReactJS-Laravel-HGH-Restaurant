import React, { useState, useEffect, useRef } from 'react';
import { adminAPI } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge } from '../../components/Shared';

const OrderManagement = () => {
    const [bills, setBills] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Filters state
    const [sortOrder, setSortOrder] = useState('desc'); // desc = mới nhất, asc = cũ nhất
    const [filterDate, setFilterDate] = useState('');
    const [filterUserId, setFilterUserId] = useState('');
    const [filterOrderType, setFilterOrderType] = useState('');

    // UI state
    const [tempUserId, setTempUserId] = useState('');
    const [tempOrderType, setTempOrderType] = useState('');
    const [isFilterModalOpen, setIsFilterModalOpen] = useState(false);
    const filterModalRef = useRef(null);

    // Click outside to close filter modal
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (filterModalRef.current && !filterModalRef.current.contains(event.target)) {
                setIsFilterModalOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    const fetchBills = async () => {
        try {
            setLoading(true);
            const params = {
                sort: sortOrder,
            };
            if (filterDate) {
                params.date_from = filterDate;
                params.date_to = filterDate;
            }
            if (filterUserId) {
                params.user_id = filterUserId;
            }
            if (filterOrderType) {
                params.order_type = filterOrderType;
            }

            const res = await adminAPI.bills.getAll(params);
            setBills(res.data.data);
        } catch (err) {
            setError('Lỗi tải danh sách đơn hàng');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchBills();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sortOrder, filterDate, filterUserId, filterOrderType]);

    const getOrderTypeName = (type) => {
        if (type === 'booking_table') return 'Đặt bàn';
        if (type === 'delivery') return 'Đặt ship';
        return type || 'N/A';
    };

    const getBillStatus = (bill) => {
        const orderType = bill.order?.order_type;
        const paymentMethod = bill.payment_method;
        const isPaidByMethod = Boolean(paymentMethod && paymentMethod !== 'unpaid');
        const isPaidByRecord = orderType === 'delivery'
            ? bill.delivery?.D_payment_status === 'paid'
            : orderType === 'booking_table'
                ? bill.booking_table?.[0]?.B_payment_status === 'paid'
                : false;

        const isPaid = isPaidByMethod || isPaidByRecord;

        return (
            <Badge variant={isPaid ? 'success' : 'warning'}>
                {isPaid ? '✓ Đã thanh toán' : '⏳ Chờ thanh toán'}
            </Badge>
        );
    };

    return (
        <div className="min-h-screen bg-gray-50 py-8 relative">
            <div className="max-w-7xl mx-auto px-4">
                <h1 className="text-4xl font-bold text-red-600 mb-8">Quản lý đơn hàng</h1>

                {error && <ErrorMessage message={error} />}

                {/* Toolbar */}
                <div className="flex justify-between items-center mb-4 bg-white p-4 rounded shadow-sm relative z-10">
                    {/* Left Filters (Modal Dropdown) */}
                    <div className="relative" ref={filterModalRef}>
                        <button
                            onClick={() => {
                                setTempUserId(filterUserId);
                                setTempOrderType(filterOrderType);
                                setIsFilterModalOpen(!isFilterModalOpen);
                            }}
                            className="bg-white hover:bg-red-50 text-red-600 font-bold py-2 px-4 rounded border-2 border-red-600 flex items-center gap-2 transition"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            Bộ lọc
                        </button>

                        {isFilterModalOpen && (
                            <div className="absolute top-full mt-2 left-0 bg-white border border-gray-200 rounded-lg shadow-xl w-72 p-5 z-50">
                                <div className="mb-4">
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">ID Người dùng</label>
                                    <input
                                        type="number"
                                        placeholder="Tất cả"
                                        value={tempUserId}
                                        onChange={(e) => setTempUserId(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-1">Loại đơn</label>
                                    <select
                                        value={tempOrderType}
                                        onChange={(e) => setTempOrderType(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500"
                                    >
                                        <option value="">Tất cả</option>
                                        <option value="booking_table">Đặt bàn</option>
                                        <option value="delivery">Đặt ship</option>
                                    </select>
                                </div>

                                <div className="mt-6 flex justify-between gap-3">
                                    <button
                                        onClick={() => {
                                            setTempUserId('');
                                            setTempOrderType('');
                                            setFilterUserId('');
                                            setFilterOrderType('');
                                            setIsFilterModalOpen(false);
                                        }}
                                        className="flex-1 py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded border border-gray-300 transition text-sm"
                                    >
                                        Đặt lại
                                    </button>
                                    <button
                                        onClick={() => {
                                            setFilterUserId(tempUserId);
                                            setFilterOrderType(tempOrderType);
                                            setIsFilterModalOpen(false);
                                        }}
                                        className="flex-1 py-2 px-4 bg-red-600 hover:bg-red-700 text-white font-semibold rounded transition text-sm"
                                    >
                                        Áp dụng
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Right Filters */}
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2">
                            <label className="font-semibold text-gray-700 text-sm">Ngày:</label>
                            <input
                                type="date"
                                value={filterDate}
                                onChange={(e) => setFilterDate(e.target.value)}
                                className="px-3 py-2 border border-gray-300 rounded bg-gray-100 text-gray-700 focus:outline-none focus:ring-1 focus:ring-red-500"
                            />
                        </div>
                        <div className="flex items-center gap-2">
                            <label className="font-semibold text-gray-700 text-sm">Sắp xếp:</label>
                            <select
                                value={sortOrder}
                                onChange={(e) => setSortOrder(e.target.value)}
                                className="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-red-500 bg-gray-100 font-semibold text-gray-700"
                            >
                                <option value="desc">Mới nhất</option>
                                <option value="asc">Cũ nhất</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Active Filter Tags */}
                {(filterUserId || filterOrderType || filterDate) && (
                    <div className="flex flex-wrap items-center gap-3 mb-6 bg-blue-50 p-3 rounded-lg border border-blue-100">
                        <span className="text-sm font-semibold text-blue-800">Đang lọc theo:</span>
                        {filterUserId && (
                            <span className="inline-flex items-center gap-2 px-4 py-1.5 bg-white border-2 border-blue-400 text-blue-500 font-bold rounded-full shadow-sm text-sm">
                                ID User: {filterUserId}
                                <button onClick={() => { setFilterUserId(''); setTempUserId(''); }} className="hover:bg-blue-100 rounded-full w-5 h-5 flex items-center justify-center transition">✕</button>
                            </span>
                        )}
                        {filterOrderType && (
                            <span className="inline-flex items-center gap-2 px-4 py-1.5 bg-white border-2 border-blue-400 text-blue-500 font-bold rounded-full shadow-sm text-sm">
                                Loại: {getOrderTypeName(filterOrderType)}
                                <button onClick={() => { setFilterOrderType(''); setTempOrderType(''); }} className="hover:bg-blue-100 rounded-full w-5 h-5 flex items-center justify-center transition">✕</button>
                            </span>
                        )}
                        {filterDate && (
                            <span className="inline-flex items-center gap-2 px-4 py-1.5 bg-white border-2 border-blue-400 text-blue-500 font-bold rounded-full shadow-sm text-sm">
                                Ngày: {filterDate}
                                <button onClick={() => setFilterDate('')} className="hover:bg-blue-100 rounded-full w-5 h-5 flex items-center justify-center transition">✕</button>
                            </span>
                        )}
                        <button
                            onClick={() => {
                                setFilterUserId(''); setTempUserId('');
                                setFilterOrderType(''); setTempOrderType('');
                                setFilterDate('');
                            }}
                            className="text-blue-500 hover:text-blue-700 text-sm font-semibold underline underline-offset-2 ml-2 transition"
                        >
                            Xóa tất cả
                        </button>
                    </div>
                )}

                <div className="rounded-lg overflow-hidden shadow-lg bg-white">
                    <Card title={`Danh sách hóa đơn (${bills.length})`} className="border-none shadow-none">
                        <div className="overflow-x-auto min-h-[400px]">
                            <table className="w-full text-sm text-left">
                                <thead className="bg-gray-100 text-gray-700 uppercase">
                                    <tr>
                                        <th className="px-4 py-3">Mã HĐ</th>
                                        <th className="px-4 py-3">ID Người dùng</th>
                                        <th className="px-4 py-3">Tên Người dùng</th>
                                        <th className="px-4 py-3">Loại đơn</th>
                                        <th className="px-4 py-3">Tổng tiền</th>
                                        <th className="px-4 py-3">Trạng thái</th>
                                        <th className="px-4 py-3">Thanh toán</th>
                                        <th className="px-4 py-3">Ngày tạo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {bills.length === 0 ? (
                                        <tr>
                                            <td colSpan="8" className="text-center py-8 text-gray-500">Không tìm thấy đơn hàng nào</td>
                                        </tr>
                                    ) : (
                                        bills.map((bill) => (
                                            <tr key={bill.bill_id} className="border-b hover:bg-gray-50">
                                                <td className="px-4 py-3 font-semibold text-red-600">{bill.bill_id}</td>
                                                <td className="px-4 py-3 font-mono text-gray-600 font-semibold">
                                                    {bill.order?.user_id || bill.user?.user_id || 'N/A'}
                                                </td>
                                                <td className="px-4 py-3 font-medium">
                                                    {bill.order?.user?.username || bill.user?.username || 'N/A'}
                                                </td>
                                                <td className="px-4 py-3 font-medium text-blue-700">
                                                    {getOrderTypeName(bill.order?.order_type)}
                                                </td>
                                                <td className="px-4 py-3 font-bold">
                                                    {Number(bill.total_price).toLocaleString('vi-VN')}đ
                                                </td>
                                                <td className="px-4 py-3">
                                                    {getBillStatus(bill)}
                                                </td>
                                                <td className="px-4 py-3 text-gray-600">
                                                    {bill.payment_method || 'N/A'}
                                                </td>
                                                <td className="px-4 py-3 text-gray-500">
                                                    {new Date(bill.created_at).toLocaleString('vi-VN')}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    );
};

export default OrderManagement;