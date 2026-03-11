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

Route::get('/menu', [DishController::class , 'index']);
Route::get('/menu/{id}', [DishController::class , 'show']);

Route::get('/gioi-thieu', function () {
    return view('about');
});
Route::get('/gio-hang', function () {
    return view('cart');
});

Route::post('/add-to-cart', [CartController::class , 'addToCart']);
Route::post('/update-cart', [CartController::class , 'updateCart']);
Route::post('/update-cart-quantities', [CartController::class , 'updateCartQuantities']);
Route::post('/save-address', [CartController::class , 'saveAddress']);
Route::post('/save-booking', [CartController::class , 'saveBooking']);
Route::post('/check-multi-overlap', [CartController::class , 'checkMultiOverlap']);

Route::post('/process-payment', [CartController::class , 'processPayment'])->name('process_payment');

Route::get('/transaction-history', [CartController::class , 'transactionHistory'])->name('transaction_history');

Route::get('/clear', function () {
    session()->flush();
    return "Đã dọn dẹp toàn bộ dữ liệu! Hãy quay lại Menu.";
});

Route::get('/export-pdf', [CartController::class , 'exportPDF']);

Route::post('/checkout', [CartController::class , 'checkout']);

Route::get('/login-register', function () {
    return view('login_register');
})->name('login_register');

Route::post('/api/register', [AuthController::class , 'storeRegister'])->name('register.submit');
Route::post('/api/login', [AuthController::class , 'login'])->name('login.submit');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [AuthController::class , 'profile'])->name('profile');
    Route::post('/api/profile/update', [AuthController::class , 'updateProfile'])->name('profile.update');
    Route::post('/logout', [AuthController::class , 'logout'])->name('logout');
});