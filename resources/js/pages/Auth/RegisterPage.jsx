import React, { useState } from 'react';
import { useAuthContext } from '../../context/AuthContext';
import { ErrorMessage, Button } from '../../components/Shared';

const RegisterPage = () => {
    const { register, loading, error } = useAuthContext();
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });
    const [localError, setLocalError] = useState(null);

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLocalError(null);

        if (Object.values(formData).some(v => !v)) {
            setLocalError('Vui lòng điền đầy đủ thông tin');
            return;
        }

        if (formData.password !== formData.password_confirmation) {
            setLocalError('Mật khẩu không trùng khớp');
            return;
        }

        if (formData.password.length < 8) {
            setLocalError('Mật khẩu phải ít nhất 8 ký tự');
            return;
        }

        const success = await register(formData);
        if (success) {
            window.location.href = '/';
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-8">
            <div className="bg-white rounded-lg shadow-lg p-8 max-w-md w-full">
                <h1 className="text-3xl font-bold text-center mb-2 text-red-600">Đăng ký</h1>
                <p className="text-center text-gray-600 mb-6">Tạo tài khoản để tận hưởng tiện ích</p>

                {error && <ErrorMessage message={error} />}
                {localError && <ErrorMessage message={localError} />}

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-semibold mb-2">Họ tên</label>
                        <input
                            type="text"
                            name="name"
                            value={formData.name}
                            onChange={handleChange}
                            className="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-red-600"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-semibold mb-2">Email</label>
                        <input
                            type="email"
                            name="email"
                            value={formData.email}
                            onChange={handleChange}
                            className="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-red-600"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-semibold mb-2">Số điện thoại</label>
                        <input
                            type="tel"
                            name="phone"
                            value={formData.phone}
                            onChange={handleChange}
                            className="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-red-600"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-semibold mb-2">Mật khẩu</label>
                        <input
                            type="password"
                            name="password"
                            value={formData.password}
                            onChange={handleChange}
                            className="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-red-600"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-semibold mb-2">Xác nhận mật khẩu</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            value={formData.password_confirmation}
                            onChange={handleChange}
                            className="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-red-600"
                        />
                    </div>

                    <Button
                        type="submit"
                        disabled={loading}
                        className="w-full"
                    >
                        {loading ? 'Đang đăng ký...' : 'Đăng ký'}
                    </Button>
                </form>

                <div className="mt-6 text-center">
                    <p className="text-gray-600">
                        Đã có tài khoản?{' '}
                        <a href="/login" className="text-red-600 hover:underline font-semibold">
                            Đăng nhập
                        </a>
                    </p>
                </div>
            </div>
        </div>
    );
};

export default RegisterPage;
