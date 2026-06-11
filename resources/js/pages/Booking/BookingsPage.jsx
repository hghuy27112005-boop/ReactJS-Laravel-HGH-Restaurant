import React, { useState, useEffect } from 'react';
import { bookingTableAPI } from '../../services/api';
import { Loading, ErrorMessage, SuccessMessage, Button, Card, Badge, EmptyState, Modal } from '../../components/Shared';

const BookingsPage = () => {
    const [bookings, setBookings] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [formData, setFormData] = useState({
        booking_date: '',
        arrival_time: '',
        duration: '60',
        guest_count: '2',
        table_number: '',
    });

    useEffect(() => {
        fetchBookings();
    }, []);

    const fetchBookings = async () => {
        try {
            setLoading(true);
            const response = await bookingService.getBookings();
            setBookings(response.data.data);
        } catch (err) {
            setError('Lỗi tải danh sách đặt bàn');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleCreateBooking = async (e) => {
        e.preventDefault();
        try {
            await bookingService.createBooking(formData);
            setSuccess('Đặt bàn thành công!');
            setShowCreateModal(false);
            setFormData({ booking_date: '', arrival_time: '', duration: '60', guest_count: '2', table_number: '' });
            await fetchBookings();
            setTimeout(() => setSuccess(null), 3000);
        } catch (err) {
            setError(err.response?.data?.message || 'Lỗi đặt bàn');
        }
    };

    const handleCancelBooking = async (bookingId) => {
        try {
            await bookingService.deleteBooking(bookingId);
            setSuccess('Hủy đặt bàn thành công');
            await fetchBookings();
            setTimeout(() => setSuccess(null), 3000);
        } catch (err) {
            setError('Lỗi hủy đặt bàn');
        }
    };

    if (loading) return <Loading />;

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-6xl mx-auto px-4">
                <div className="flex justify-between items-center mb-8">
                    <h1 className="text-4xl font-bold text-red-600">Đặt bàn</h1>
                    <Button onClick={() => setShowCreateModal(true)}>+ Đặt bàn mới</Button>
                </div>

                {error && <ErrorMessage message={error} onClose={() => setError(null)} />}
                {success && <SuccessMessage message={success} onClose={() => setSuccess(null)} />}

                {/* Create Modal */}
                <Modal
                    isOpen={showCreateModal}
                    title="Đặt bàn mới"
                    onClose={() => setShowCreateModal(false)}
                    onConfirm={handleCreateBooking}
                    confirmText="Đặt bàn"
                >
                    <form onSubmit={handleCreateBooking} className="space-y-4">
                        <div>
                            <label className="block text-sm font-semibold mb-2">Ngày đặt</label>
                            <input
                                type="date"
                                value={formData.booking_date}
                                onChange={(e) => setFormData({ ...formData, booking_date: e.target.value })}
                                className="w-full border border-gray-300 rounded px-3 py-2"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-semibold mb-2">Giờ tới</label>
                            <input
                                type="time"
                                value={formData.arrival_time}
                                onChange={(e) => setFormData({ ...formData, arrival_time: e.target.value })}
                                className="w-full border border-gray-300 rounded px-3 py-2"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-semibold mb-2">Thời gian (phút)</label>
                            <input
                                type="number"
                                min="30"
                                max="300"
                                value={formData.duration}
                                onChange={(e) => setFormData({ ...formData, duration: e.target.value })}
                                className="w-full border border-gray-300 rounded px-3 py-2"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-semibold mb-2">Số khách</label>
                            <input
                                type="number"
                                min="1"
                                max="12"
                                value={formData.guest_count}
                                onChange={(e) => setFormData({ ...formData, guest_count: e.target.value })}
                                className="w-full border border-gray-300 rounded px-3 py-2"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-semibold mb-2">Bàn (1-50)</label>
                            <input
                                type="number"
                                min="1"
                                max="50"
                                value={formData.table_number}
                                onChange={(e) => setFormData({ ...formData, table_number: e.target.value })}
                                className="w-full border border-gray-300 rounded px-3 py-2"
                            />
                        </div>
                    </form>
                </Modal>

                {/* Bookings List */}
                {bookings.length === 0 ? (
                    <EmptyState
                        icon="📅"
                        title="Chưa có đặt bàn"
                        description="Hãy đặt một bàn để trải nghiệm nhà hàng của chúng tôi"
                        action={<Button onClick={() => setShowCreateModal(true)}>Đặt bàn ngay</Button>}
                    />
                ) : (
                    <div className="space-y-4">
                        {bookings.map(booking => (
                            <Card key={booking.booking_id} title={`Bàn ${booking.table_number}`}>
                                <div className="grid md:grid-cols-3 gap-4">
                                    <div>
                                        <p className="text-sm text-gray-600">Ngày</p>
                                        <p className="font-semibold">{new Date(booking.booking_date).toLocaleDateString('vi-VN')}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Giờ</p>
                                        <p className="font-semibold">{booking.arrival_time}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Số khách</p>
                                        <p className="font-semibold">{booking.guest_count} người</p>
                                    </div>
                                </div>
                                <div className="mt-4 flex gap-2">
                                    <Badge variant={booking.status === 'confirmed' ? 'success' : 'warning'}>
                                        {booking.status === 'confirmed' ? '✓ Xác nhận' : '⏳ Chờ xác nhận'}
                                    </Badge>
                                    {booking.status !== 'cancelled' && (
                                        <Button
                                            variant="danger"
                                            size="sm"
                                            onClick={() => handleCancelBooking(booking.booking_id)}
                                        >
                                            Hủy
                                        </Button>
                                    )}
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
};

export default BookingsPage;
