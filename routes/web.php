<?php

use App\Http\Controllers\Auth\GuestLoginController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\TaskShareController;
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

# Subscription page (before checkout)
Route::get("stripe/subscription", [
    StripSubscriptionController::class,
    "index",
])->name("stripe.subscription");

# Checkout page
Route::get("stripe/subscription/checkout", [
    StripSubscriptionController::class,
    "checkout",
])->name("stripe.subscription.checkout");

# Payment complete
Route::get("stripe/subscription/comp", [
    StripSubscriptionController::class,
    "comp",
])->name("stripe.subscription.comp");

# Customer portal
Route::get("stripe/subscription/customer_portal", [
    StripSubscriptionController::class,
    "customer_portal",
])->name("stripe.subscription.customer_portal");

/**
 * Guest login
 */
Route::get("/guest-login", [GuestLoginController::class, "login"])
    ->middleware("guest")
    ->name("guest.login");

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
     * Task sharing web routes
     */
    Route::prefix("todos/shared")->group(function () {
        Route::get("/", [TaskShareController::class, "sharedWithMe"])->name(
            "todos.shared"
        );
    });

    /**
     * Routes for task sharing (web interface)
     */
    Route::prefix("tasks")
        ->middleware(["auth"])
        ->group(function () {
            // View tasks shared with me
            Route::get("/shared", [SharedTaskController::class, "index"])->name(
                "tasks.shared"
            );

            // View sharing settings for a task (owner only)
            Route::get("/{todo}/sharing", [
                SharedTaskController::class,
                "showSharing",
            ])->name("tasks.sharing");

            // Share a task with a user (owner only)
            Route::post("/{todo}/sharing", [
                SharedTaskController::class,
                "storeSharing",
            ])->name("tasks.storeSharing");

            // Update sharing permission (owner only)
            Route::put("/{todo}/sharing/{user}", [
                SharedTaskController::class,
                "updateSharing",
            ])->name("tasks.updateSharing");

            // Remove sharing (owner only)
            Route::delete("/{todo}/sharing/{user}", [
                SharedTaskController::class,
                "destroySharing",
            ])->name("tasks.destroySharing");
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
