import React, { useState } from 'react';
import { useAuthContext } from '../../context/AuthContext';
import { ErrorMessage, Button } from '../../components/Shared';

const LoginPage = () => {
    const { login, loading, error } = useAuthContext();
    const [formData, setFormData] = useState({ email: '', password: '' });
    const [localError, setLocalError] = useState(null);

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLocalError(null);

        if (!formData.email || !formData.password) {
            setLocalError('Vui lòng điền đầy đủ thông tin');
            return;
        }

        const success = await login(formData.email, formData.password);
        if (success) {
            window.location.href = '/';
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-100 px-4">
            <div className="bg-white rounded-lg shadow-lg p-8 max-w-md w-full">
                <h1 className="text-3xl font-bold text-center mb-2 text-red-600">Đăng nhập</h1>
                <p className="text-center text-gray-600 mb-6">Chào mừng quay lại nhà hàng của chúng tôi</p>

                {error && <ErrorMessage message={error} />}
                {localError && <ErrorMessage message={localError} />}

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-semibold mb-2">Email</label>
                        <input
                            type="email"
                            name="email"
                            value={formData.email}
                            onChange={handleChange}
                            placeholder="you@example.com"
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
                            placeholder="••••••••"
                            className="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-red-600"
                        />
                    </div>

                    <Button
                        type="submit"
                        disabled={loading}
                        className="w-full"
                    >
                        {loading ? 'Đang đăng nhập...' : 'Đăng nhập'}
                    </Button>
                </form>

                <div className="mt-6 text-center">
                    <p className="text-gray-600">
                        Chưa có tài khoản?{' '}
                        <a href="/register" className="text-red-600 hover:underline font-semibold">
                            Đăng ký ngay
                        </a>
                    </p>
                </div>

                <div className="mt-4 text-center">
                    <a href="/forgot-password" className="text-red-600 hover:underline text-sm">
                        Quên mật khẩu?
                    </a>
                </div>
            </div>
        </div>
    );
};

export default LoginPage;
