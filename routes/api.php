<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TodoApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\TaskShareController;
use App\Http\Controllers\Api\MemoApiController;
use App\Http\Controllers\Api\GlobalShareController;
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
     * カテゴリー単位でのタスク共有を実装し、グローバルタスク共有と個別タスク共有を廃止
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
// 個別タスク共有は廃止され、カテゴリー共有に置き換えられました
Route::middleware(["web"])->group(function () {
    // 廃止されたエンドポイントへのアクセスを処理
    Route::get("/tasks/{todo}/shares", function() {
        return response()->json([
            "error" => "個別タスク共有は廃止されました。代わりにカテゴリー共有を使用してください。",
            "use_category_sharing" => true
        ], 400);
    });

    Route::post("/tasks/{todo}/shares", function() {
        return response()->json([
            "error" => "個別タスク共有は廃止されました。代わりにカテゴリー共有を使用してください。",
            "use_category_sharing" => true
        ], 400);
    });

    Route::put("/tasks/{todo}/shares/{user}", function() {
        return response()->json([
            "error" => "個別タスク共有は廃止されました。代わりにカテゴリー共有を使用してください。",
            "use_category_sharing" => true
        ], 400);
    });

    Route::delete("/tasks/{todo}/shares/{user}", function() {
        return response()->json([
            "error" => "個別タスク共有は廃止されました。代わりにカテゴリー共有を使用してください。",
            "use_category_sharing" => true
        ], 400);
    });

    // 共有タスク一覧は共有カテゴリーのタスク一覧にリダイレクト
    Route::get("/shared-with-me", function() {
        return redirect("/api/shared-categories/tasks");
    });
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

// グローバルタスク共有は廃止され、カテゴリー共有に置き換えられました
Route::middleware(["web"])->group(function () {
    // 廃止されたエンドポイントへのアクセスを処理
    Route::get("/global-shares", function() {
        return response()->json([
            "error" => "グローバルタスク共有は廃止されました。代わりにカテゴリー共有を使用してください。",
            "use_category_sharing" => true
        ], 400);
    });

    Route::post("/global-shares", function() {
        return response()->json([
            "error" => "グローバルタスク共有は廃止されました。代わりにカテゴリー共有を使用してください。",
            "use_category_sharing" => true
        ], 400);
    });

    Route::put("/global-shares/{globalShare}", function() {
        return response()->json([
            "error" => "グローバルタスク共有は廃止されました。代わりにカテゴリー共有を使用してください。",
            "use_category_sharing" => true
        ], 400);
    });

    Route::delete("/global-shares/{globalShare}", function() {
        return response()->json([
            "error" => "グローバルタスク共有は廃止されました。代わりにカテゴリー共有を使用してください。",
            "use_category_sharing" => true
        ], 400);
    });

    // グローバル共有タスク一覧は共有カテゴリーのタスク一覧にリダイレクト
    Route::get("/global-shared-with-me", function() {
        return redirect("/api/shared-categories/tasks");
    });
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
