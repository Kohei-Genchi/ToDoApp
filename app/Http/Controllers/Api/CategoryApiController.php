<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\CategoryApiRequest;

class CategoryApiController extends Controller
{
    /**
     * Get all categories for the authenticated user.
     */
    public function index(): JsonResponse
    {
        try {
            if (!Auth::check()) {
                return response()->json([]);
            }

            $user = Auth::user();
            $categories = $user->categories()->orderBy("name")->get();

            return response()->json($categories);
        } catch (\Exception $e) {
            Log::error(
                "Error in CategoryApiController->index: " . $e->getMessage()
            );

            if (config("app.debug")) {
                return response()->json(
                    [
                        "error" => $e->getMessage(),
                        "file" => $e->getFile(),
                        "line" => $e->getLine(),
                    ],
                    500
                );
            }
            return response()->json([]);
        }
    }

    /**
     * Store a newly created category via AJAX.
     */
    public function store(CategoryApiRequest $request): JsonResponse
    {
        // For unauthenticated access, return unauthorized error
        if (!Auth::check()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Unauthorized",
                ],
                401
            );
        }

        try {
            $user = Auth::user();

            // Create the category - request is already validated by CategoryApiRequest
            $category = Category::create([
                "name" => $request->name,
                "color" => $request->color,
                "user_id" => $user->id,
            ]);

            return response()->json(
                [
                    "success" => true,
                    "message" => "Category created successfully",
                    "id" => $category->id,
                    "name" => $category->name,
                    "color" => $category->color,
                ],
                201
            );
        } catch (\Exception $e) {
            Log::error(
                "Error in CategoryApiController->store: " . $e->getMessage()
            );
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error creating category",
                    "error" => $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Update an existing category.
     */
    public function update(
        CategoryApiRequest $request,
        Category $category
    ): JsonResponse {
        // For unauthenticated access, return unauthorized error
        if (!Auth::check()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Unauthorized",
                ],
                401
            );
        }

        // Check if the authenticated user owns this category
        if ($category->user_id !== Auth::id()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Unauthorized",
                ],
                403
            );
        }

        try {
            // Update the category - request is already validated by CategoryApiRequest
            $category->update([
                "name" => $request->name,
                "color" => $request->color,
            ]);

            return response()->json([
                "success" => true,
                "message" => "Category updated successfully",
                "category" => $category,
            ]);
        } catch (\Exception $e) {
            Log::error(
                "Error in CategoryApiController->update: " . $e->getMessage()
            );
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error updating category",
                    "error" => $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): JsonResponse
    {
        // For unauthenticated access, return unauthorized error
        if (!Auth::check()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Unauthorized",
                ],
                401
            );
        }

        // Check if the authenticated user owns this category
        if ($category->user_id !== Auth::id()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Unauthorized",
                ],
                403
            );
        }

        try {
            // Delete the category
            $category->delete();

            return response()->json([
                "success" => true,
                "message" => "Category deleted successfully",
            ]);
        } catch (\Exception $e) {
            Log::error(
                "Error in CategoryApiController->destroy: " . $e->getMessage()
            );
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error deleting category",
                    "error" => $e->getMessage(),
                ],
                500
            );
        }
    }
}
