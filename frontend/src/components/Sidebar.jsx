import React, { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';

const Sidebar = ({ isOpen, onClose }) => {
    const navigate = useNavigate();
    const location = useLocation();

    const menuItems = [
        { path: '/admin/dashboard', icon: '📊', label: 'Dashboard' },
        { path: '/admin/users', icon: '👥', label: 'Khách hàng' },
        { path: '/admin/stock', icon: '📦', label: 'Kho hàng' },
        { path: '/admin/deliveries', icon: '🚗', label: 'Giao hàng' },
        { path: '/admin/sales', icon: '📈', label: 'Bán hàng' },
        { path: '/admin/analytics', icon: '📉', label: 'Phân tích' },
        { path: '/admin/reports', icon: '📋', label: 'Báo cáo' },
    ];

    return (
        <>
            {/* Overlay */}
            {isOpen && (
                <div
                    className="fixed inset-0 bg-black bg-opacity-50 md:hidden z-30"
                    onClick={onClose}
                />
            )}

            {/* Sidebar */}
            <aside
                className={`fixed left-0 top-0 h-screen w-64 bg-gray-900 text-white overflow-y-auto transform transition-transform duration-300 z-40 ${
                    isOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
                }`}
            >
                {/* Header */}
                <div className="p-6 border-b border-gray-800">
                    <h2 className="text-2xl font-bold">🍽️ Admin</h2>
                    <p className="text-sm text-gray-400 mt-1">Restaurant Mgmt</p>
                </div>

                {/* Menu */}
                <nav className="p-4 space-y-2">
                    {menuItems.map(item => (
                        <button
                            key={item.path}
                            onClick={() => {
                                navigate(item.path);
                                onClose();
                            }}
                            className={`w-full text-left px-4 py-3 rounded-lg transition ${
                                location.pathname === item.path
                                    ? 'bg-red-600 text-white'
                                    : 'text-gray-300 hover:bg-gray-800'
                            }`}
                        >
                            <span className="text-lg mr-2">{item.icon}</span>
                            {item.label}
                        </button>
                    ))}
                </nav>

                {/* Footer */}
                <div className="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-800">
                    <button className="w-full px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm">
                        🚪 Đăng xuất
                    </button>
                </div>
            </aside>
        </>
    );
};

export default Sidebar;
