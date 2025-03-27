<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TodoApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\MemoApiController;
use App\Http\Controllers\Api\CategoryShareController;
use App\Http\Controllers\StripSubscriptionController;

/**
 * User information API
 */
Route::middleware("auth:sanctum")->get("/user", function (Request $request) {
    return $request->user();
});

Route::middleware(["auth:sanctum"])->group(function () {
    // Get pending share requests
    Route::get("/share-requests", [
        App\Http\Controllers\Api\ShareRequestsController::class,
        "index",
    ]);

    /**
     * カテゴリー共有 API ルート
     */
    Route::middleware(["web"])->group(function () {
        // カテゴリー共有ユーザー一覧の取得
        Route::get("/categories/{category}/shares", [
            CategoryShareController::class,
            "index",
        ]);

        // カテゴリーを特定のユーザーと共有 (LINE認証必須)
        Route::post("/categories/{category}/shares", [
            CategoryShareController::class,
            "store",
        ]);

        // 共有権限の更新
        Route::put("/categories/{category}/shares/{user}", [
            CategoryShareController::class,
            "update",
        ]);

        // 共有の解除
        Route::delete("/categories/{category}/shares/{user}", [
            CategoryShareController::class,
            "destroy",
        ]);

        // 自分と共有されているカテゴリー一覧の取得
        Route::get("/shared-categories", [
            CategoryShareController::class,
            "sharedWithMe",
        ]);

        // 共有カテゴリーからのタスク一覧の取得
        Route::get("/shared-categories/tasks", [
            CategoryShareController::class,
            "tasksFromSharedCategories",
        ]);
    });

    // LINEで承認/拒否するためのWebルート（署名付き）
    Route::get("/category-share/{token}/approve", [
        CategoryShareController::class,
        "approveShareRequest",
    ])
        ->name("category-share.approve")
        ->middleware(["signed"]);

    Route::get("/category-share/{token}/reject", [
        CategoryShareController::class,
        "rejectShareRequest",
    ])
        ->name("category-share.reject")
        ->middleware(["signed"]);

    // Cancel a share request
    Route::delete("/share-requests/{shareRequest}", [
        App\Http\Controllers\Api\ShareRequestsController::class,
        "cancel",
    ]);

    // Speech to text API - 認証を追加
    Route::post("/speech-to-tasks", [
        App\Http\Controllers\Api\SpeechToTextController::class,
        "processSpeech",
    ]);
});

// Public routes for handling approvals (these will be secured by URL signing)
Route::get("/share-requests/{token}/approve", [
    App\Http\Controllers\Api\ShareRequestsController::class,
    "approve",
])
    ->name("share-requests.approve")
    ->middleware(["signed"]);

Route::get("/share-requests/{token}/reject", [
    App\Http\Controllers\Api\ShareRequestsController::class,
    "reject",
])
    ->name("share-requests.reject")
    ->middleware(["signed"]);

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
