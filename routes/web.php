<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\GuestLoginController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\CategoryShareController;
use App\Http\Controllers\ShareNotificationWebController;
use App\Http\Controllers\SharedTasksController;
use App\Http\Controllers\StripSubscriptionController;
use App\Http\Controllers\DirectShareApprovalController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Routes for web pages with HTML responses
|
*/

/**
 * Load authentication routes from auth.php
 */
require __DIR__ . "/auth.php";

/**
 * Public Routes (No Authentication Required)
 */
Route::group([], function () {
    // Homepage
    Route::get("/", function () {
        return redirect()->route("todos.index");
    })->name("home");

    // Google Authentication
    Route::prefix("auth/google")->group(function () {
        Route::get("/", [GoogleController::class, "redirectToGoogle"]);
        Route::get("/callback", [
            GoogleController::class,
            "handleGoogleCallback",
        ]);
    });

    // Guest Login
    Route::get("/guest-login", [GuestLoginController::class, "login"])
        ->middleware("guest")
        ->name("guest.login");
});

/**
 * Stripe Subscription Routes
 */
Route::prefix("stripe/subscription")
    ->middleware("auth")
    ->group(function () {
        // Subscription page
        Route::get("/", [StripSubscriptionController::class, "index"])->name(
            "stripe.subscription"
        );

        // Checkout page
        Route::get("/checkout", [
            StripSubscriptionController::class,
            "checkout",
        ])->name("stripe.subscription.checkout");

        // Payment complete
        Route::get("/comp", [StripSubscriptionController::class, "comp"])->name(
            "stripe.subscription.comp"
        );

        // Customer portal
        Route::get("/customer_portal", [
            StripSubscriptionController::class,
            "customer_portal",
        ])->name("stripe.subscription.customer_portal");
    });

/**
 * Authenticated Routes
 */
Route::middleware(["auth"])->group(function () {
    // Dashboard redirect
    Route::get("/dashboard", function () {
        return redirect()->route("todos.index", ["view" => "today"]);
    })->name("dashboard");

    /**
     * Todo Management Web UI
     */
    Route::prefix("todos")
        ->middleware(["web"])
        ->group(function () {
            // Todo list main page
            Route::get("/", [TodoController::class, "index"])->name(
                "todos.index"
            );

            // Create todo (web form)
            Route::post("/", [TodoController::class, "store"])->name(
                "todos.store"
            );

            // Toggle todo status
            Route::patch("/{todo}/toggle", [
                TodoController::class,
                "toggle",
            ])->name("todos.toggle");

            // Delete todo
            Route::delete("/{todo}", [TodoController::class, "destroy"])->name(
                "todos.destroy"
            );
        });

    /**
     * Category Management Web UI
     */
    Route::prefix("categories")->group(function () {
        // Categories list page
        Route::get("/", [CategoryController::class, "index"])->name(
            "categories.index"
        );

        // Create category (web form)
        Route::post("/", [CategoryController::class, "store"])->name(
            "categories.store"
        );

        // Update category (web form)
        Route::put("/{category}", [CategoryController::class, "update"])->name(
            "categories.update"
        );

        // Delete category
        Route::delete("/{category}", [
            CategoryController::class,
            "destroy",
        ])->name("categories.destroy");

        // Shared categories page
        Route::get("/shared", [
            CategoryShareController::class,
            "sharedWithMePage",
        ])->name("categories.shared");
    });

    /**
     * Team Collaboration Features
     */
    Route::prefix("tasks")->group(function () {
        // Kanban board
        Route::get("/kanban", [SharedTasksController::class, "index"])->name(
            "tasks.kanban"
        );

        // Team members
        Route::get("/team", [
            SharedTasksController::class,
            "teamMembers",
        ])->name("tasks.team");

        // Analytics
        Route::get("/analytics", [
            SharedTasksController::class,
            "analytics",
        ])->name("tasks.analytics");
    });

    Route::post("/tasks/status/{todo}", [
        App\Http\Controllers\Api\TodoStatusController::class,
        "updateStatus",
    ])
        ->middleware(["auth"])
        ->name("tasks.status.update");

    Route::get("/share/approve/{token}", [
        DirectShareApprovalController::class,
        "processApproval",
    ])->name("share.direct.approve");

    /**
     * Share Requests Web UI
     */
    Route::get("/share-requests", [
        ShareNotificationWebController::class,
        "index",
    ])->name("share-requests.index");

    Route::get("/share-requests-direct/{token}/approve", [
        App\Http\Controllers\ShareNotificationWebController::class,
        "approveRequest",
    ])->name("share-requests.direct.approve");
    // Find these routes
    Route::get("/share-requests/{token}/approve", [
        App\Http\Controllers\ShareNotificationWebController::class,
        "approveRequest",
    ])
        ->name("share-requests.web.approve")
        ->middleware("signed");

    Route::get("/share-requests/{token}/reject", [
        App\Http\Controllers\ShareNotificationWebController::class,
        "rejectRequest",
    ])
        ->name("share-requests.web.reject")
        ->middleware("signed");

    /**
     * Profile Management
     */
    Route::prefix("profile")->group(function () {
        // Edit profile
        Route::get("/", [ProfileController::class, "edit"])->name(
            "profile.edit"
        );

        // Update profile
        Route::patch("/", [ProfileController::class, "update"])->name(
            "profile.update"
        );

        // Delete profile
        Route::delete("/", [ProfileController::class, "destroy"])->name(
            "profile.destroy"
        );
    });

    /**
     * Web Components API - Fragments for loading via AJAX
     */
    Route::prefix("api")->group(function () {
        // Categories for web components
        Route::get("/web-categories", [
            App\Http\Controllers\Api\CategoryApiController::class,
            "index",
        ]);

        // Memo list partial view
        Route::get("/memos-partial", function () {
            $memos = Auth::user()
                ->todos()
                ->with("category")
                ->whereNull("due_date")
                ->where("status", "pending")
                ->orderBy("created_at", "desc")
                ->get();

            return view("layouts.partials.memo-list", compact("memos"));
        });
    });
});
