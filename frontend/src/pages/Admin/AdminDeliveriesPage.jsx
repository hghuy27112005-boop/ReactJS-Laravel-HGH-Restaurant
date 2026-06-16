import React, { useState, useEffect } from 'react';
import { deliveryService, extractListData } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge, Button } from '../../components/Shared';

const AdminDeliveriesPage = () => {
    const [deliveries, setDeliveries] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filter, setFilter] = useState('all');

    useEffect(() => {
        fetchDeliveries();
    }, []);

    const fetchDeliveries = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await deliveryService.getDeliveries();
            setDeliveries(extractListData(response));
        } catch (err) {
            if (err.response?.status === 401) return;
            setError(err.response?.data?.message || 'Không thể tải danh sách giao hàng.');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const getFiltered = () => {
        if (filter === 'pending') return deliveries.filter(d => d.status === 'pending');
        if (filter === 'approved') return deliveries.filter(d => d.status === 'approved');
        if (filter === 'in_delivery') return deliveries.filter(d => d.status === 'in_delivery');
        if (filter === 'delivered') return deliveries.filter(d => d.status === 'delivered');
        if (filter === 'cancelled') return deliveries.filter(d => d.status === 'cancelled');
        return deliveries;
    };

    const handleApprove = async (deliveryId) => {
        try {
            await deliveryService.approveDelivery(deliveryId);
            await fetchDeliveries();
        } catch (err) {
            setError('Lỗi xác nhận giao hàng');
        }
    };

    const handleStartDelivery = async (deliveryId) => {
        try {
            await deliveryService.startDelivery(deliveryId);
            await fetchDeliveries();
        } catch (err) {
            setError('Lỗi bắt đầu giao hàng');
        }
    };

    if (loading) return <Loading />;

    const filtered = getFiltered();
    const stats = {
        pending: deliveries.filter(d => d.status === 'pending').length,
        approved: deliveries.filter(d => d.status === 'approved').length,
        in_delivery: deliveries.filter(d => d.status === 'in_delivery').length,
        delivered: deliveries.filter(d => d.status === 'delivered').length,
    };

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-6xl mx-auto px-4">
                <h1 className="text-4xl font-bold mb-8 text-red-600">Quản lý giao hàng</h1>

                {error && <ErrorMessage message={error} />}

                {/* Stats */}
                <div className="grid md:grid-cols-4 gap-4 mb-8">
                    <Card>
                        <p className="text-sm text-gray-600">Chờ xác nhận</p>
                        <p className="text-3xl font-bold text-yellow-600">{stats.pending}</p>
                    </Card>
                    <Card>
                        <p className="text-sm text-gray-600">Đã xác nhận</p>
                        <p className="text-3xl font-bold text-blue-600">{stats.approved}</p>
                    </Card>
                    <Card>
                        <p className="text-sm text-gray-600">Đang giao</p>
                        <p className="text-3xl font-bold text-purple-600">{stats.in_delivery}</p>
                    </Card>
                    <Card>
                        <p className="text-sm text-gray-600">Đã giao</p>
                        <p className="text-3xl font-bold text-green-600">{stats.delivered}</p>
                    </Card>
                </div>

                {/* Filters */}
                <div className="flex gap-2 mb-6 overflow-x-auto">
                    {['all', 'pending', 'approved', 'in_delivery', 'delivered', 'cancelled'].map(f => (
                        <button
                            key={f}
                            onClick={() => setFilter(f)}
                            className={`px-4 py-2 rounded whitespace-nowrap ${
                                filter === f ? 'bg-red-600 text-white' : 'bg-white border'
                            }`}
                        >
                            {f === 'all' ? 'Tất cả' : f === 'pending' ? '⏳ Chờ' : f === 'approved' ? '✓ Xác nhận' : f === 'in_delivery' ? '🚗 Giao' : f === 'delivered' ? '✓ Xong' : '✕ Hủy'}
                        </button>
                    ))}
                </div>

                {/* Deliveries Table */}
                <Card>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-100">
                                <tr>
                                    <th className="px-4 py-2 text-left">Mã</th>
                                    <th className="px-4 py-2 text-left">Địa chỉ</th>
                                    <th className="px-4 py-2 text-left">SĐT</th>
                                    <th className="px-4 py-2 text-left">Trạng thái</th>
                                    <th className="px-4 py-2 text-left">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filtered.map(delivery => (
                                    <tr key={delivery.delivery_id} className="border-b hover:bg-gray-50">
                                        <td className="px-4 py-2 font-semibold">{delivery.delivery_code}</td>
                                        <td className="px-4 py-2">{delivery.address}</td>
                                        <td className="px-4 py-2">{delivery.phone}</td>
                                        <td className="px-4 py-2">
                                            <Badge variant={
                                                delivery.status === 'pending' ? 'warning' :
                                                delivery.status === 'approved' ? 'info' :
                                                delivery.status === 'in_delivery' ? 'info' :
                                                delivery.status === 'delivered' ? 'success' : 'danger'
                                            }>
                                                {delivery.status}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2">
                                            {delivery.status === 'pending' && (
                                                <Button
                                                    size="sm"
                                                    onClick={() => handleApprove(delivery.delivery_id)}
                                                >
                                                    Xác nhận
                                                </Button>
                                            )}
                                            {delivery.status === 'approved' && (
                                                <Button
                                                    size="sm"
                                                    onClick={() => handleStartDelivery(delivery.delivery_id)}
                                                >
                                                    Bắt đầu
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </div>
    );
};

export default AdminDeliveriesPage;
