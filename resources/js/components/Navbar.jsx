import React from 'react';
import { useAuthContext } from '../context/AuthContext';

const Navbar = () => {
    const { user, logout, isAuthenticated } = useAuthContext();
    const [menuOpen, setMenuOpen] = React.useState(false);

    return (
        <nav className="bg-white shadow-lg sticky top-0 z-40">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center h-16">
                    {/* Logo */}
                    <a href="/" className="text-2xl font-bold text-red-600">
                        🍽️ Restaurant
                    </a>

                    {/* Desktop Menu */}
                    <div className="hidden md:flex items-center gap-6">
                        <a href="/menu" className="text-gray-700 hover:text-red-600">Thực đơn</a>
                        <a href="/dishes" className="text-gray-700 hover:text-red-600">Tất cả món</a>
                        
                        {isAuthenticated ? (
                            <>
                                <a href="/cart" className="text-gray-700 hover:text-red-600">🛒 Giỏ hàng</a>
                                <a href="/orders" className="text-gray-700 hover:text-red-600">Đơn hàng</a>
                                <a href="/bookings" className="text-gray-700 hover:text-red-600">Đặt bàn</a>
                                
                                <div className="relative group">
                                    <button className="text-gray-700 hover:text-red-600 flex items-center gap-2">
                                        👤 {user?.name}
                                    </button>
                                    <div className="hidden group-hover:block absolute right-0 bg-white shadow-lg rounded mt-2 w-48">
                                        <a href="/profile" className="block px-4 py-2 hover:bg-gray-100">Hồ sơ</a>
                                        <a href="/statistics" className="block px-4 py-2 hover:bg-gray-100">Thống kê</a>
                                        <a href="/points" className="block px-4 py-2 hover:bg-gray-100">Điểm thành viên</a>
                                        {user?.authority === 'Admin' && (
                                            <a href="/admin" className="block px-4 py-2 hover:bg-gray-100 border-t">Quản lý</a>
                                        )}
                                        <button
                                            onClick={() => logout()}
                                            className="w-full text-left px-4 py-2 hover:bg-gray-100 border-t text-red-600"
                                        >
                                            Đăng xuất
                                        </button>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <>
                                <a href="/login" className="text-gray-700 hover:text-red-600">Đăng nhập</a>
                                <a href="/register" className="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                    Đăng ký
                                </a>
                            </>
                        )}
                    </div>

                    {/* Mobile Menu Button */}
                    <button 
                        className="md:hidden"
                        onClick={() => setMenuOpen(!menuOpen)}
                    >
                        ☰
                    </button>
                </div>

                {/* Mobile Menu */}
                {menuOpen && (
                    <div className="md:hidden pb-4">
                        <a href="/menu" className="block py-2 text-gray-700">Thực đơn</a>
                        <a href="/dishes" className="block py-2 text-gray-700">Tất cả món</a>
                        {isAuthenticated ? (
                            <>
                                <a href="/cart" className="block py-2 text-gray-700">Giỏ hàng</a>
                                <a href="/orders" className="block py-2 text-gray-700">Đơn hàng</a>
                                <a href="/bookings" className="block py-2 text-gray-700">Đặt bàn</a>
                                <a href="/profile" className="block py-2 text-gray-700">Hồ sơ</a>
                                {user?.authority === 'Admin' && (
                                    <a href="/admin" className="block py-2 text-gray-700">Quản lý</a>
                                )}
                                <button onClick={() => logout()} className="block w-full text-left py-2 text-red-600">
                                    Đăng xuất
                                </button>
                            </>
                        ) : (
                            <>
                                <a href="/login" className="block py-2 text-gray-700">Đăng nhập</a>
                                <a href="/register" className="block py-2 text-gray-700">Đăng ký</a>
                            </>
                        )}
                    </div>
                )}
            </div>
        </nav>
    );
};

export default Navbar;
