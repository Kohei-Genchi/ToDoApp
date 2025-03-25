<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TodoApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\TaskShareController;
use App\Http\Controllers\Api\MemoApiController;
use App\Http\Controllers\Api\GlobalShareController;
use App\Http\Controllers\StripSubscriptionController;

/**
 * User information API
 */
Route::middleware("auth:sanctum")->get("/user", function (Request $request) {
    return $request->user();
});

// Route::middleware("auth:web")->group(function () {
//     // 'auth:sanctum' を 'auth:web' に変更
//     Route::post("/speech-to-tasks", [
//         App\Http\Controllers\Api\SpeechToTextController::class,
//         "processSpeech",
//     ]);
// });
//
// // routes/api.php - 認証要件を一時的に外す
// routes/api.php
Route::post("/speech-to-tasks", [
    App\Http\Controllers\Api\SpeechToTextController::class,
    "processSpeech",
]);
// 認証ミドルウェアを外す
// auth:sanctum や auth:web のミドルウェアを一時的に外します
/**
 * Stripe Webhook
 */
Route::post("/stripe/subscription/webhook", [
    StripSubscriptionController::class,
    "webhook",
]);

/**
 * Todo API routes
 */
Route::prefix("todos")
    ->middleware(["web"])
    ->group(function () {
        // Get tasks
        Route::get("/", [TodoApiController::class, "index"]);
        // Create task
        Route::post("/", [TodoApiController::class, "store"]);
        // Individual task operations
        Route::get("/{todo}", [TodoApiController::class, "show"]);
        // Update task
        Route::match(["put", "post"], "/{todo}", [
            TodoApiController::class,
            "update",
        ]);
        // Toggle task status
        Route::patch("/{todo}/toggle", [TodoApiController::class, "toggle"]);
        // Delete task
        Route::delete("/{todo}", [TodoApiController::class, "destroy"]);
    });

/**
 * Task sharing API routes - Fixed to match the client's expected URLs
 */
Route::middleware(["web"])->group(function () {
    // Get users with whom a task is shared
    Route::get("/tasks/{todo}/shares", [TaskShareController::class, "index"]);
    // Share a task with a user
    Route::post("/tasks/{todo}/shares", [TaskShareController::class, "store"]);
    // Update sharing permission for a user
    Route::put("/tasks/{todo}/shares/{user}", [
        TaskShareController::class,
        "update",
    ]);
    // Stop sharing a task with a user
    Route::delete("/tasks/{todo}/shares/{user}", [
        TaskShareController::class,
        "destroy",
    ]);
    // Get tasks shared with me
    Route::get("/shared-with-me", [TaskShareController::class, "sharedWithMe"]);
});

/**
 * Category API routes
 */
Route::prefix("categories")
    ->middleware(["web"])
    ->group(function () {
        // Get categories
        Route::get("/", [CategoryApiController::class, "index"]);
        // Create category
        Route::post("/", [CategoryApiController::class, "store"]);
        // Update category
        Route::put("/{category}", [CategoryApiController::class, "update"]);
        // Delete category
        Route::delete("/{category}", [CategoryApiController::class, "destroy"]);
    });

Route::middleware(["web"])->group(function () {
    // Get users with whom the authenticated user has globally shared tasks
    Route::get("/global-shares", [GlobalShareController::class, "index"]);

    // Share all tasks globally with a user
    Route::post("/global-shares", [GlobalShareController::class, "store"]);

    // Update global sharing permission for a user
    Route::put("/global-shares/{globalShare}", [
        GlobalShareController::class,
        "update",
    ]);

    // Stop sharing globally with a user
    Route::delete("/global-shares/{globalShare}", [
        GlobalShareController::class,
        "destroy",
    ]);

    // Get all tasks shared with the authenticated user via global sharing
    Route::get("/global-shared-with-me", [
        GlobalShareController::class,
        "sharedWithMe",
    ]);
});

/**
 * Memo API routes
 */
Route::prefix("memos")
    ->middleware(["web"])
    ->group(function () {
        // Get all memos
        Route::get("/", [MemoApiController::class, "index"]);
        // Create a new memo
        Route::post("/", [MemoApiController::class, "store"]);
    });
