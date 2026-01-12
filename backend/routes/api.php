<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\JourneyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TaskController;

Route::middleware('auth:sanctum')->group(function () {
    // Rotas de usuários
    Route::get('/user', function (Request $request) {
        return $request->user(); // retorna os dados do usuário autenticado
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::get('/users/{userId}/journeys', [UserController::class, 'journeys']);

    // Rotas de jornadas
    Route::get('/journeys/public', [JourneyController::class, 'public']);
    Route::post('/journeys', [JourneyController::class, 'store']);
    Route::post('/journeys/join', [JourneyController::class, 'join']);
    Route::get('/journeys', [JourneyController::class, 'index']);
    Route::get('/journeys/{id}', [JourneyController::class, 'show']);
    Route::get('/journeys/{id}/users', [JourneyController::class, 'users']);
    Route::delete('/journeys/{id}', [JourneyController::class, 'destroy']);
    Route::get('/journeys/{id}/ranking', [JourneyController::class, 'ranking']);

    // Rotas de tarefas
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::post('/tasks/{taskId}/assign', [TaskController::class, 'assignTaskToUser']);
    Route::post('/tasks/evaluation', [TaskController::class, 'requestEvaluation']);
    Route::post('/tasks/{taskId}/evaluate', [TaskController::class, 'evaluateTask']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);

    // Rotas da loja  
    Route::get('/journeys/{id}/store', [StoreController::class, 'show']);
    Route::get('/journeys/{id}/store/items', [StoreController::class, 'items']);
    Route::post('/journeys/{id}/store/items', [StoreController::class, 'storeItem']);

});
// Rotas de usuários desprotegidas
Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);