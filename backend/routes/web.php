<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DishController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AuthController;

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


Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
    Route::post('/update-cart', [CartController::class, 'updateCart']);
    Route::post('/update-cart-quantities', [CartController::class, 'updateCartQuantities']);
    Route::post('/save-address', [CartController::class, 'saveAddress']);
    Route::post('/save-booking', [CartController::class, 'saveBooking']);
    Route::post('/check-multi-overlap', [CartController::class, 'checkMultiOverlap']);
    Route::post('/checkout', [CartController::class, 'checkout']);

    Route::get('/transaction-history', [CartController::class, 'transactionHistory'])->name('transaction_history');
    Route::get('/export-pdf', [CartController::class, 'exportPDF']);

    // Admin routes
    Route::middleware(['admin'])->group(function () {
        Route::get('/admin/menu-management', [DishController::class, 'menuManagement'])->name('admin.menu_management');
        Route::post('/admin/add-dish', [DishController::class, 'addDish'])->name('admin.add_dish');
        Route::post('/admin/edit-dish/{id}', [DishController::class, 'updateDish'])->name('admin.edit_dish');
        Route::post('/admin/delete-dish/{id}', [DishController::class, 'deleteDish'])->name('admin.delete_dish');
        Route::get('/admin/transaction-management', [CartController::class, 'transactionManagement'])->name('admin.transaction_management');
    });
});

Route::get('/clear', function () {
    session()->flush();
    return "Đã dọn dẹp toàn bộ dữ liệu! Hãy quay lại Menu.";
});

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);