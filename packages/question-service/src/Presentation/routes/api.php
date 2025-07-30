<?php

use Illuminate\Support\Facades\Route;
use KidsQaAi\QuestionService\Presentation\Controllers\QuestionController;

/*
|--------------------------------------------------------------------------
| Question Service API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Question Service package.
| All routes are prefixed with 'api' and require authentication.
|
*/

Route::prefix('api')->middleware(['supabase.auth'])->group(function () {
    // Question routes
    Route::prefix('questions')->group(function () {
        // Submit a new question
        Route::post('/', [QuestionController::class, 'store']);

        // Get questions for authenticated user
        Route::get('/', [QuestionController::class, 'index']);

        // Get specific question
        Route::get('/{questionId}', [QuestionController::class, 'show']);
    });
});
