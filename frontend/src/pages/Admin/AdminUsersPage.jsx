import React, { useState, useEffect } from 'react';
import { adminAPI } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge, Button } from '../../components/Shared';

const AdminUsersPage = () => {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filter, setFilter] = useState('all');

    useEffect(() => {
        fetchUsers();
    }, []);

    const fetchUsers = async () => {
        try {
            setLoading(true);
            const response = await adminService.getTopCustomers();
            setUsers(response.data.data);
        } catch (err) {
            setError('Lỗi tải danh sách khách hàng');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <Loading />;

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-6xl mx-auto px-4">
                <h1 className="text-4xl font-bold mb-8 text-red-600">Quản lý khách hàng</h1>

                {error && <ErrorMessage message={error} />}

                <Card title={`Tổng ${users.length} khách hàng`}>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-100">
                                <tr>
                                    <th className="px-4 py-2 text-left">Tên</th>
                                    <th className="px-4 py-2 text-left">Email</th>
                                    <th className="px-4 py-2 text-left">SĐT</th>
                                    <th className="px-4 py-2 text-left">Hạng</th>
                                    <th className="px-4 py-2 text-left">Tổng tiêu</th>
                                    <th className="px-4 py-2 text-left">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.map(user => (
                                    <tr key={user.user_id} className="border-b hover:bg-gray-50">
                                        <td className="px-4 py-2 font-semibold">{user.name}</td>
                                        <td className="px-4 py-2">{user.email}</td>
                                        <td className="px-4 py-2">{user.phone || 'N/A'}</td>
                                        <td className="px-4 py-2">
                                            <Badge variant="info">{user.membership_tier || 'Bronze'}</Badge>
                                        </td>
                                        <td className="px-4 py-2 font-bold">{(user.total_spent || 0).toLocaleString('vi-VN')}đ</td>
                                        <td className="px-4 py-2">
                                            <button className="text-blue-600 hover:underline text-xs">Chi tiết</button>
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

export default AdminUsersPage;
