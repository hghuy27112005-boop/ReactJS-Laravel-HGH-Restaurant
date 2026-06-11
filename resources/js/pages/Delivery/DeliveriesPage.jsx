import React, { useState, useEffect } from 'react';
import { deliveryAPI } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge, EmptyState } from '../../components/Shared';

const DeliveriesPage = () => {
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
            const response = await deliveryService.getDeliveries();
            setDeliveries(response.data.data);
        } catch (err) {
            setError('Lỗi tải danh sách giao hàng');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const getFilteredDeliveries = () => {
        if (filter === 'all') return deliveries;
        return deliveries.filter(d => d.status === filter);
    };

    const getStatusColor = (status) => {
        const colors = {
            pending: 'warning',
            approved: 'info',
            in_delivery: 'info',
            delivered: 'success',
            cancelled: 'danger',
        };
        return colors[status] || 'default';
    };

    const getStatusLabel = (status) => {
        const labels = {
            pending: '⏳ Chờ xác nhận',
            approved: '✓ Đã xác nhận',
            in_delivery: '🚗 Đang giao',
            delivered: '✓ Đã giao',
            cancelled: '✕ Hủy',
        };
        return labels[status] || status;
    };

    if (loading) return <Loading />;

    const filtered = getFilteredDeliveries();

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-6xl mx-auto px-4">
                <h1 className="text-4xl font-bold mb-8 text-red-600">Giao hàng</h1>

                {error && <ErrorMessage message={error} onClose={() => setError(null)} />}

                {/* Filter Buttons */}
                <div className="flex gap-2 mb-8 overflow-x-auto">
                    {['all', 'pending', 'approved', 'in_delivery', 'delivered', 'cancelled'].map(f => (
                        <button
                            key={f}
                            onClick={() => setFilter(f)}
                            className={`px-4 py-2 rounded font-semibold ${
                                filter === f
                                    ? 'bg-red-600 text-white'
                                    : 'bg-white border border-gray-300 text-gray-700 hover:border-red-600'
                            }`}
                        >
                            {f === 'all' ? 'Tất cả' : f.toUpperCase()}
                        </button>
                    ))}
                </div>

                {/* Deliveries List */}
                {filtered.length === 0 ? (
                    <EmptyState
                        icon="📦"
                        title="Không có giao hàng"
                        description={`Hiện chưa có giao hàng với trạng thái "${filter}"`}
                    />
                ) : (
                    <div className="space-y-4">
                        {filtered.map(delivery => (
                            <Card key={delivery.delivery_id} title={`Giao hàng #${delivery.delivery_code}`}>
                                <div className="grid md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <p className="text-sm text-gray-600">Địa chỉ</p>
                                        <p className="font-semibold">{delivery.address}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">SĐT</p>
                                        <p className="font-semibold">{delivery.phone}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Phí giao</p>
                                        <p className="font-bold text-red-600">5,000đ</p>
                                    </div>
                                </div>

                                {/* Timeline */}
                                <div className="space-y-3 py-4 border-t border-b">
                                    {delivery.approved_at && (
                                        <div className="flex gap-3">
                                            <span>✓ Xác nhận:</span>
                                            <span className="text-gray-600">{new Date(delivery.approved_at).toLocaleString('vi-VN')}</span>
                                        </div>
                                    )}
                                    {delivery.delivery_started_at && (
                                        <div className="flex gap-3">
                                            <span>🚗 Bắt đầu giao:</span>
                                            <span className="text-gray-600">{new Date(delivery.delivery_started_at).toLocaleString('vi-VN')}</span>
                                        </div>
                                    )}
                                    {delivery.delivered_at && (
                                        <div className="flex gap-3">
                                            <span>✓ Giao thành công:</span>
                                            <span className="text-gray-600">{new Date(delivery.delivered_at).toLocaleString('vi-VN')}</span>
                                        </div>
                                    )}
                                </div>

                                <div className="mt-4">
                                    <Badge variant={getStatusColor(delivery.status)}>
                                        {getStatusLabel(delivery.status)}
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
