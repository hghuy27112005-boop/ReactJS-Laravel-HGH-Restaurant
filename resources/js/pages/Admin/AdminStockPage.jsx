import React, { useState, useEffect } from 'react';
import { adminAPI } from '../../services/api';
import { Loading, ErrorMessage, Card, Badge, Button } from '../../components/Shared';

const AdminStockPage = () => {
    const [stock, setStock] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filter, setFilter] = useState('all');

    useEffect(() => {
        fetchStock();
    }, []);

    const fetchStock = async () => {
        try {
            setLoading(true);
            // Simulated stock data
            const mockStock = [
                { stock_id: 1, dish_name: 'Margherita Pizza', quantity_left: 5, quantity_start: 50, cost_per_unit: 32000, needs_restock: true },
                { stock_id: 2, dish_name: 'Carbonara Pasta', quantity_left: 15, quantity_start: 50, cost_per_unit: 34000, needs_restock: false },
                { stock_id: 3, dish_name: 'Caesar Salad', quantity_left: 25, quantity_start: 40, cost_per_unit: 22000, needs_restock: false },
                { stock_id: 4, dish_name: 'Tiramisu', quantity_left: 2, quantity_start: 30, cost_per_unit: 18000, needs_restock: true },
                { stock_id: 5, dish_name: 'Coca Cola', quantity_left: 50, quantity_start: 100, cost_per_unit: 6000, needs_restock: false },
            ];
            setStock(mockStock);
        } catch (err) {
            setError('Lỗi tải kho hàng');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const getFiltered = () => {
        if (filter === 'low') return stock.filter(s => s.quantity_left < 10);
        if (filter === 'out') return stock.filter(s => s.quantity_left === 0);
        return stock;
    };

    if (loading) return <Loading />;

    const filtered = getFiltered();
    const lowStockCount = stock.filter(s => s.quantity_left < 10).length;
    const outOfStockCount = stock.filter(s => s.quantity_left === 0).length;

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-6xl mx-auto px-4">
                <h1 className="text-4xl font-bold mb-8 text-red-600">Quản lý kho hàng</h1>

                {error && <ErrorMessage message={error} />}

                {/* Stats */}
                <div className="grid md:grid-cols-3 gap-4 mb-8">
                    <Card>
                        <p className="text-sm text-gray-600">Tổng sản phẩm</p>
                        <p className="text-3xl font-bold">{stock.length}</p>
                    </Card>
                    <Card className="bg-yellow-50">
                        <p className="text-sm text-gray-600">Sắp hết hàng</p>
                        <p className="text-3xl font-bold text-yellow-600">{lowStockCount}</p>
                    </Card>
                    <Card className="bg-red-50">
                        <p className="text-sm text-gray-600">Hết hàng</p>
                        <p className="text-3xl font-bold text-red-600">{outOfStockCount}</p>
                    </Card>
                </div>

                {/* Filters */}
                <div className="flex gap-2 mb-6">
                    <button
                        onClick={() => setFilter('all')}
                        className={`px-4 py-2 rounded ${filter === 'all' ? 'bg-red-600 text-white' : 'bg-white border'}`}
                    >
                        Tất cả
                    </button>
                    <button
                        onClick={() => setFilter('low')}
                        className={`px-4 py-2 rounded ${filter === 'low' ? 'bg-red-600 text-white' : 'bg-white border'}`}
                    >
                        Sắp hết ({lowStockCount})
                    </button>
                    <button
                        onClick={() => setFilter('out')}
                        className={`px-4 py-2 rounded ${filter === 'out' ? 'bg-red-600 text-white' : 'bg-white border'}`}
                    >
                        Hết hàng ({outOfStockCount})
                    </button>
                </div>

                {/* Stock Table */}
                <Card title="Tồn kho">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-100">
                                <tr>
                                    <th className="px-4 py-2 text-left">Tên sản phẩm</th>
                                    <th className="px-4 py-2 text-left">Tồn lại</th>
                                    <th className="px-4 py-2 text-left">Khởi đầu</th>
                                    <th className="px-4 py-2 text-left">Sử dụng</th>
                                    <th className="px-4 py-2 text-left">% Sử dụng</th>
                                    <th className="px-4 py-2 text-left">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filtered.map(item => {
                                    const used = item.quantity_start - item.quantity_left;
                                    const percent = Math.round((used / item.quantity_start) * 100);

                                    return (
                                        <tr key={item.stock_id} className="border-b hover:bg-gray-50">
                                            <td className="px-4 py-2 font-semibold">{item.dish_name}</td>
                                            <td className="px-4 py-2 font-bold">{item.quantity_left}</td>
                                            <td className="px-4 py-2">{item.quantity_start}</td>
                                            <td className="px-4 py-2">{used}</td>
                                            <td className="px-4 py-2">
                                                <div className="flex items-center gap-2">
                                                    <div className="w-24 bg-gray-200 rounded h-2">
                                                        <div
                                                            className="bg-red-600 h-2 rounded"
                                                            style={{ width: `${percent}%` }}
                                                        />
                                                    </div>
                                                    <span className="text-xs">{percent}%</span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-2">
                                                {item.quantity_left === 0 ? (
                                                    <Badge variant="danger">✕ Hết</Badge>
                                                ) : item.quantity_left < 10 ? (
                                                    <Badge variant="warning">⚠️ Sắp hết</Badge>
                                                ) : (
                                                    <Badge variant="success">✓ OK</Badge>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </div>
    );
};

export default AdminStockPage;
