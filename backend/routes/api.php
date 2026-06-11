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
    } catch (\Throwable $e) {
        return response()->json([
            'connection' => 'Failed',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/test-create', function () {
    try {
        $user = \App\Models\User::first();
        if (!$user) {
            return response()->json(['error' => 'No user found. Please register a user first.'], 400);
        }
        
        $todo = \App\Models\Todo::create([
            'user_id' => $user->id,
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'todo'
        ]);
        
        return response()->json([
            'status' => 'Success',
            'todo' => $todo
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'Failed',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
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
