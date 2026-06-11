<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Thêm CORS middleware vào toàn bộ API
        $middleware->api(append: [
            \App\Http\Middleware\CorsMiddleware::class,
            \App\Http\Middleware\RateLimitMiddleware::class,
        ]);

        // Thêm SPA middleware cho web routes
        // ✅ COMMENT OUT: React chạy riêng trên Vite (port 5173), không cần SPA middleware
        // $middleware->web(append: [
        //     \App\Http\Middleware\SpaMiddleware::class,
        // ]);

        // Alias cho middleware
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'cors' => \App\Http\Middleware\CorsMiddleware::class,
            'rate-limit' => \App\Http\Middleware\RateLimitMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
