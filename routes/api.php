<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TodoApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\StripSubscriptionController;
use App\Http\Controllers\Api\MemoApiController;

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
        Route::get("/", [TodoApiController::class, "index"]);
        // タスク作成
        Route::post("/", [TodoApiController::class, "store"]);
        // 個別タスク操作
        Route::get("/{todo}", [TodoApiController::class, "show"]);
        // タスク更新
        Route::match(["put", "post"], "/{todo}", [
            TodoApiController::class,
            "update",
        ]);
        // タスクステータス操作
        Route::patch("/{todo}/toggle", [TodoApiController::class, "toggle"]);
        // タスク削除
        Route::delete("/{todo}", [TodoApiController::class, "destroy"]);
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

/**
 * メモ API ルート
 */
Route::prefix("memos")
    ->middleware(["web"])
    ->group(function () {
        // Get all memos
        Route::get("/", [MemoApiController::class, "index"]);
        // Create a new memo
        Route::post("/", [MemoApiController::class, "store"]);
    });