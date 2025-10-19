<?php

use Illuminate\Support\Facades\Route;
use Modules\Student\Http\Controllers\StudentController;

Route::post('/student', [StudentController::class, 'store']);

Route::middleware(['auth:sanctum','role:Admin'])->group(function () {
    Route::get('/students', [StudentController::class, 'index']);
});