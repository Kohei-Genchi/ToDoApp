<?php

use App\Http\Controllers\Auth\GuestLoginController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Models\Todo;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\StripSubscriptionController;

/**
 * 認証ルートの読み込み
 */
require __DIR__ . "/auth.php";

/**
 * ホームページリダイレクト
 */
Route::get("/", function () {
    return redirect()->route("todos.index");
})->name("home");

//Google ログイン
Route::get("/auth/google", [GoogleController::class, "redirectToGoogle"]);
Route::get("/auth/google/callback", [
    GoogleController::class,
    "handleGoogleCallback",
]);

# サブスク申請ページ(チェックアウトに進む前のページ)
Route::get("stripe/subscription", [
    StripSubscriptionController::class,
    "index",
])->name("stripe.subscription");

# チェックアウトページ
Route::get("stripe/subscription/checkout", [
    StripSubscriptionController::class,
    "checkout",
])->name("stripe.subscription.checkout");

# 支払い完了
Route::get("stripe/subscription/comp", [
    StripSubscriptionController::class,
    "comp",
])->name("stripe.subscription.comp");

# カスタマーポータル
Route::get("stripe/subscription/customer_portal", [
    StripSubscriptionController::class,
    "customer_portal",
])->name("stripe.subscription.customer_portal");

/**
 * ゲストログイン機能
 */
Route::get("/guest-login", [GuestLoginController::class, "login"])
    ->middleware("guest")
    ->name("guest.login");

/**
 * カテゴリーAPI
 */
Route::get("/api/web-categories", [CategoryApiController::class, "index"]);

/**
 * Todoアプリメインページ
 */
Route::get("/todos", [TodoController::class, "index"])->name("todos.index");

/**
 * ダッシュボードリダイレクト
 */
Route::get("/dashboard", function () {
    return redirect()->route("todos.index", ["view" => "today"]);
})
    ->middleware(["auth"])
    ->name("dashboard");

/**
 * 認証が必要なルートグループ
 */
Route::middleware(["auth"])->group(function () {
    /**
     * Todoルート
     */
    Route::post("/todos", [TodoController::class, "store"])->name(
        "todos.store"
    );
    Route::put("/todos/{todo}", [TodoController::class, "update"])->name(
        "todos.update"
    );
    Route::patch("/todos/{todo}/toggle", [
        TodoController::class,
        "toggle",
    ])->name("todos.toggle");
    Route::delete("/todos/{todo}", [TodoController::class, "destroy"])->name(
        "todos.destroy"
    );

    /**
     * カテゴリールート
     */
    Route::get("/categories", [CategoryController::class, "index"])->name(
        "categories.index"
    );
    Route::post("/categories", [CategoryController::class, "store"])->name(
        "categories.store"
    );
    Route::put("/categories/{category}", [
        CategoryController::class,
        "update",
    ])->name("categories.update");
    Route::delete("/categories/{category}", [
        CategoryController::class,
        "destroy",
    ])->name("categories.destroy");

    /**
     * プロフィールルート
     */
    Route::get("/profile", [ProfileController::class, "edit"])->name(
        "profile.edit"
    );
    Route::patch("/profile", [ProfileController::class, "update"])->name(
        "profile.update"
    );
    Route::delete("/profile", [ProfileController::class, "destroy"])->name(
        "profile.destroy"
    );

    /**
     * メモリスト部分ビュー取得API
     */
    Route::get("/api/memos-partial", function () {
        $memos = Auth::user()
            ->todos()
            ->with("category")
            ->whereNull("due_date")
            ->where("status", "pending")
            ->orderBy("created_at", "desc")
            ->get();

        return view("layouts.partials.memo-list", compact("memos"));
    });

    /**
     * カテゴリーAPI
     */
    Route::post("/api/categories", [
        CategoryApiController::class,
        "store",
    ])->name("api.categories.store");
});
