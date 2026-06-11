import React, { useState, useEffect } from 'react';
import { adminAPI } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge } from '../../components/Shared';

const AdminDashboard = () => {
    const [dashboard, setDashboard] = useState(null);
    const [reports, setReports] = useState({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        fetchDashboard();
    }, [activeTab]);

    const fetchDashboard = async () => {
        try {
            setLoading(true);
            const [dashboardRes, revenueRes, bestsellersRes, typesRes, performanceRes, retentionRes] = await Promise.all([
                adminService.getDashboard(),
                adminService.getRevenueReport('day'),
                adminService.getBestsellersReport(),
                adminService.getSalesByType(),
                adminService.getDeliveryPerformance(),
                adminService.getCustomerRetention(),
            ]);

            setDashboard(dashboardRes.data.data);
            setReports({
                revenue: revenueRes.data.data,
                bestsellers: bestsellersRes.data.data,
                types: typesRes.data.data,
                performance: performanceRes.data.data,
                retention: retentionRes.data.data,
            });
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
                <h1 className="text-4xl font-bold mb-8 text-red-600">Dashboard Quản lý</h1>

                {error && <ErrorMessage message={error} />}

                {/* Key Metrics */}
                {dashboard && (
                    <div className="grid md:grid-cols-4 gap-4 mb-8">
                        <Card>
                            <p className="text-sm text-gray-600 mb-2">Doanh thu hôm nay</p>
                            <p className="text-3xl font-bold text-green-600">
                                {Number(dashboard.today_revenue).toLocaleString('vi-VN')}đ
                            </p>
                        </Card>
                        <Card>
                            <p className="text-sm text-gray-600 mb-2">Tổng doanh thu</p>
                            <p className="text-3xl font-bold text-red-600">
                                {Number(dashboard.total_revenue).toLocaleString('vi-VN')}đ
                            </p>
                        </Card>
                        <Card>
                            <p className="text-sm text-gray-600 mb-2">Tổng đơn hàng</p>
                            <p className="text-3xl font-bold">{dashboard.total_orders}</p>
                        </Card>
                        <Card>
                            <p className="text-sm text-gray-600 mb-2">Khách hàng</p>
                            <p className="text-3xl font-bold">{dashboard.total_customers}</p>
                        </Card>
                    </div>
                )}

                {/* Tabs */}
                <div className="flex gap-2 mb-8 border-b">
                    {['overview', 'revenue', 'bestsellers', 'delivery', 'retention'].map(tab => (
                        <button
                            key={tab}
                            onClick={() => setActiveTab(tab)}
                            className={`px-4 py-2 font-semibold ${activeTab === tab ? 'border-b-2 border-red-600 text-red-600' : 'text-gray-600'}`}
                        >
                            {tab.charAt(0).toUpperCase() + tab.slice(1)}
                        </button>
                    ))}
                </div>

                {/* Overview Tab */}
                {activeTab === 'overview' && dashboard && (
                    <div className="grid md:grid-cols-2 gap-6">
                        <Card title="Giao hàng đang thực hiện">
                            <p className="text-2xl font-bold">{dashboard.active_deliveries}</p>
                        </Card>
                        <Card title="Hàng tồn kho thấp">
                            <p className="text-2xl font-bold text-yellow-600">{dashboard.low_stock_items}</p>
                        </Card>
                        <Card title="Top Dishes (Tháng này)">
                            <div className="space-y-2">
                                {dashboard.top_dishes?.map((dish, idx) => (
                                    <div key={idx} className="flex justify-between">
                                        <span>{dish.dish_name}</span>
                                        <span className="font-bold">{dish.total_sold} bán</span>
                                    </div>
                                ))}
                            </div>
                        </Card>
                        <Card title="Doanh thu (7 ngày gần nhất)">
                            <div className="space-y-2">
                                {dashboard.revenue_trend?.map((day, idx) => (
                                    <div key={idx} className="flex justify-between">
                                        <span>{day.date}</span>
                                        <span className="font-bold">{Number(day.revenue).toLocaleString('vi-VN')}đ</span>
                                    </div>
                                ))}
                            </div>
                        </Card>
                    </div>
                )}

                {/* Revenue Tab */}
                {activeTab === 'revenue' && reports.revenue && (
                    <Card title="Báo cáo doanh thu">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-100">
                                    <tr>
                                        <th className="px-4 py-2 text-left">Ngày</th>
                                        <th className="px-4 py-2 text-left">Doanh thu</th>
                                        <th className="px-4 py-2 text-left">Số đơn</th>
                                        <th className="px-4 py-2 text-left">Trung bình</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {reports.revenue.map((item, idx) => (
                                        <tr key={idx} className="border-b">
                                            <td className="px-4 py-2">{item.period}</td>
                                            <td className="px-4 py-2 font-bold">{Number(item.total_revenue).toLocaleString('vi-VN')}đ</td>
                                            <td className="px-4 py-2">{item.order_count}</td>
                                            <td className="px-4 py-2">{Number(item.average_order).toLocaleString('vi-VN')}đ</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}

                {/* Bestsellers Tab */}
                {activeTab === 'bestsellers' && reports.bestsellers && (
                    <Card title="Báo cáo bán chạy">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-100">
                                    <tr>
                                        <th className="px-4 py-2 text-left">Món ăn</th>
                                        <th className="px-4 py-2 text-left">Số bán</th>
                                        <th className="px-4 py-2 text-left">Doanh thu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {reports.bestsellers.map((item, idx) => (
                                        <tr key={idx} className="border-b">
                                            <td className="px-4 py-2">{item.dish_name}</td>
                                            <td className="px-4 py-2">{item.total_sold}</td>
                                            <td className="px-4 py-2 font-bold">{Number(item.total_revenue).toLocaleString('vi-VN')}đ</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}

                {/* Delivery Performance Tab */}
                {activeTab === 'delivery' && reports.performance && (
                    <Card title="Hiệu suất giao hàng">
                        <div className="space-y-4">
                            <div className="flex justify-between">
                                <span>Tỷ lệ đúng hạn</span>
                                <span className="font-bold text-green-600">{reports.performance.ontime_rate}%</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Tổng giao hàng</span>
                                <span className="font-bold">{reports.performance.total_deliveries}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Trung bình thời gian</span>
                                <span className="font-bold">{reports.performance.average_time} phút</span>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Retention Tab */}
                {activeTab === 'retention' && reports.retention && (
                    <Card title="Tỷ lệ giữ chân khách hàng">
                        <div className="space-y-4">
                            <div className="flex justify-between">
                                <span>Khách mới (tháng này)</span>
                                <span className="font-bold">{reports.retention.new_customers}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Khách quay lại</span>
                                <span className="font-bold">{reports.retention.returning_customers}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Tỷ lệ giữ chân</span>
                                <span className="font-bold text-green-600">{reports.retention.retention_rate}%</span>
                            </div>
                        </div>
                    </Card>
                )}
            </div>
        </div>
    );
};

export default AdminDashboard;
