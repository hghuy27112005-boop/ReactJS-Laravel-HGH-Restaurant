import React, { useState, useEffect } from 'react';
import { adminAPI } from '../../services/api';
import { Loading, ErrorMessage, Card } from '../../components/Shared';
import RevenueChart from '../../components/RevenueChart';

const AnalyticsDashboardPage = () => {
    const [dashboard, setDashboard] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchDashboard();
    }, []);

    const fetchDashboard = async () => {
        try {
            setLoading(true);
            const response = await adminService.getDashboard();
            setDashboard(response.data.data);
        } catch (err) {
            setError('Lỗi tải dashboard');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <Loading />;

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-7xl mx-auto px-4">
                <h1 className="text-4xl font-bold mb-8 text-red-600">📊 Phân tích</h1>

                {error && <ErrorMessage message={error} />}

                {dashboard && (
                    <div className="space-y-8">
                        {/* Key Metrics */}
                        <div className="grid md:grid-cols-4 gap-4">
                            <Card className="bg-gradient-to-br from-green-50 to-green-100 border-l-4 border-green-600">
                                <p className="text-sm text-gray-600">Doanh thu hôm nay</p>
                                <p className="text-3xl font-bold text-green-700">
                                    {Number(dashboard.today_revenue || 0).toLocaleString('vi-VN')}đ
                                </p>
                                <p className="text-xs text-green-600 mt-2">↑ 12% so với hôm qua</p>
                            </Card>

                            <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-l-4 border-blue-600">
                                <p className="text-sm text-gray-600">Đơn hôm nay</p>
                                <p className="text-3xl font-bold text-blue-700">
                                    {dashboard.total_orders || 0}
                                </p>
                                <p className="text-xs text-blue-600 mt-2">↑ 8% so với hôm qua</p>
                            </Card>

                            <Card className="bg-gradient-to-br from-purple-50 to-purple-100 border-l-4 border-purple-600">
                                <p className="text-sm text-gray-600">Giao hàng</p>
                                <p className="text-3xl font-bold text-purple-700">
                                    {dashboard.active_deliveries || 0}
                                </p>
                                <p className="text-xs text-purple-600 mt-2">Đang hoạt động</p>
                            </Card>

                            <Card className="bg-gradient-to-br from-orange-50 to-orange-100 border-l-4 border-orange-600">
                                <p className="text-sm text-gray-600">Khách thành viên</p>
                                <p className="text-3xl font-bold text-orange-700">
                                    {dashboard.membership_stats?.total_members || 0}
                                </p>
                                <p className="text-xs text-orange-600 mt-2">↑ 5% tháng này</p>
                            </Card>
                        </div>

                        {/* Revenue Chart */}
                        <div>
                            <RevenueChart />
                        </div>

                        {/* Best Sellers */}
                        <Card title="🏆 Sản phẩm bán chạy nhất">
                            <div className="space-y-3">
                                {dashboard.top_dishes?.map((dish, idx) => (
                                    <div key={idx} className="flex justify-between items-center pb-3 border-b last:border-b-0">
                                        <div>
                                            <p className="font-semibold">#{idx + 1} {dish.name}</p>
                                            <p className="text-sm text-gray-600">{dish.count} bán ra</p>
                                        </div>
                                        <p className="font-bold text-green-600">{(dish.revenue || 0).toLocaleString('vi-VN')}đ</p>
                                    </div>
                                )) || 'N/A'}
                            </div>
                        </Card>

                        {/* Membership Stats */}
                        <Card title="⭐ Thống kê thành viên">
                            <div className="grid md:grid-cols-5 gap-4">
                                {[
                                    { tier: 'Bronze', count: 45, color: 'bg-orange-100 text-orange-700' },
                                    { tier: 'Silver', count: 32, color: 'bg-gray-100 text-gray-700' },
                                    { tier: 'Gold', count: 18, color: 'bg-yellow-100 text-yellow-700' },
                                    { tier: 'Platinum', count: 8, color: 'bg-blue-100 text-blue-700' },
                                    { tier: 'Diamond', count: 2, color: 'bg-purple-100 text-purple-700' },
                                ].map((tier, idx) => (
                                    <div key={idx} className={`p-4 rounded-lg text-center ${tier.color}`}>
                                        <p className="text-2xl font-bold">{tier.count}</p>
                                        <p className="text-sm font-semibold mt-1">{tier.tier}</p>
                                    </div>
                                ))}
                            </div>
                        </Card>

                        {/* Performance Metrics */}
                        <div className="grid md:grid-cols-2 gap-6">
                            <Card title="📦 Hiệu suất giao hàng">
                                <div className="space-y-4">
                                    <div>
                                        <div className="flex justify-between text-sm mb-1">
                                            <span>Đúng giờ</span>
                                            <span className="font-bold">92%</span>
                                        </div>
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div className="bg-green-600 h-2 rounded-full" style={{ width: '92%' }} />
                                        </div>
                                    </div>
                                    <div>
                                        <div className="flex justify-between text-sm mb-1">
                                            <span>Hài lòng khách</span>
                                            <span className="font-bold">88%</span>
                                        </div>
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div className="bg-blue-600 h-2 rounded-full" style={{ width: '88%' }} />
                                        </div>
                                    </div>
                                    <div>
                                        <div className="flex justify-between text-sm mb-1">
                                            <span>Hoàn thành</span>
                                            <span className="font-bold">99%</span>
                                        </div>
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div className="bg-purple-600 h-2 rounded-full" style={{ width: '99%' }} />
                                        </div>
                                    </div>
                                </div>
                            </Card>

                            <Card title="💰 Tổng quan tài chính">
                                <div className="space-y-3">
                                    <div className="flex justify-between pb-3 border-b">
                                        <span className="text-gray-600">Tổng doanh thu tháng</span>
                                        <span className="font-bold">450,000,000đ</span>
                                    </div>
                                    <div className="flex justify-between pb-3 border-b">
                                        <span className="text-gray-600">Lợi nhuận ròng</span>
                                        <span className="font-bold text-green-600">180,000,000đ</span>
                                    </div>
                                    <div className="flex justify-between pb-3 border-b">
                                        <span className="text-gray-600">Chi phí hoạt động</span>
                                        <span className="font-bold text-red-600">270,000,000đ</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Margin lợi nhuận</span>
                                        <span className="font-bold">40%</span>
                                    </div>
                                </div>
                            </Card>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default AnalyticsDashboardPage;
