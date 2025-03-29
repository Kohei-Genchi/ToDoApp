<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ShareNotificationService;
use Illuminate\View\View;

class CategoryShareController extends Controller
{
    protected $shareNotificationService;
    protected $shareRequestsController;

    public function __construct(
        ShareNotificationService $shareNotificationService,
        ShareRequestsController $shareRequestsController
    ) {
        $this->shareNotificationService = $shareNotificationService;
        $this->shareRequestsController = $shareRequestsController;
    }

    /**
     * Get users with whom a category is shared.
     */
    public function index(Category $category): JsonResponse
    {
        // Check if the authenticated user owns this category
        if ($category->user_id !== Auth::id()) {
            return response()->json(["error" => "Unauthorized"], 403);
        }

        // Get the users with whom this category is shared
        $sharedUsers = $category->sharedWith;

        return response()->json($sharedUsers);
    }

    /**
     * Share a category with a user.
     * This method delegates to ShareRequestsController.
     */
    public function store(Request $request, Category $category): JsonResponse
    {
        try {
            // Delegate to ShareRequestsController
            return $this->shareRequestsController->storeCategoryShare(
                $request,
                $category
            );
        } catch (\Exception $e) {
            Log::error("Error in CategoryShareController->store", [
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "エラーが発生しました: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Update sharing permission for a user.
     */
    public function update(
        Request $request,
        Category $category,
        User $user
    ): JsonResponse {
        // Check if the authenticated user owns this category
        if ($category->user_id !== Auth::id()) {
            return response()->json(["error" => "Unauthorized"], 403);
        }

        // Validate request
        $request->validate([
            "permission" => "required|in:view,edit",
        ]);

        try {
            // Check if the category is shared with the user
            if (!$category->isSharedWith($user)) {
                return response()->json(
                    ["error" => "Category is not shared with this user"],
                    400
                );
            }

            // Update the permission
            $category->sharedWith()->updateExistingPivot($user->id, [
                "permission" => $request->permission,
            ]);

            return response()->json([
                "success" => true,
                "message" => "Sharing permission updated successfully",
            ]);
        } catch (\Exception $e) {
            Log::error(
                "Error updating sharing permission: " . $e->getMessage()
            );
            return response()->json(
                [
                    "error" =>
                        "Error updating sharing permission: " .
                        $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Stop sharing a category with a user.
     */
    public function destroy(Category $category, User $user): JsonResponse
    {
        // Check if the authenticated user owns this category
        if ($category->user_id !== Auth::id()) {
            return response()->json(["error" => "Unauthorized"], 403);
        }

        try {
            // Unshare the category
            $category->unshareFrom($user);

            return response()->json([
                "success" => true,
                "message" => "Category sharing removed successfully",
            ]);
        } catch (\Exception $e) {
            Log::error("Error removing category sharing: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Error removing category sharing: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Get categories shared with the authenticated user.
     */
    public function sharedWithMe(): JsonResponse
    {
        $sharedCategories = Auth::user()
            ->sharedCategories()
            ->with("user")
            ->get();

        return response()->json($sharedCategories);
    }

    /**
     * Get all tasks from categories shared with the authenticated user.
     */
    public function tasksFromSharedCategories(): JsonResponse
    {
        // Get IDs of categories shared with the current user
        $sharedCategoryIds = Auth::user()
            ->sharedCategories()
            ->pluck("categories.id");

        // Get all tasks from those categories
        $tasks = Todo::whereIn("category_id", $sharedCategoryIds)
            ->with(["category", "user"])
            ->get();

        return response()->json($tasks);
    }

    /**
     * Display the categories shared with the authenticated user (web view)
     */
    public function sharedWithMePage(): View
    {
        return view("categories.shared");
    }
}
