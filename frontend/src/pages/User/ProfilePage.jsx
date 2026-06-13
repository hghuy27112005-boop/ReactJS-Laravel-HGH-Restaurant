import React, { useState, useEffect } from 'react';
import { userAPI, statisticsAPI } from '../../services/api';
import { useAuthContext } from '../../context/AuthContext';
import { Loading, ErrorMessage, SuccessMessage, Button, Card } from '../../components/Shared';

const ProfilePage = () => {
    const { user } = useAuthContext();
    const [profile, setProfile] = useState(null);
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [editing, setEditing] = useState(false);
    const [formData, setFormData] = useState({});

    useEffect(() => {
        fetchProfile();
        fetchStats();
    }, []);

    const fetchProfile = async () => {
        try {
            const response = await userService.getProfile();
            setProfile(response.data.data);
            setFormData(response.data.data);
        } catch (err) {
            setError('Lỗi tải hồ sơ');
        }
    };

    const fetchStats = async () => {
        try {
            const response = await statisticsService.getUserStats();
            setStats(response.data.data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleUpdate = async (e) => {
        e.preventDefault();
        try {
            await userService.updateProfile(formData);
            setSuccess('Cập nhật hồ sơ thành công');
            setProfile(formData);
            setEditing(false);
            setTimeout(() => setSuccess(null), 3000);
        } catch (err) {
            setError('Lỗi cập nhật hồ sơ');
        }
    };

    const handleAvatarChange = async (e) => {
        const file = e.target.files[0];
        if (file) {
            try {
                const response = await userService.uploadAvatar(file);
                setProfile({ ...profile, avatar_url: response.data.data.avatar_url });
                setSuccess('Cập nhật ảnh đại diện thành công');
                setTimeout(() => setSuccess(null), 3000);
            } catch (err) {
                setError('Lỗi cập nhật ảnh đại diện');
            }
        }
    };

    if (loading) return <Loading />;

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-4xl mx-auto px-4">
                {error && <ErrorMessage message={error} onClose={() => setError(null)} />}
                {success && <SuccessMessage message={success} onClose={() => setSuccess(null)} />}

                <div className="grid md:grid-cols-3 gap-6">
                    {/* Profile Card */}
                    <Card title="Hồ sơ cá nhân" className="md:col-span-2">
                        {!editing ? (
                            <>
                                <div className="space-y-4">
                                    <div>
                                        <label className="text-sm font-semibold text-gray-600">Họ tên</label>
                                        <p className="text-lg">{profile?.name}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-semibold text-gray-600">Email</label>
                                        <p className="text-lg">{profile?.email}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-semibold text-gray-600">Số điện thoại</label>
                                        <p className="text-lg">{profile?.phone}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-semibold text-gray-600">Thành viên</label>
                                        <p className="text-lg font-bold text-red-600">{profile?.membership}</p>
                                    </div>
                                </div>
                                <Button
                                    onClick={() => setEditing(true)}
                                    className="mt-6"
                                >
                                    Chỉnh sửa
                                </Button>
                            </>
                        ) : (
                            <form onSubmit={handleUpdate} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-semibold mb-2">Họ tên</label>
                                    <input
                                        type="text"
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        className="w-full border border-gray-300 rounded px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold mb-2">Email</label>
                                    <input
                                        type="email"
                                        value={formData.email}
                                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                        className="w-full border border-gray-300 rounded px-3 py-2"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold mb-2">Số điện thoại</label>
                                    <input
                                        type="tel"
                                        value={formData.phone}
                                        onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                                        className="w-full border border-gray-300 rounded px-3 py-2"
                                    />
                                </div>
                                <div className="flex gap-2">
                                    <Button type="submit">Lưu</Button>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() => setEditing(false)}
                                    >
                                        Hủy
                                    </Button>
                                </div>
                            </form>
                        )}
                    </Card>

                    {/* Avatar & Stats */}
                    <div>
                        <Card title="Ảnh đại diện">
                            <div className="text-center">
                                <img
                                    src={profile?.avatar_url || '/pics/default_avt.jpg'}
                                    alt="Avatar"
                                    className="w-32 h-32 rounded-full mx-auto mb-4 object-cover"
                                />
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={handleAvatarChange}
                                    className="hidden"
                                    id="avatar-input"
                                />
                                <label htmlFor="avatar-input" className="cursor-pointer">
                                    <Button as="span">Thay đổi ảnh</Button>
                                </label>
                            </div>
                        </Card>

                        {stats && (
                            <Card title="Thống kê" className="mt-6">
                                <div className="space-y-3">
                                    <div>
                                        <p className="text-sm text-gray-600">Tổng đơn</p>
                                        <p className="text-2xl font-bold">{stats.total_orders}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Tổng chi tiêu</p>
                                        <p className="text-2xl font-bold text-red-600">
                                            {Number(stats.total_spent).toLocaleString('vi-VN')}đ
                                        </p>
                                    </div>
                                </div>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ProfilePage;
