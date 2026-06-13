import React, { useState, useEffect } from 'react';
import { Loading, ErrorMessage, Card, Badge, Button, EmptyState } from '../../components/Shared';

const UserNotificationsPage = () => {
    const [notifications, setNotifications] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filter, setFilter] = useState('all');

    useEffect(() => {
        fetchNotifications();
    }, []);

    const fetchNotifications = async () => {
        try {
            setLoading(true);
            // Simulated notifications
            const mockNotifications = [
                {
                    id: 1,
                    type: 'order',
                    title: 'Đơn hàng #001 được xác nhận',
                    message: 'Nhà hàng đã xác nhận và bắt đầu chuẩn bị đơn hàng của bạn',
                    icon: '📋',
                    timestamp: '2024-06-08 14:32:00',
                    read: false,
                },
                {
                    id: 2,
                    type: 'delivery',
                    title: 'Giao hàng sắp đến',
                    message: 'Tài xế đang trên đường, ETA 15 phút',
                    icon: '🚗',
                    timestamp: '2024-06-08 14:45:00',
                    read: false,
                },
                {
                    id: 3,
                    type: 'promotion',
                    title: 'Khuyến mãi đặc biệt cho bạn',
                    message: 'Giảm 20% cho đơn hàng tiếp theo, sử dụng trước 30/06',
                    icon: '🎉',
                    timestamp: '2024-06-08 10:00:00',
                    read: true,
                },
                {
                    id: 4,
                    type: 'points',
                    title: 'Bạn được 50 điểm thưởng',
                    message: 'Cảm ơn bạn đã mua hàng, bạn được +50 điểm',
                    icon: '⭐',
                    timestamp: '2024-06-07 16:20:00',
                    read: true,
                },
            ];
            setNotifications(mockNotifications);
        } catch (err) {
            setError('Lỗi tải thông báo');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const markAsRead = (id) => {
        setNotifications(notifications.map(n => n.id === id ? { ...n, read: true } : n));
    };

    const deleteNotification = (id) => {
        setNotifications(notifications.filter(n => n.id !== id));
    };

    const markAllAsRead = () => {
        setNotifications(notifications.map(n => ({ ...n, read: true })));
    };

    const getFiltered = () => {
        if (filter === 'unread') return notifications.filter(n => !n.read);
        if (filter === 'order') return notifications.filter(n => n.type === 'order');
        if (filter === 'delivery') return notifications.filter(n => n.type === 'delivery');
        if (filter === 'promotion') return notifications.filter(n => n.type === 'promotion');
        if (filter === 'points') return notifications.filter(n => n.type === 'points');
        return notifications;
    };

    if (loading) return <Loading />;

    const filtered = getFiltered();
    const unreadCount = notifications.filter(n => !n.read).length;

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-4xl mx-auto px-4">
                <div className="flex justify-between items-center mb-8">
                    <h1 className="text-4xl font-bold text-red-600">🔔 Thông báo</h1>
                    {unreadCount > 0 && (
                        <Badge variant="danger">{unreadCount} chưa đọc</Badge>
                    )}
                </div>

                {error && <ErrorMessage message={error} onClose={() => setError(null)} />}

                {/* Filters and Actions */}
                <div className="mb-6 flex justify-between items-center flex-wrap gap-4">
                    <div className="flex gap-2 flex-wrap">
                        {['all', 'unread', 'order', 'delivery', 'promotion', 'points'].map(f => (
                            <button
                                key={f}
                                onClick={() => setFilter(f)}
                                className={`px-3 py-1 rounded text-sm font-semibold transition ${
                                    filter === f
                                        ? 'bg-red-600 text-white'
                                        : 'bg-white border border-gray-300 text-gray-700 hover:border-red-600'
                                }`}
                            >
                                {f === 'all' ? 'Tất cả' : f === 'unread' ? 'Chưa đọc' : f === 'order' ? 'Đơn hàng' : f === 'delivery' ? 'Giao hàng' : f === 'promotion' ? 'Khuyến mãi' : 'Điểm'}
                            </button>
                        ))}
                    </div>
                    {unreadCount > 0 && (
                        <Button size="sm" variant="secondary" onClick={markAllAsRead}>
                            Đánh dấu tất cả đã đọc
                        </Button>
                    )}
                </div>

                {/* Notifications List */}
                {filtered.length === 0 ? (
                    <EmptyState
                        icon="🔔"
                        title="Không có thông báo"
                        description="Bạn đã đọc hết các thông báo"
                    />
                ) : (
                    <div className="space-y-3">
                        {filtered.map(notif => (
                            <Card
                                key={notif.id}
                                className={`border-l-4 ${!notif.read ? 'border-l-blue-600 bg-blue-50' : 'border-l-gray-300'}`}
                            >
                                <div className="flex gap-4">
                                    <div className="text-3xl flex-shrink-0">{notif.icon}</div>
                                    <div className="flex-1">
                                        <div className="flex justify-between items-start">
                                            <div>
                                                <p className="font-bold">{notif.title}</p>
                                                <p className="text-sm text-gray-600 mt-1">{notif.message}</p>
                                            </div>
                                            {!notif.read && (
                                                <div className="w-3 h-3 bg-blue-600 rounded-full flex-shrink-0 mt-1" />
                                            )}
                                        </div>
                                        <div className="flex justify-between items-center mt-3">
                                            <p className="text-xs text-gray-500">
                                                {new Date(notif.timestamp).toLocaleString('vi-VN')}
                                            </p>
                                            <div className="flex gap-2">
                                                {!notif.read && (
                                                    <button
                                                        onClick={() => markAsRead(notif.id)}
                                                        className="text-xs text-blue-600 hover:underline"
                                                    >
                                                        Đánh dấu đã đọc
                                                    </button>
                                                )}
                                                <button
                                                    onClick={() => deleteNotification(notif.id)}
                                                    className="text-xs text-red-600 hover:underline"
                                                >
                                                    Xóa
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
};

export default UserNotificationsPage;