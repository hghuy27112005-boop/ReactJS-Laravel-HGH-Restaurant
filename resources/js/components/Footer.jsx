import React from 'react';

const Footer = () => {
    return (
        <footer className="bg-gray-800 text-white mt-12">
            <div className="max-w-7xl mx-auto px-4 py-12">
                <div className="grid md:grid-cols-4 gap-8 mb-8">
                    <div>
                        <h3 className="text-xl font-bold mb-4">🍽️ Restaurant</h3>
                        <p className="text-gray-400">
                            Nhà hàng chuyên phục vụ các món ăn ngon với dịch vụ tốt nhất.
                        </p>
                    </div>
                    <div>
                        <h4 className="font-semibold mb-4">Liên kết</h4>
                        <ul className="space-y-2 text-gray-400">
                            <li><a href="/menu" className="hover:text-white">Thực đơn</a></li>
                            <li><a href="/bookings" className="hover:text-white">Đặt bàn</a></li>
                            <li><a href="/orders" className="hover:text-white">Đơn hàng</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 className="font-semibold mb-4">Hỗ trợ</h4>
                        <ul className="space-y-2 text-gray-400">
                            <li><a href="#" className="hover:text-white">Liên hệ</a></li>
                            <li><a href="#" className="hover:text-white">Điều khoản</a></li>
                            <li><a href="#" className="hover:text-white">Chính sách</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 className="font-semibold mb-4">Liên hệ</h4>
                        <ul className="space-y-2 text-gray-400">
                            <li>📍 Địa chỉ: 123 Nguyễn Huệ</li>
                            <li>📞 SĐT: 0123456789</li>
                            <li>📧 Email: info@restaurant.vn</li>
                        </ul>
                    </div>
                </div>
                <div className="border-t border-gray-700 pt-8 text-center text-gray-400">
                    <p>&copy; 2024 Restaurant Management System. All rights reserved.</p>
                </div>
            </div>
        </footer>
    );
};

export default Footer;
