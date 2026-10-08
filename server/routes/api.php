<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskListController;
use App\Http\Controllers\CardController;

Route::get('/hello', function () {
    return response()->json([
        'message' => 'Hello from BSIT 4C! \n Members:\n1. Bacaylan, Justine\n2. Catubay, Kaith Nathaniel\n3. Limpangog, Clyde Lawrence\n4. Palomares, Yummy Fran',
    ]);
});

Route::post("signup", [AuthController::class, "signup"]);
Route::post("login", [AuthController::class, "signin"]);

// Protected routes with sanctum auth
Route::middleware('auth:sanctum')->group(function () {
    Route::get("user", [AuthController::class, "user"]);
    Route::post("logout", [AuthController::class, "logout"]);

    Route::get("project", [ProjectController::class, "index"]);
    Route::post("project", [ProjectController::class, "store"]);
    Route::get("project/{id}", [ProjectController::class, "show"]);
    Route::put('/project/{id}', [ProjectController::class, 'update']);
    Route::delete('/project/{id}', [ProjectController::class, 'destroy']);
    Route::post('/project/{id}/member', [ProjectController::class, 'addMember']);
    Route::delete('/project/{id}/member/{userId}', [ProjectController::class, 'removeMember']);

    Route::get("/project/{projectId}/list", [TaskListController::class, "index"]);
    Route::post("/project/{projectId}/list", [TaskListController::class, "store"]);
    Route::put('/project/{projectId}/list/{listId}', [TaskListController::class, 'update']);
    Route::delete('/project/{projectId}/list/{listId}', [TaskListController::class, 'destroy']);

    Route::get('/list/{listId}/card', [CardController::class, 'index']);
    Route::post('/list/{listId}/card', [CardController::class, 'store']);

    Route::put('/cards/{cardId}/move', [CardController::class, 'moveCard']);
    Route::put('/cards/{cardId}', [CardController::class, 'update']);
    Route::delete('/cards/{cardId}', [CardController::class, 'destroy']);

});