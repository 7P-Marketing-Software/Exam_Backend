<?php

use Illuminate\Support\Facades\Route;
use Modules\ExamAnswer\Http\Controllers\ExamAnswerController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('examanswers', ExamAnswerController::class)->names('examanswer');
});
