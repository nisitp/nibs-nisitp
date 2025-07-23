<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedbackController; // Import FeedbackController

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route for submitting feedback (POST)
Route::post('/feedback', [FeedbackController::class, 'store']);

// Route for getting all feedback submissions (GET)
Route::get('/feedback', [FeedbackController::class, 'index']);

