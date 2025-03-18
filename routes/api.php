<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TodoApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\TaskShareController;
use App\Http\Controllers\Api\MemoApiController;
use App\Http\Controllers\StripSubscriptionController;

/**
 * User information API
 */
Route::middleware("auth:sanctum")->get("/user", function (Request $request) {
    return $request->user();
});

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
