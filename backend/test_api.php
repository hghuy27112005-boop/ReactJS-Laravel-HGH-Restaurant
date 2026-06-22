<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
auth()->login($user);

$request = Illuminate\Http\Request::create('/api/my-bills', 'GET', ['order_type' => 'delivery']);
$response = $kernel->handle($request);
echo $response->getContent();
