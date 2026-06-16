import axios from 'axios';

// Base API URL
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const axiosInstance = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Add token to request if exists
axiosInstance.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Handle response errors
axiosInstance.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Token expired or invalid
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

// Auth API
export const authAPI = {
    register: (data) => axiosInstance.post('/register', data),
    login: (username, password) => axiosInstance.post('/login', { username, password }),
    logout: () => axiosInstance.post('/logout'),
    forgotPassword: (email) => axiosInstance.post('/forgot-password', { email }),
};

// User API
export const userAPI = {
    getProfile: () => axiosInstance.get('/user'),
    updateProfile: (data) => axiosInstance.put('/user', data),
    uploadAvatar: (formData) =>
        axiosInstance.post('/user/avatar', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        }),
};

// Bills API
export const billAPI = {
    getAll: (filters = {}) => axiosInstance.get('/bills', { params: filters }),
    create: (data) => axiosInstance.post('/bills', data),
    getById: (id) => axiosInstance.get(`/bills/${id}`),
    update: (id, data) => axiosInstance.put(`/bills/${id}`, data),
    delete: (id) => axiosInstance.delete(`/bills/${id}`),
    calculateTotal: (id) => axiosInstance.post(`/bills/${id}/calculate-total`),
    processPayment: (id, paymentData) =>
        axiosInstance.post(`/bills/${id}/payment`, paymentData),
};

// User order history (session cart / Blade checkout flow)
export const myBillsAPI = {
    getAll: (filters = {}) => axiosInstance.get('/my-bills', { params: filters }),
};

/** @returns {Array} list from API response */
export const extractListData = (response) => {
    const payload = response?.data;
    if (Array.isArray(payload?.data)) return payload.data;
    if (Array.isArray(payload)) return payload;
    return [];
};

// Orders API
export const orderAPI = {
    create: (data) => axiosInstance.post('/orders', data),
    addToBill: (billId, data) =>
        axiosInstance.post(`/orders/bill/${billId}`, data),
    getById: (id) => axiosInstance.get(`/orders/${id}`),
    update: (id, data) => axiosInstance.put(`/orders/${id}`, data),
    delete: (id) => axiosInstance.delete(`/orders/${id}`),
};

// Deliveries API
export const deliveryAPI = {
    getAll: (filters = {}) => axiosInstance.get('/deliveries', { params: filters }),
    create: (data) => axiosInstance.post('/deliveries', data),
    getById: (id) => axiosInstance.get(`/deliveries/${id}`),
    approve: (id) => axiosInstance.post(`/deliveries/${id}/approve`),
    startDelivery: (id) =>
        axiosInstance.post(`/deliveries/${id}/start`),
    complete: (id) =>
        axiosInstance.post(`/deliveries/${id}/complete`),
};

// Booking Tables API
export const bookingTableAPI = {
    getAll: () => axiosInstance.get('/booking-tables'),
    create: (data) => axiosInstance.post('/booking-tables', data),
    getById: (id) => axiosInstance.get(`/booking-tables/${id}`),
    update: (id, data) => axiosInstance.put(`/booking-tables/${id}`, data),
    delete: (id) => axiosInstance.delete(`/booking-tables/${id}`),
    getAvailable: (date, time) =>
        axiosInstance.get('/booking-tables/available', {
            params: { date, time },
        }),
};

// Dishes API
export const dishAPI = {
    getAll: (filters = {}) =>
        axiosInstance.get('/dishes', { params: filters }),
    getById: (id) => axiosInstance.get(`/dishes/${id}`),
    getDishTypes: () => axiosInstance.get('/dish-types'),
};

// Points API
export const pointsAPI = {
    getUserPoints: () => axiosInstance.get('/points'),
};

// Statistics API
export const statisticsAPI = {
    getUserStats: () => axiosInstance.get('/statistics/user'),
};

// Discounts API
export const discountAPI = {
    getUserDiscounts: () => axiosInstance.get('/discounts'),
    getByMembership: (membership) =>
        axiosInstance.get(`/discounts/membership/${membership}`),
};

// Promotions API
export const promotionAPI = {
    getAll: () => axiosInstance.get('/sale-off-events'),
    getById: (id) => axiosInstance.get(`/sale-off-events/${id}`),
};

// Admin APIs
export const adminAPI = {
    dashboard: {
        get: () => axiosInstance.get('/admin/dashboard'),
    },
    bills: {
        getAll: (filters = {}) =>
            axiosInstance.get('/admin/bills', { params: filters }),
        update: (id, data) =>
            axiosInstance.put(`/admin/bills/${id}`, data),
    },
    deliveries: {
        getAll: (filters = {}) =>
            axiosInstance.get('/admin/deliveries', { params: filters }),
        updateStatus: (id, status) =>
            axiosInstance.put(`/admin/deliveries/${id}/status`, { status }),
    },
    stock: {
        getAll: () => axiosInstance.get('/admin/stocks'),
        create: (data) => axiosInstance.post('/admin/stocks', data),
        update: (id, data) =>
            axiosInstance.put(`/admin/stocks/${id}`, data),
        getLowStock: () =>
            axiosInstance.get('/admin/stocks/low-stock'),
    },
    users: {
        getAll: (filters = {}) =>
            axiosInstance.get('/admin/users', { params: filters }),
        update: (id, data) =>
            axiosInstance.put(`/admin/users/${id}`, data),
    },
    statistics: {
        revenue: (filters = {}) =>
            axiosInstance.get('/admin/statistics/revenue', { params: filters }),
        bestsellers: () =>
            axiosInstance.get('/admin/statistics/bestsellers'),
        customers: (filters = {}) =>
            axiosInstance.get('/admin/statistics/customers', { params: filters }),
    },
};

// Aliases used by page components (defined after underlying APIs)
export const billService = {
    getBills: (filters) => myBillsAPI.getAll(filters),
    storeBill: (data) => billAPI.create(data),
    processPayment: (id, data) => billAPI.processPayment(id, data),
};

export const bookingService = {
    getBookings: () => myBillsAPI.getAll({ order_type: 'dat-ban' }),
    createBooking: (data) => bookingTableAPI.create(data),
    updateBooking: (id, data) => bookingTableAPI.update(id, data),
    deleteBooking: (id) => bookingTableAPI.delete(id),
    cancelBooking: (id) => bookingTableAPI.delete(id),
};

export const deliveryService = {
    getDeliveries: () => myBillsAPI.getAll({ order_type: 'mang-ve' }),
    approveDelivery: (id) => deliveryAPI.approve(id),
    startDelivery: (id) => deliveryAPI.startDelivery(id),
};

export default axiosInstance;
