<?php

use Illuminate\Support\Facades\Route;
use Modules\Exam\Http\Controllers\ExamController;

Route::get('/exams', [ExamController::class, 'index']);

Route::middleware(['auth:sanctum','role:Admin'])->prefix('exam')->group(function () {
    Route::post('/', [ExamController::class, 'store']);
    Route::post('/{id}/update', [ExamController::class, 'update']);
    Route::delete('/{id}', [ExamController::class, 'destroy']);
});
