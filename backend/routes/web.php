<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DishController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\BookingTableController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VnpayController;

use App\Models\Dish;

Route::get('/', function () {
    $highlights = Dish::where('is_bestseller', true)->get();
    return view('home', ['highlights' => $highlights]);
});

Route::get('/gioi-thieu', function () {
    return view('about');
});

Route::get('/menu', [DishController::class, 'index'])->name('menu.index');
Route::get('/menu/{id}', [DishController::class, 'show'])->name('menu.show');

Route::get('/login-register', function () {
    return view('login_register');
})->name('login');

Route::post('/api/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/api/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/api/forgot-password/verify', [AuthController::class, 'verifyUser'])->name('password.verify');
Route::post('/api/forgot-password/reset', [AuthController::class, 'resetPassword'])->name('password.update');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // =============================================
    // DELIVERY ROUTES
    // =============================================
    Route::get('/delivery', [DeliveryController::class, 'deliveryCheckoutPage'])->name('delivery.page');
    Route::post('/save-address', [DeliveryController::class, 'saveAddress']);
    Route::post('/delivery/checkout', [DeliveryController::class, 'processDeliveryCheckout'])->name('delivery.checkout');

    // =============================================
    // BOOKING TABLE ROUTES
    // =============================================
    Route::get('/booking-table', [BookingTableController::class, 'bookingCheckoutPage'])->name('booking.page');
    Route::post('/save-booking', [BookingTableController::class, 'saveBooking']);
    Route::post('/check-multi-overlap', [BookingTableController::class, 'checkMultiOverlap']);
    Route::post('/booking-table/checkout', [BookingTableController::class, 'processBookingCheckout'])->name('booking.checkout');
    Route::post('/booking-table/process-payment', [BookingTableController::class, 'processBookingPayment'])->name('booking_table.process_payment');

    // =============================================
    // ORDER / CART ROUTES (shared)
    // =============================================
    Route::post('/add-to-cart', [OrderController::class, 'addToCart'])->name('cart.add');
    Route::post('/update-cart', [OrderController::class, 'updateCart']);
    Route::post('/update-cart-quantities', [OrderController::class, 'updateCartQuantities']);
    Route::post('/process-payment', [OrderController::class, 'processPayment'])->name('process_payment');
    Route::get('/transaction-history', [OrderController::class, 'transactionHistory'])->name('transaction_history');
    Route::get('/export-pdf', [OrderController::class, 'exportPDF']);
    
    // Redirect legacy cart URLs
    Route::get('/gio-hang', fn () => redirect()->route('delivery.page'));
    Route::get('/dat-hang', fn () => redirect()->route('delivery.page'))->name('cart.order');
    Route::get('/dat-ban-old', fn () => redirect()->route('booking.page'))->name('cart.booking');

    // Admin routes
    Route::middleware(['admin'])->group(function () {
        Route::get('/admin/menu-management', [DishController::class, 'menuManagement'])->name('admin.menu_management');
        Route::post('/admin/add-dish', [DishController::class, 'addDish'])->name('admin.add_dish');
        Route::post('/admin/edit-dish/{id}', [DishController::class, 'updateDish'])->name('admin.edit_dish');
        Route::post('/admin/delete-dish/{id}', [DishController::class, 'deleteDish'])->name('admin.delete_dish');
        Route::get('/admin/transaction-management', [OrderController::class, 'transactionManagement'])->name('admin.transaction_management');
    });
});

Route::get('/vnpay/return', [VnpayController::class, 'vnpayReturn']);

Route::get('/clear', function () {
    session()->flush();
    return "Đã dọn dẹp toàn bộ dữ liệu! Hãy quay lại Menu.";
});

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
