<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DishController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    $highlights = DB::table('dishes')->where('is_bestseller', true)->get();
    return view('home', ['highlights' => $highlights]);
});

Route::get('/gioi-thieu', function () {
    return view('about');
});

Route::get('/menu', [DishController::class , 'index'])->name('menu.index');
Route::get('/menu/{id}', [DishController::class , 'show'])->name('menu.show');

Route::get('/login-register', function () {
    return view('login_register');
})->name('login');

Route::post('/api/register', [AuthController::class , 'storeRegister'])->name('register.submit');
Route::post('/api/login', [AuthController::class , 'login'])->name('login.submit');
Route::get('/forgot-password', [AuthController::class , 'showForgotPassword'])->name('password.request');
Route::post('/api/forgot-password/verify', [AuthController::class , 'verifyUser'])->name('password.verify');
Route::post('/api/forgot-password/reset', [AuthController::class , 'resetPassword'])->name('password.update');


Route::middleware(['auth'])->group(function () {

    Route::get('/profile', [AuthController::class , 'profile'])->name('profile');
    Route::post('/api/profile/update', [AuthController::class , 'updateProfile'])->name('profile.update');
    Route::post('/logout', [AuthController::class , 'logout'])->name('logout');

    Route::get('/gio-hang', [CartController::class , 'index'])->name('cart.index');
    Route::post('/add-to-cart', [CartController::class , 'addToCart'])->name('cart.add');
    Route::post('/update-cart', [CartController::class , 'updateCart']);
    Route::post('/update-cart-quantities', [CartController::class , 'updateCartQuantities']);
    Route::post('/save-address', [CartController::class , 'saveAddress']);
    Route::post('/save-booking', [CartController::class , 'saveBooking']);
    Route::post('/check-multi-overlap', [CartController::class , 'checkMultiOverlap']);
    Route::post('/process-payment', [CartController::class , 'processPayment'])->name('process_payment');
    Route::post('/checkout', [CartController::class , 'checkout']);

    Route::get('/transaction-history', [CartController::class , 'transactionHistory'])->name('transaction_history');
    Route::get('/export-pdf', [CartController::class , 'exportPDF']);
});

Route::get('/clear', function () {
    session()->flush();
    return "Đã dọn dẹp toàn bộ dữ liệu! Hãy quay lại Menu.";
});

Route::get('/auth/google', [AuthController::class , 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class , 'handleGoogleCallback']);