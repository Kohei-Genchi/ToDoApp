<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\StripSubscriptionController;

/**
 * ユーザー情報取得API
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
 * Todo API ルート
 */
Route::prefix("todos")
    ->middleware(["web"])
    ->group(function () {
        // タスク一覧取得
        Route::get("/", [TodoController::class, "apiIndex"]);
        // タスク作成
        Route::post("/", [TodoController::class, "store"]);
        // 個別タスク操作
        Route::get("/{todo}", [TodoController::class, "show"]);
        // タスク更新
        Route::match(["put", "post"], "/{todo}", [
            TodoController::class,
            "update",
        ]);
        // タスクステータス操作
        Route::patch("/{todo}/toggle", [TodoController::class, "toggle"]);
        Route::delete("/{todo}", [TodoController::class, "destroy"]);
    });

/**
 * カテゴリー API ルート
 */
Route::prefix("categories")
    ->middleware(["web"])
    ->group(function () {
        // カテゴリー一覧取得
        Route::get("/", [CategoryApiController::class, "index"]);
        // カテゴリー作成
        Route::post("/", [CategoryApiController::class, "store"]);
        // カテゴリー更新
        Route::put("/{category}", [CategoryApiController::class, "update"]);
        // カテゴリー削除
        Route::delete("/{category}", [CategoryApiController::class, "destroy"]);
    });
