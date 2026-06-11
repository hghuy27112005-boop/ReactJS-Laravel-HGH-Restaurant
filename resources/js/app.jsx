import './bootstrap';
import '../css/app.css';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import { CartProvider } from './context/CartContext';
import Layout from './components/Layout';
import ProtectedRoute from './components/ProtectedRoute';

// Pages
import HomeApp from './pages/Home/HomeApp';
import LoginPage from './pages/Auth/LoginPage';
import RegisterPage from './pages/Auth/RegisterPage';
import MenuPage from './pages/Menu/MenuPage';
import DishDetailPage from './pages/Menu/DishDetailPage';
import ProfilePage from './pages/User/ProfilePage';
import StatisticsPage from './pages/User/StatisticsPage';
import PointsPage from './pages/User/PointsPage';
import BookingsPage from './pages/Booking/BookingsPage';
import BookingFormPage from './pages/Booking/BookingFormPage';
import BookingListPage from './pages/Booking/BookingListPage';
import OrdersPage from './pages/Order/OrdersPage';
import OrderConfirmationPage from './pages/Order/OrderConfirmationPage';
import OrderTrackingPage from './pages/Order/OrderTrackingPage';
import CartPage from './pages/Order/CartPage';
import CheckoutPage from './pages/Order/CheckoutPage';
import DeliveriesPage from './pages/Delivery/DeliveriesPage';
import AdminDashboard from './pages/Admin/AdminDashboard';
import AdminUsersPage from './pages/Admin/AdminUsersPage';
import AdminStockPage from './pages/Admin/AdminStockPage';
import AdminDeliveriesPage from './pages/Admin/AdminDeliveriesPage';
import SalesReportPage from './pages/Admin/SalesReportPage';
import AnalyticsDashboardPage from './pages/Admin/AnalyticsDashboardPage';
import AdminSettingsPage from './pages/Admin/AdminSettingsPage';
import FavoriteDishesPage from './pages/User/FavoriteDishesPage';
import UserNotificationsPage from './pages/User/UserNotificationsPage';

// Main App Component with Router
function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <CartProvider>
          <Layout>
            <Routes>
              {/* Home */}
              <Route path="/" element={<HomeApp />} />

              {/* Auth */}
              <Route path="/login" element={<LoginPage />} />
              <Route path="/register" element={<RegisterPage />} />

              {/* Menu */}
              <Route path="/menu" element={<MenuPage />} />
              <Route path="/menu/:dishId" element={<DishDetailPage />} />

              {/* User Routes */}
              <Route path="/profile" element={<ProtectedRoute><ProfilePage /></ProtectedRoute>} />
              <Route path="/statistics" element={<ProtectedRoute><StatisticsPage /></ProtectedRoute>} />
              <Route path="/points" element={<ProtectedRoute><PointsPage /></ProtectedRoute>} />
              <Route path="/favorites" element={<ProtectedRoute><FavoriteDishesPage /></ProtectedRoute>} />
              <Route path="/notifications" element={<ProtectedRoute><UserNotificationsPage /></ProtectedRoute>} />

              {/* Bookings */}
              <Route path="/bookings" element={<ProtectedRoute><BookingsPage /></ProtectedRoute>} />
              <Route path="/bookings/form" element={<ProtectedRoute><BookingFormPage /></ProtectedRoute>} />
              <Route path="/bookings/list" element={<ProtectedRoute><BookingListPage /></ProtectedRoute>} />

              {/* Orders */}
              <Route path="/orders" element={<ProtectedRoute><OrdersPage /></ProtectedRoute>} />
              <Route path="/cart" element={<ProtectedRoute><CartPage /></ProtectedRoute>} />
              <Route path="/checkout" element={<ProtectedRoute><CheckoutPage /></ProtectedRoute>} />
              <Route path="/order-confirmation/:billId" element={<ProtectedRoute><OrderConfirmationPage /></ProtectedRoute>} />
              <Route path="/order-tracking/:billId" element={<ProtectedRoute><OrderTrackingPage /></ProtectedRoute>} />
              <Route path="/deliveries" element={<ProtectedRoute><DeliveriesPage /></ProtectedRoute>} />

              {/* Admin Routes */}
              <Route path="/admin/dashboard" element={<ProtectedRoute><AdminDashboard /></ProtectedRoute>} />
              <Route path="/admin/users" element={<ProtectedRoute><AdminUsersPage /></ProtectedRoute>} />
              <Route path="/admin/stock" element={<ProtectedRoute><AdminStockPage /></ProtectedRoute>} />
              <Route path="/admin/deliveries" element={<ProtectedRoute><AdminDeliveriesPage /></ProtectedRoute>} />
              <Route path="/admin/sales" element={<ProtectedRoute><SalesReportPage /></ProtectedRoute>} />
              <Route path="/admin/analytics" element={<ProtectedRoute><AnalyticsDashboardPage /></ProtectedRoute>} />
              <Route path="/admin/settings" element={<ProtectedRoute><AdminSettingsPage /></ProtectedRoute>} />

              {/* Fallback */}
              <Route path="*" element={<Navigate to="/" />} />
            </Routes>
          </Layout>
        </CartProvider>
      </AuthProvider>
    </BrowserRouter>
  );
}

// Mount App to #app
const root = createRoot(document.getElementById('app'));
root.render(<App />);
