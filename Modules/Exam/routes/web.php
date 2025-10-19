<?php

use Illuminate\Support\Facades\Route;
use Modules\Exam\Http\Controllers\ExamController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('exams', ExamController::class)->names('exam');
});
