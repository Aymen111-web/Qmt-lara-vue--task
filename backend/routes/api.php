<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TodoController;

// Public routes
Route::get('/', function () {
    return response()->json(['status' => 'API is running']);
});

Route::get('/db-check', function () {
    try {
        Illuminate\Support\Facades\DB::connection()->getPdo();
        $usersExist = Illuminate\Support\Facades\Schema::hasTable('users');
        $todosExist = Illuminate\Support\Facades\Schema::hasTable('todos');
        
        return response()->json([
            'connection' => 'Successful',
            'database_name' => Illuminate\Support\Facades\DB::connection()->getDatabaseName(),
            'tables' => [
                'users' => $usersExist ? 'Exists' : 'Missing',
                'todos' => $todosExist ? 'Exists' : 'Missing',
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'connection' => 'Failed',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/todos', [TodoController::class, 'index']);
    Route::post('/todos', [TodoController::class, 'store']);
    Route::put('/todos/{id}', [TodoController::class, 'update']);
    Route::delete('/todos/{id}', [TodoController::class, 'destroy']);
    Route::patch('/todos/{id}/status', [TodoController::class, 'changeStatus']);
});
