import React, { useState, useEffect } from 'react';
import { deliveryService, extractListData } from '../../services/api';
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
        if (filter === 'delivered') {
            return deliveries.filter(d => d.is_paid || d.status === 'completed');
        }
        if (filter === 'pending') {
            return deliveries.filter(d => !d.is_paid && d.status === 'pending');
        }
        return deliveries.filter(d => d.status === filter);
    };

    const getStatusColor = (bill) => {
        if (bill.status === 'cancelled') return 'danger';
        if (bill.is_paid || bill.status === 'completed') return 'success';
        return 'warning';
    };

    const getStatusLabel = (bill) => {
        if (bill.status === 'cancelled') return '✕ Đã hủy';
        if (bill.is_paid) return '✓ Đã thanh toán';
        if (bill.status === 'completed') return '✓ Hoàn thành';
        return '⏳ Chờ thanh toán';
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
                    {['all', 'pending', 'delivered', 'cancelled'].map(f => (
                        <button
                            key={f}
                            onClick={() => setFilter(f)}
                            className={`px-4 py-2 rounded font-semibold ${
                                filter === f
                                    ? 'bg-red-600 text-white'
                                    : 'bg-white border border-gray-300 text-gray-700 hover:border-red-600'
                            }`}
                        >
                            {f === 'all' ? 'Tất cả' : f === 'pending' ? 'Chờ TT' : f === 'delivered' ? 'Đã TT' : 'Đã hủy'}
                        </button>
                    ))}
                </div>

                {filtered.length === 0 ? (
                    <EmptyState
                        icon="📦"
                        title="Không có đơn giao hàng"
                        description={deliveries.length === 0
                            ? 'Bạn chưa có đơn mang về nào. Chọn món tại Menu (Mang về) để bắt đầu.'
                            : `Chưa có đơn với bộ lọc "${filter}"`}
                    />
                ) : (
                    <div className="space-y-4">
                        {filtered.map(bill => (
                            <Card key={bill.id} title={`Hóa đơn #${bill.bill_code}`}>
                                <div className="grid md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <p className="text-sm text-gray-600">Địa chỉ</p>
                                        <p className="font-semibold">{bill.address || '—'}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Ngày đặt</p>
                                        <p className="font-semibold">{bill.booking_date ? new Date(bill.booking_date).toLocaleDateString('vi-VN') : '—'}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Tổng tiền</p>
                                        <p className="font-bold text-red-600">{Number(bill.total_amount || 0).toLocaleString('vi-VN')}đ</p>
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
