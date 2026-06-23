<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VnpayController;
use App\Http\Controllers\AuthController;

Route::get('/vnpay/return', [VnpayController::class, 'vnpayReturn']);
Route::match(['get', 'post'], '/vnpay/ipn', [VnpayController::class, 'vnpayIpn']);

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

Route::get('/clear', function () {
    session()->flush();
    return "Đã dọn dẹp toàn bộ dữ liệu session.";
});

Route::get('/{any?}', function () {
    return view('layout');
})->where('any', '^(?!api|vnpay|auth|clear).*$');