<?php

use App\Http\Controllers\Auth\GuestLoginController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\CategoryShareController;
use App\Models\Todo;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\StripSubscriptionController;

/**
 * Load authentication routes
 */
require __DIR__ . "/auth.php";

/**
 * Homepage redirect
 */
Route::get("/", function () {
    return redirect()->route("todos.index");
})->name("home");

//Google login
Route::get("/auth/google", [GoogleController::class, "redirectToGoogle"]);
Route::get("/auth/google/callback", [
    GoogleController::class,
    "handleGoogleCallback",
]);
Route::middleware(["web"])->group(function () {
    // Approval and rejection with friendly UI
    Route::get("/share-requests/{token}/approve", [
        App\Http\Controllers\ShareNotificationWebController::class,
        "approveRequest",
    ])->name("share-requests.web.approve");

    Route::get("/share-requests/{token}/reject", [
        App\Http\Controllers\ShareNotificationWebController::class,
        "rejectRequest",
    ])->name("share-requests.web.reject");

    // Dashboard for share requests (requires authentication)
    Route::middleware(["auth"])->group(function () {
        Route::get("/share-requests", [
            App\Http\Controllers\ShareNotificationWebController::class,
            "index",
        ])->name("share-requests.index");
    });
});

# Subscription page (before checkout)
Route::get("stripe/subscription", [StripSubscriptionController::class, "index"])
    ->name("stripe.subscription")
    ->middleware("auth");

# Checkout page
Route::get("stripe/subscription/checkout", [
    StripSubscriptionController::class,
    "checkout",
])
    ->name("stripe.subscription.checkout")
    ->middleware("auth");

# Payment complete - クエリパラメータを保持するために明示的なバインディング
Route::get("stripe/subscription/comp", [
    StripSubscriptionController::class,
    "comp",
])
    ->name("stripe.subscription.comp")
    ->middleware("auth");

# Customer portal
Route::get("stripe/subscription/customer_portal", [
    StripSubscriptionController::class,
    "customer_portal",
])
    ->name("stripe.subscription.customer_portal")
    ->middleware("auth");

# Webhook - CSRFトークン検証を除外するために'web'ミドルウェアを使用せず
Route::post("api/stripe/subscription/webhook", [
    StripSubscriptionController::class,
    "webhook",
]);
/**
 * Guest login
 */
Route::get("/guest-login", [GuestLoginController::class, "login"])
    ->middleware("guest")
    ->name("guest.login");

// Add this to the authenticated routes group in routes/web.php
Route::get("/categories/shared", [
    CategoryShareController::class,
    "sharedWithMePage",
])->name("categories.shared");
/**
 * Category API
 */
Route::get("/api/web-categories", [CategoryApiController::class, "index"]);

/**
 * Todo app main page
 */
Route::get("/todos", [TodoController::class, "index"])->name("todos.index");

/**
 * Dashboard redirect
 */
Route::get("/dashboard", function () {
    return redirect()->route("todos.index", ["view" => "today"]);
})
    ->middleware(["auth"])
    ->name("dashboard");

/**
 * Routes requiring authentication
 */
Route::middleware(["auth"])->group(function () {
    /**
     * Todo Web routes
     */
    Route::post("/todos", [TodoController::class, "store"])->name(
        "todos.store"
    );
    Route::patch("/todos/{todo}/toggle", [
        TodoController::class,
        "toggle",
    ])->name("todos.toggle");
    Route::delete("/todos/{todo}", [TodoController::class, "destroy"])->name(
        "todos.destroy"
    );

    Route::get("/tasks/kanban", [
        App\Http\Controllers\SharedTasksController::class,
        "index",
    ])->name("tasks.kanban");
    Route::get("/tasks/team", [
        App\Http\Controllers\SharedTasksController::class,
        "teamMembers",
    ])->name("tasks.team");
    Route::get("/tasks/analytics", [
        App\Http\Controllers\SharedTasksController::class,
        "analytics",
    ])->name("tasks.analytics");

    /**
     * Category routes
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
     * Profile routes
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
     * Task sharing web routes - Redirect to category sharing
     */
    Route::prefix("todos/shared")->group(function () {
        Route::get("/", [CategoryShareController::class, "sharedWithMe"])->name(
            "todos.shared"
        );
    });

    /**
     * Memo list partial view API
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
     * Category API
     */
    Route::post("/api/categories", [
        CategoryApiController::class,
        "store",
    ])->name("api.categories.store");
});
