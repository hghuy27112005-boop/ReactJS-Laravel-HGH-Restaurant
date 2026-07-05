import React, { useState, useEffect } from 'react';
import { adminAPI, extractListData } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge, Button } from '../../components/Shared';

const AdminDeliveriesPage = () => {
    const [deliveries, setDeliveries] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filter, setFilter] = useState('all');
    const [search, setSearch] = useState('');
    const [pagination, setPagination] = useState({});
    const [currentPage, setCurrentPage] = useState(1);
    const [selectedDelivery, setSelectedDelivery] = useState(null);
    const [showModal, setShowModal] = useState(false);

    // Delivery status constants
    const DELIVERY_STATUS = {
        'waiting_info': { label: 'Chờ thông tin', color: 'info', bgColor: 'bg-blue-50' },
        'waiting_confirmation': { label: 'Chờ xác nhận', color: 'warning', bgColor: 'bg-yellow-50' },
        'waiting_payment': { label: 'Chờ thanh toán', color: 'warning', bgColor: 'bg-yellow-50' },
        'waiting_approval': { label: 'Chờ duyệt', color: 'warning', bgColor: 'bg-yellow-50' },
        'shipping': { label: 'Đang giao', color: 'primary', bgColor: 'bg-blue-50' },
        'completed': { label: 'Hoàn thành', color: 'success', bgColor: 'bg-green-50' },
        'cancelled': { label: 'Đã hủy', color: 'danger', bgColor: 'bg-red-50' },
    };

    const PAYMENT_STATUS = {
        'unpaid': { label: 'Chưa thanh toán', color: 'danger' },
        'paid': { label: 'Đã thanh toán', color: 'success' },
    };

    useEffect(() => {
        fetchDeliveries();
    }, [filter, search, currentPage]);

    const fetchDeliveries = async () => {
        try {
            setLoading(true);
            setError(null);
            const filters = {
                search: search || undefined,
                delivery_status: filter === 'all' ? undefined : filter,
                page: currentPage,
                per_page: 10,
            };
            const response = await adminAPI.deliveries.getAll(filters);
            setDeliveries(extractListData(response));
            setPagination(response?.data?.pagination || {});
        } catch (err) {
            if (err.response?.status === 401) return;
            setError(err.response?.data?.message || 'Không thể tải danh sách giao hàng.');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleApprove = async (delivery) => {
        try {
            await adminAPI.deliveries.approve(delivery.delivery_id);
            await fetchDeliveries();
            setShowModal(false);
        } catch (err) {
            setError('Lỗi xác nhận giao hàng');
            console.error(err);
        }
    };

    const handleStartDelivery = async (delivery) => {
        try {
            await adminAPI.deliveries.startDelivery(delivery.delivery_id);
            await fetchDeliveries();
            setShowModal(false);
        } catch (err) {
            setError('Lỗi bắt đầu giao hàng');
            console.error(err);
        }
    };

    const handleComplete = async (delivery) => {
        try {
            await adminAPI.deliveries.complete(delivery.delivery_id);
            await fetchDeliveries();
            setShowModal(false);
        } catch (err) {
            setError('Lỗi hoàn thành giao hàng');
            console.error(err);
        }
    };

    const handleCancel = async (delivery) => {
        if (window.confirm('Bạn chắc chắn muốn hủy giao hàng này?')) {
            try {
                await adminAPI.deliveries.cancel(delivery.delivery_id);
                await fetchDeliveries();
                setShowModal(false);
            } catch (err) {
                setError('Lỗi hủy giao hàng');
                console.error(err);
            }
        }
    };

    const getActionButtons = (delivery) => {
        switch (delivery.delivery_status) {
            case 'waiting_info':
                return (
                    <button
                        onClick={() => handleApprove(delivery)}
                        className="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700"
                    >
                        Xác nhận
                    </button>
                );
            case 'waiting_confirmation':
            case 'waiting_approval':
                return (
                    <button
                        onClick={() => handleStartDelivery(delivery)}
                        className="px-3 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700"
                    >
                        Bắt đầu giao
                    </button>
                );
            case 'shipping':
                return (
                    <button
                        onClick={() => handleComplete(delivery)}
                        className="px-3 py-1 bg-yellow-600 text-white rounded text-xs hover:bg-yellow-700"
                    >
                        Hoàn thành
                    </button>
                );
            default:
                return null;
        }
    };

    if (loading) return <Loading />;

    const stats = {
        total: pagination.total || 0,
        pending: deliveries.filter(d => ['waiting_info', 'waiting_confirmation', 'waiting_approval'].includes(d.delivery_status)).length,
        shipping: deliveries.filter(d => d.delivery_status === 'shipping').length,
        completed: deliveries.filter(d => d.delivery_status === 'completed').length,
    };

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-6xl mx-auto px-4">
                <h1 className="text-4xl font-bold mb-8 text-red-600">Quản lý giao hàng</h1>

                {error && <ErrorMessage message={error} />}

                {/* Stats */}
                <div className="grid md:grid-cols-4 gap-4 mb-8">
                    <Card>
                        <p className="text-sm text-gray-600">Tổng giao hàng</p>
                        <p className="text-3xl font-bold text-red-600">{stats.total}</p>
                    </Card>
                    <Card className="bg-blue-50">
                        <p className="text-sm text-gray-600">Chờ xử lý</p>
                        <p className="text-3xl font-bold text-blue-600">{stats.pending}</p>
                    </Card>
                    <Card className="bg-purple-50">
                        <p className="text-sm text-gray-600">Đang giao</p>
                        <p className="text-3xl font-bold text-purple-600">{stats.shipping}</p>
                    </Card>
                    <Card className="bg-green-50">
                        <p className="text-sm text-gray-600">Hoàn thành</p>
                        <p className="text-3xl font-bold text-green-600">{stats.completed}</p>
                    </Card>
                </div>

                {/* Search and Filter */}
                <div className="mb-6 space-y-4">
                    <div className="flex gap-2 flex-wrap">
                        {['all', 'shipping', 'completed', 'cancelled'].map(status => (
                            <button
                                key={status}
                                onClick={() => { setFilter(status); setCurrentPage(1); }}
                                className={`px-4 py-2 rounded text-sm font-medium transition ${
                                    filter === status
                                        ? 'bg-red-600 text-white'
                                        : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                                }`}
                            >
                                {status === 'all' ? 'Tất cả' : DELIVERY_STATUS[status]?.label || status}
                            </button>
                        ))}
                    </div>
                    <input
                        type="text"
                        placeholder="Tìm kiếm theo ID giao hàng, tên khách, SĐT..."
                        value={search}
                        onChange={(e) => { setSearch(e.target.value); setCurrentPage(1); }}
                        className="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-red-600"
                    />
                </div>

                {/* Deliveries Table */}
                <Card title={`Danh sách giao hàng (${deliveries.length})`}>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-100 border-b">
                                <tr>
                                    <th className="px-4 py-2 text-left">ID Giao Hàng</th>
                                    <th className="px-4 py-2 text-left">Khách hàng</th>
                                    <th className="px-4 py-2 text-left">Địa chỉ</th>
                                    <th className="px-4 py-2 text-left">Trạng thái giao</th>
                                    <th className="px-4 py-2 text-left">Thanh toán</th>
                                    <th className="px-4 py-2 text-left">Ngày tạo</th>
                                    <th className="px-4 py-2 text-left">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                {deliveries.length === 0 ? (
                                    <tr>
                                        <td colSpan="7" className="px-4 py-3 text-center text-gray-500">
                                            Không có dữ liệu
                                        </td>
                                    </tr>
                                ) : (
                                    deliveries.map(delivery => (
                                        <tr
                                            key={delivery.delivery_id}
                                            className="border-b hover:bg-gray-50"
                                        >
                                            <td className="px-4 py-2 font-mono text-xs font-bold">{delivery.delivery_id}</td>
                                            <td className="px-4 py-2">
                                                <div className="font-semibold">{delivery.order?.user?.username || delivery.user?.username || 'N/A'}</div>
                                                <div className="text-xs text-gray-500">{delivery.order?.user?.phone}</div>
                                            </td>
                                            <td className="px-4 py-2 text-xs">{delivery.address?.substring(0, 30)}...</td>
                                            <td className="px-4 py-2">
                                                <Badge variant={DELIVERY_STATUS[delivery.delivery_status]?.color}>
                                                    {DELIVERY_STATUS[delivery.delivery_status]?.label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-2">
                                                <Badge variant={PAYMENT_STATUS[delivery.D_payment_status]?.color}>
                                                    {PAYMENT_STATUS[delivery.D_payment_status]?.label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-2 text-xs">
                                                {new Date(delivery.created_at).toLocaleString('vi-VN')}
                                            </td>
                                            <td className="px-4 py-2">
                                                <button
                                                    onClick={() => {
                                                        setSelectedDelivery(delivery);
                                                        setShowModal(true);
                                                    }}
                                                    className="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700"
                                                >
                                                    Chi tiết
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {pagination.last_page > 1 && (
                        <div className="flex justify-center items-center gap-2 mt-4 pt-4 border-t">
                            {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map(page => (
                                <button
                                    key={page}
                                    onClick={() => setCurrentPage(page)}
                                    className={`px-3 py-1 rounded text-sm ${
                                        currentPage === page
                                            ? 'bg-red-600 text-white'
                                            : 'bg-white border border-gray-300 hover:bg-gray-50'
                                    }`}
                                >
                                    {page}
                                </button>
                            ))}
                        </div>
                    )}
                </Card>
            </div>

            {/* Modal Chi tiết */}
            {showModal && selectedDelivery && (
                <div
                    className="fixed inset-0 bg-black/20 flex items-center justify-center p-4 z-[1100]"
                    onClick={() => setShowModal(false)}
                >
                    <div
                        className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="sticky top-0 bg-red-600 px-6 py-4 flex justify-between items-center border-b">
                            <h2 className="text-xl font-bold text-white">Chi tiết giao hàng</h2>
                            <button
                                onClick={() => setShowModal(false)}
                                className="text-2xl text-white hover:text-gray-200"
                            >
                                ×
                            </button>
                        </div>

                        <div className="p-6 space-y-6">
                            {/* Thông tin cơ bản */}
                            <div>
                                <h3 className="font-bold text-lg mb-3">Thông tin giao hàng</h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-xs text-gray-500">ID Giao hàng</p>
                                        <p className="font-mono font-bold text-sm">{selectedDelivery.delivery_id}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500">ID Đơn hàng</p>
                                        <p className="font-mono font-bold text-sm">{selectedDelivery.order_id}</p>
                                    </div>
                                    <div className="col-span-2">
                                        <p className="text-xs text-gray-500">Địa chỉ giao</p>
                                        <p className="text-sm">{selectedDelivery.address}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Thông tin khách hàng */}
                            <div className="border-t border-red-600 pt-4">
                                <h3 className="font-bold text-lg mb-3">Thông tin khách hàng</h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-xs text-gray-500">Tên</p>
                                        <p className="font-semibold">{selectedDelivery.order?.user?.username || selectedDelivery.user?.username || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500">Email</p>
                                        <p className="text-sm">{selectedDelivery.order?.user?.email}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500">Điện thoại</p>
                                        <p className="text-sm">{selectedDelivery.order?.user?.tele_number || 'N/A'}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Trạng thái */}
                            <div className="border-t border-red-600 pt-4">
                                <h3 className="font-bold text-lg mb-3">Trạng thái</h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-xs text-gray-500">Trạng thái giao hàng</p>
                                        <Badge variant={DELIVERY_STATUS[selectedDelivery.delivery_status]?.color} className="inline-block">
                                            {DELIVERY_STATUS[selectedDelivery.delivery_status]?.label}
                                        </Badge>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500">Trạng thái thanh toán</p>
                                        <Badge variant={PAYMENT_STATUS[selectedDelivery.D_payment_status]?.color} className="inline-block">
                                            {PAYMENT_STATUS[selectedDelivery.D_payment_status]?.label}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            {/* Timeline */}
                            <div className="border-t border-red-600 pt-4">
                                <h3 className="font-bold text-lg mb-3">Timeline</h3>
                                <div className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span>Tạo lúc:</span>
                                        <span>{new Date(selectedDelivery.created_at).toLocaleString('vi-VN')}</span>
                                    </div>
                                    {selectedDelivery.approved_at && (
                                        <div className="flex justify-between">
                                            <span>Xác nhận lúc:</span>
                                            <span>{new Date(selectedDelivery.approved_at).toLocaleString('vi-VN')}</span>
                                        </div>
                                    )}
                                    {selectedDelivery.delivered_at && (
                                        <div className="flex justify-between">
                                            <span>Giao thành công lúc:</span>
                                            <span>{new Date(selectedDelivery.delivered_at).toLocaleString('vi-VN')}</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Action Buttons */}
                            <div className="border-t border-red-600 pt-4 flex gap-2">
                                {getActionButtons(selectedDelivery) && (
                                    <>
                                        {getActionButtons(selectedDelivery)}
                                        {!['completed', 'cancelled'].includes(selectedDelivery.delivery_status) && (
                                            <button
                                                onClick={() => handleCancel(selectedDelivery)}
                                                className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
                                            >
                                                Hủy giao hàng
                                            </button>
                                        )}
                                    </>
                                )}
                                <button
                                    onClick={() => setShowModal(false)}
                                    className="ml-auto px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 text-sm"
                                >
                                    Đóng
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default AdminDeliveriesPage;