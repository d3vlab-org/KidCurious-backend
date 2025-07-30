<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'KidCurious API'
    ]);
});

// WebSocket long-polling fallback endpoint
Route::middleware(['auth:supabase'])->group(function () {
    Route::get('/poll/answers/{userId}', function (Request $request, string $userId) {
        // Long-polling implementation will be added here
        return response()->json([
            'message' => 'Long-polling endpoint - implementation pending',
            'user_id' => $userId
        ]);
    });
});
