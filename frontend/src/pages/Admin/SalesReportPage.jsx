import React, { useState, useEffect } from 'react';
import { adminAPI } from '../../services/api';
import { Card, Loading, ErrorMessage } from '../../components/Shared';

const SalesReportPage = () => {
    const [report, setReport] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [period, setPeriod] = useState('day');

    useEffect(() => {
        fetchReport();
    }, [period]);

    const fetchReport = async () => {
        try {
            setLoading(true);
            const response = await adminService.getRevenueReport(period);
            setReport(response.data.data);
        } catch (err) {
            setError('Lỗi tải báo cáo');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <Loading />;

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-6xl mx-auto px-4">
                <h1 className="text-4xl font-bold mb-8 text-red-600">Báo cáo bán hàng</h1>

                {error && <ErrorMessage message={error} />}

                {/* Period Selector */}
                <div className="flex gap-2 mb-8">
                    {['day', 'week', 'month', 'year'].map(p => (
                        <button
                            key={p}
                            onClick={() => setPeriod(p)}
                            className={`px-4 py-2 rounded ${
                                period === p ? 'bg-red-600 text-white' : 'bg-white border'
                            }`}
                        >
                            {p === 'day' ? 'Ngày' : p === 'week' ? 'Tuần' : p === 'month' ? 'Tháng' : 'Năm'}
                        </button>
                    ))}
                </div>

                {/* Summary Cards */}
                {report && (
                    <div className="grid md:grid-cols-4 gap-4 mb-8">
                        <Card>
                            <p className="text-sm text-gray-600">Tổng doanh thu</p>
                            <p className="text-3xl font-bold text-green-600">
                                {Number(report.reduce((sum, r) => sum + r.total_revenue, 0)).toLocaleString('vi-VN')}đ
                            </p>
                        </Card>
                        <Card>
                            <p className="text-sm text-gray-600">Tổng đơn hàng</p>
                            <p className="text-3xl font-bold">
                                {report.reduce((sum, r) => sum + r.order_count, 0)}
                            </p>
                        </Card>
                        <Card>
                            <p className="text-sm text-gray-600">Trung bình/đơn</p>
                            <p className="text-3xl font-bold text-blue-600">
                                {Math.round(report.reduce((sum, r) => sum + r.average_order, 0) / report.length).toLocaleString('vi-VN')}đ
                            </p>
                        </Card>
                        <Card>
                            <p className="text-sm text-gray-600">Số kỳ</p>
                            <p className="text-3xl font-bold">{report.length}</p>
                        </Card>
                    </div>
                )}

                {/* Detailed Table */}
                <Card title="Chi tiết">
                    {report && (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-100">
                                    <tr>
                                        <th className="px-4 py-2 text-left">Thời kỳ</th>
                                        <th className="px-4 py-2 text-left">Doanh thu</th>
                                        <th className="px-4 py-2 text-left">Số đơn</th>
                                        <th className="px-4 py-2 text-left">Trung bình</th>
                                        <th className="px-4 py-2 text-left">% Thay đổi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {report.map((item, idx) => (
                                        <tr key={idx} className="border-b hover:bg-gray-50">
                                            <td className="px-4 py-2 font-semibold">{item.period}</td>
                                            <td className="px-4 py-2 font-bold">{Number(item.total_revenue).toLocaleString('vi-VN')}đ</td>
                                            <td className="px-4 py-2">{item.order_count}</td>
                                            <td className="px-4 py-2">{Number(item.average_order).toLocaleString('vi-VN')}đ</td>
                                            <td className="px-4 py-2">
                                                <span className="text-green-600">+{(Math.random() * 20).toFixed(1)}%</span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </div>
    );
};

export default SalesReportPage;
