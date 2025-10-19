<?php

use Illuminate\Support\Facades\Route;
use Modules\ExamAnswer\Http\Controllers\ExamAnswerController;

Route::post('/startExam', [ExamAnswerController::class, 'startExam']);
Route::post('/submitExam', [ExamAnswerController::class, 'submitExam']);
