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

// Route::get("/todos/shared", function () {
//     return view("todos.index", ["view" => "shared"]);
// })
//     ->middleware(["auth"])
//     ->name("todos.shared");

// 統合された共有タスクを表示するためのルートパラメータ追加
Route::get("/todos", [TodoController::class, "index"])
    ->middleware(["auth"])
    ->name("todos.index");

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

    Route::prefix("categories")->group(function () {
        // Keep these routes for API usage
        Route::post("/", [CategoryController::class, "store"])->name(
            "categories.store"
        );
        Route::put("/{category}", [CategoryController::class, "update"])->name(
            "categories.update"
        );
        Route::delete("/{category}", [
            CategoryController::class,
            "destroy",
        ])->name("categories.destroy");

        // Keep shared categories view
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

        Route::get("/team", [
            SharedTasksController::class,
            "teamMembers",
        ])->name("tasks.team");
    });

    Route::post("/tasks/status/{todo}", [
        TodoApiController::class,
        "updateStatus",
    ])->name("tasks.status.update");

    Route::get("/share/approve/{token}", [
        ShareNotificationWebController::class,
        "processApproval",
    ])->name("share.direct.approve");

    Route::prefix("shared-data")->group(function () {
        // Get categories shared with current user
        Route::get("/categories", function () {
            $sharedCategories = Auth::user()
                ->sharedCategories()
                ->with("user")
                ->get();
            return response()->json($sharedCategories);
        })->name("shared-data.categories");

        // Get categories the current user has shared with others
        Route::get("/my-shared-categories", function () {
            $mySharedCategories = Auth::user()
                ->categories()
                ->with("sharedWith")
                ->whereHas("sharedWith")
                ->get();
            return response()->json($mySharedCategories);
        })->name("shared-data.my-shared-categories");

        // Get share requests data
        Route::get("/share-requests", function () {
            // Code to retrieve share requests
            // ...
        })->name("shared-data.share-requests");
    });
    /**
     * Share Requests Web UI
     */
    Route::get("/share-requests", [
        ShareNotificationWebController::class,
        "index",
    ])->name("share-requests.index");

    Route::get("/share-requests-direct/{token}/approve", [
        ShareNotificationWebController::class,
        "approveRequest",
    ])->name("share-requests.direct.approve");

    Route::get("/share-requests/{token}/approve", [
        ShareNotificationWebController::class,
        "approveRequest",
    ])
        ->name("share-requests.web.approve")
        ->middleware("signed");

    Route::get("/share-requests/{token}/reject", [
        ShareNotificationWebController::class,
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
