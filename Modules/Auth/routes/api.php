<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Artisan;

Route::prefix('auth/')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
});

Route::get('run-seeder',function(){
    Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\AdminSeeder'
     ]);
     return response()->json(['message' => 'Seeder run successfully']);
 });
