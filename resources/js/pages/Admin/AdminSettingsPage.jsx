import React, { useState } from 'react';
import { Card, Button, Badge } from '../../components/Shared';

const AdminSettingsPage = () => {
    const [settings, setSettings] = useState({
        restaurant_name: 'Nhà hàng Ăn ngon',
        restaurant_phone: '(84) 123 456 789',
        restaurant_email: 'hello@restaurant.test',
        restaurant_address: '123 Nguyễn Huệ, TP.HCM',
        delivery_fee_base: 25000,
        delivery_fee_per_km: 5000,
        min_order_value: 50000,
        business_hours_start: '10:00',
        business_hours_end: '22:00',
        auto_confirm_orders: false,
        allow_reviews: true,
        max_daily_orders: 500,
    });

    const [saved, setSaved] = useState(false);

    const handleChange = (key, value) => {
        setSettings(prev => ({ ...prev, [key]: value }));
        setSaved(false);
    };

    const handleSave = async () => {
        try {
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 500));
            setSaved(true);
            setTimeout(() => setSaved(false), 3000);
        } catch (err) {
            console.error(err);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-4xl mx-auto px-4">
                <h1 className="text-4xl font-bold mb-8 text-red-600">⚙️ Cài đặt</h1>

                {saved && (
                    <div className="mb-6 p-4 bg-green-100 text-green-700 rounded-lg font-semibold">
                        ✓ Cài đặt đã được lưu thành công
                    </div>
                )}

                <div className="space-y-6">
                    {/* General Settings */}
                    <Card title="ℹ️ Thông tin chung">
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-semibold mb-2">Tên nhà hàng</label>
                                <input
                                    type="text"
                                    value={settings.restaurant_name}
                                    onChange={(e) => handleChange('restaurant_name', e.target.value)}
                                    className="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                                />
                            </div>
                            <div className="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-semibold mb-2">Số điện thoại</label>
                                    <input
                                        type="tel"
                                        value={settings.restaurant_phone}
                                        onChange={(e) => handleChange('restaurant_phone', e.target.value)}
                                        className="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold mb-2">Email</label>
                                    <input
                                        type="email"
                                        value={settings.restaurant_email}
                                        onChange={(e) => handleChange('restaurant_email', e.target.value)}
                                        className="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-semibold mb-2">Địa chỉ</label>
                                <textarea
                                    value={settings.restaurant_address}
                                    onChange={(e) => handleChange('restaurant_address', e.target.value)}
                                    className="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                                    rows="3"
                                />
                            </div>
                        </div>
                    </Card>

                    {/* Delivery Settings */}
                    <Card title="🚗 Cài đặt giao hàng">
                        <div className="space-y-4">
                            <div className="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-semibold mb-2">Phí giao hàng cơ bản</label>
                                    <input
                                        type="number"
                                        value={settings.delivery_fee_base}
                                        onChange={(e) => handleChange('delivery_fee_base', parseInt(e.target.value))}
                                        className="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-semibold mb-2">Phí/km</label>
                                    <input
                                        type="number"
                                        value={settings.delivery_fee_per_km}
                                        onChange={(e) => handleChange('delivery_fee_per_km', parseInt(e.target.value))}
                                        className="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-semibold mb-2">Đơn hàng tối thiểu</label>
                                <input
                                    type="number"
                                    value={settings.min_order_value}
                                    onChange={(e) => handleChange('min_order_value', parseInt(e.target.value))}
                                    className="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                                />
                            </div>
                        </div>
                    </Card>

                    {/* Business Hours */}
                    <Card title="🕐 Giờ hoạt động">
                        <div className="grid md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-semibold mb-2">Mở từ</label>
                                <input
                                    type="time"
                                    value={settings.business_hours_start}
                                    onChange={(e) => handleChange('business_hours_start', e.target.value)}
                                    className="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-semibold mb-2">Đóng lúc</label>
                                <input
                                    type="time"
                                    value={settings.business_hours_end}
                                    onChange={(e) => handleChange('business_hours_end', e.target.value)}
                                    className="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                                />
                            </div>
                        </div>
                    </Card>

                    {/* System Settings */}
                    <Card title="⚙️ Cài đặt hệ thống">
                        <div className="space-y-4">
                            <label className="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={settings.auto_confirm_orders}
                                    onChange={(e) => handleChange('auto_confirm_orders', e.target.checked)}
                                    className="w-5 h-5 rounded"
                                />
                                <span>Tự động xác nhận đơn hàng</span>
                            </label>
                            <label className="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={settings.allow_reviews}
                                    onChange={(e) => handleChange('allow_reviews', e.target.checked)}
                                    className="w-5 h-5 rounded"
                                />
                                <span>Cho phép đánh giá từ khách hàng</span>
                            </label>
                            <div>
                                <label className="block text-sm font-semibold mb-2">Số đơn tối đa/ngày</label>
                                <input
                                    type="number"
                                    value={settings.max_daily_orders}
                                    onChange={(e) => handleChange('max_daily_orders', parseInt(e.target.value))}
                                    className="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                                />
                            </div>
                        </div>
                    </Card>

                    {/* Save Button */}
                    <div className="flex justify-end gap-4">
                        <Button variant="secondary">Hủy</Button>
                        <Button onClick={handleSave}>💾 Lưu cài đặt</Button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AdminSettingsPage;