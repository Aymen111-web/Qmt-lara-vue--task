<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TodoController;


    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/api/register', [AuthController::class, 'register']);
    Route::post('/api/login', [AuthController::class, 'login']);


    Route::middleware('auth:sanctum')->group(function () {
     Route::post('/api/register', [AuthController::class, 'register']);
    Route::post('/api/login', [AuthController::class, 'login']);

    Route::get('/todos', [TodoController::class, 'index']);
    Route::post('/todos', [TodoController::class, 'store']);
    Route::put('/todos/{id}', [TodoController::class, 'update']);
    Route::delete('/todos/{id}', [TodoController::class, 'destroy']);
    Route::patch('/todos/{id}/status', [TodoController::class, 'changeStatus']);
});
