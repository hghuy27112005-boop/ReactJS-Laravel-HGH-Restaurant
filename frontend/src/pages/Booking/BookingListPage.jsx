import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { bookingTableAPI } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge, Button, EmptyState } from '../../components/Shared';
import BookingDetail from '../../components/BookingDetail';

const BookingListPage = () => {
    const navigate = useNavigate();
    const [bookings, setBookings] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filter, setFilter] = useState('all');
    const [selectedBooking, setSelectedBooking] = useState(null);
    const [showDetailModal, setShowDetailModal] = useState(false);

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

    const getFilteredBookings = () => {
        if (filter === 'all') return bookings;
        if (filter === 'upcoming') {
            return bookings.filter(b => {
                const bookingDate = new Date(b.booking_date);
                return bookingDate > new Date() && b.status !== 'cancelled';
            });
        }
        if (filter === 'past') {
            return bookings.filter(b => {
                const bookingDate = new Date(b.booking_date);
                return bookingDate < new Date();
            });
        }
        if (filter === 'cancelled') {
            return bookings.filter(b => b.status === 'cancelled');
        }
        return bookings;
    };

    const handleReschedule = async (bookingId, newDate, newTime) => {
        try {
            // Call API to reschedule
            await bookingService.updateBooking(bookingId, {
                booking_date: newDate,
                arrival_time: newTime,
            });
            setError(null);
            await fetchBookings();
            setShowDetailModal(false);
        } catch (err) {
            setError('Lỗi đổi lịch');
        }
    };

    const handleCancel = async (bookingId) => {
        try {
            await bookingService.cancelBooking(bookingId);
            await fetchBookings();
            setShowDetailModal(false);
        } catch (err) {
            setError('Lỗi hủy đặt bàn');
        }
    };

    if (loading) return <Loading />;

    const filtered = getFilteredBookings();

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-4xl mx-auto px-4">
                <div className="flex justify-between items-center mb-8">
                    <h1 className="text-4xl font-bold text-red-600">Danh sách đặt bàn</h1>
                    <Button onClick={() => navigate('/booking-form')}>+ Đặt bàn mới</Button>
                </div>

                {error && <ErrorMessage message={error} onClose={() => setError(null)} />}

                {/* Filters */}
                <div className="flex gap-2 mb-8 overflow-x-auto">
                    {['all', 'upcoming', 'past', 'cancelled'].map(f => (
                        <button
                            key={f}
                            onClick={() => setFilter(f)}
                            className={`px-4 py-2 rounded font-semibold whitespace-nowrap ${
                                filter === f
                                    ? 'bg-red-600 text-white'
                                    : 'bg-white border border-gray-300 text-gray-700 hover:border-red-600'
                            }`}
                        >
                            {f === 'all' ? 'Tất cả' : f === 'upcoming' ? 'Sắp tới' : f === 'past' ? 'Quá khứ' : 'Đã hủy'}
                        </button>
                    ))}
                </div>

                {/* Bookings */}
                {filtered.length === 0 ? (
                    <EmptyState
                        icon="📅"
                        title="Không có đặt bàn"
                        description={`Chưa có đặt bàn với bộ lọc "${filter}"`}
                        action={<Button onClick={() => navigate('/booking-form')}>Đặt bàn ngay</Button>}
                    />
                ) : (
                    <div className="space-y-4">
                        {filtered.map(booking => {
                            const bookingDate = new Date(booking.booking_date);
                            const isUpcoming = bookingDate > new Date() && booking.status !== 'cancelled';

                            return (
                                <Card
                                    key={booking.booking_id}
                                    className={isUpcoming ? 'border-l-4 border-l-green-500' : ''}
                                >
                                    <div className="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 className="font-bold text-lg">Bàn {booking.table_number}</h3>
                                            <p className="text-sm text-gray-600">{booking.booking_code}</p>
                                        </div>
                                        <Badge
                                            variant={
                                                booking.status === 'confirmed' ? 'success' :
                                                booking.status === 'cancelled' ? 'danger' : 'warning'
                                            }
                                        >
                                            {booking.status === 'confirmed' ? '✓ Xác nhận' :
                                             booking.status === 'cancelled' ? '✕ Đã hủy' : '⏳ Chờ xác nhận'}
                                        </Badge>
                                    </div>

                                    <div className="grid md:grid-cols-5 gap-4 mb-4">
                                        <div>
                                            <p className="text-sm text-gray-600">Ngày</p>
                                            <p className="font-semibold">{bookingDate.toLocaleDateString('vi-VN')}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-600">Giờ</p>
                                            <p className="font-semibold">{booking.arrival_time}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-600">Khách</p>
                                            <p className="font-semibold">{booking.guest_count} người</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-600">Thời gian</p>
                                            <p className="font-semibold">{booking.duration} phút</p>
                                        </div>
                                        <div className="text-right">
                                            <Button
                                                onClick={() => {
                                                    setSelectedBooking(booking);
                                                    setShowDetailModal(true);
                                                }}
                                                size="sm"
                                            >
                                                Chi tiết
                                            </Button>
                                        </div>
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                )}

                {/* Detail Modal */}
                {showDetailModal && selectedBooking && (
                    <BookingDetail
                        booking={selectedBooking}
                        onClose={() => setShowDetailModal(false)}
                        onReschedule={handleReschedule}
                        onCancel={handleCancel}
                    />
                )}
            </div>
        </div>
    );
};

export default BookingListPage;
