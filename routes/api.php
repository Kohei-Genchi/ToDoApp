<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TodoApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\MemoApiController;
use App\Http\Controllers\Api\CategoryShareController;
use App\Http\Controllers\Api\ShareRequestsController;
use App\Http\Controllers\Api\SlackInteractionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes for API endpoints that return JSON responses
|
*/

/**
 * Public Routes (No Authentication Required)
 */
Route::group([], function () {
    // Share request approval/rejection via signed URLs (security via URL signing)
    Route::get("share-requests/{token}/approve", [
        ShareRequestsController::class,
        "approve",
    ])
        ->name("api.share-requests.approve")
        ->middleware(["signed"]);

    Route::get("share-requests/{token}/reject", [
        ShareRequestsController::class,
        "reject",
    ])
        ->name("api.share-requests.reject")
        ->middleware(["signed"]);
});

/**
 * Authenticated API Routes
 */
Route::middleware(["auth:sanctum"])->group(function () {
    // User information
    Route::get("user", function (Illuminate\Http\Request $request) {
        return $request->user();
    });

    /**
     * Share Requests Management
     */
    Route::prefix("share-requests")->group(function () {
        // List share requests
        Route::get("/", [ShareRequestsController::class, "index"])->name(
            "api.share-requests.index"
        );

        // Cancel a share request
        Route::delete("/{shareRequest}", [
            ShareRequestsController::class,
            "cancel",
        ])->name("api.share-requests.cancel");
    });

    Route::prefix("slack")->group(function () {
        Route::get("/approve/{token}", [
            SlackInteractionController::class,
            "approveShare",
        ])->name("api.slack.approve");

        Route::get("/reject/{token}", [
            SlackInteractionController::class,
            "rejectShare",
        ])->name("api.slack.reject");
    });
    /**
     * Category Sharing
     */
    Route::prefix("categories")
        ->middleware(["web", "auth"])
        ->group(function () {
            // Get all categories
            Route::get("/", [CategoryApiController::class, "index"])->name(
                "api.categories.index"
            );

            // Create category
            Route::post("/", [CategoryApiController::class, "store"])->name(
                "api.categories.store"
            );

            // Update category
            Route::put("/{category}", [
                CategoryApiController::class,
                "update",
            ])->name("api.categories.update");

            // Delete category
            Route::delete("/{category}", [
                CategoryApiController::class,
                "destroy",
            ])->name("api.categories.destroy");

            // Category Sharing
            Route::prefix("/{category}/shares")->group(function () {
                // List users with whom category is shared
                Route::get("/", [
                    CategoryShareController::class,
                    "index",
                ])->name("api.categories.shares.index");

                // Share category with user
                Route::post("/", [
                    CategoryShareController::class,
                    "store",
                ])->name("api.categories.shares.store");

                // Update share permissions
                Route::put("/{user}", [
                    CategoryShareController::class,
                    "update",
                ])->name("api.categories.shares.update");

                // Remove sharing
                Route::delete("/{user}", [
                    CategoryShareController::class,
                    "destroy",
                ])->name("api.categories.shares.destroy");
            });
        });

    /**
     * Shared Content Access
     */
    Route::prefix("shared")->group(function () {
        // Get categories shared with me
        Route::get("categories", [
            CategoryShareController::class,
            "sharedWithMe",
        ])->name("api.shared.categories");

        // Get tasks from shared categories
        Route::get("categories/tasks", [
            CategoryShareController::class,
            "tasksFromSharedCategories",
        ])->name("api.shared.tasks");
    });

    /**
     * Task Management
     */
    Route::prefix("todos")
        ->middleware(["web", "auth"])
        ->group(function () {
            // List tasks
            Route::get("/", [TodoApiController::class, "index"])->name(
                "api.todos.index"
            );

            // Create task
            Route::post("/", [TodoApiController::class, "store"])->name(
                "api.todos.store"
            );

            // Show task
            Route::get("/{todo}", [TodoApiController::class, "show"])->name(
                "api.todos.show"
            );

            // Update task (support both PUT and POST for Laravel form method spoofing)
            Route::match(["put", "post"], "/{todo}", [
                TodoApiController::class,
                "update",
            ])->name("api.todos.update");

            // Toggle task status
            Route::patch("/{todo}/toggle", [
                TodoApiController::class,
                "toggle",
            ])->name("api.todos.toggle");

            // Delete task
            Route::delete("/{todo}", [
                TodoApiController::class,
                "destroy",
            ])->name("api.todos.destroy");
        });

    /**
     * Memo Management
     */
    Route::prefix("memos")
        ->middleware(["web", "auth"]) // Add auth middleware here
        ->group(function () {
            // Get all memos
            Route::get("/", [MemoApiController::class, "index"]);
            // Create a new memo
            Route::post("/", [MemoApiController::class, "store"]);
        });
});
