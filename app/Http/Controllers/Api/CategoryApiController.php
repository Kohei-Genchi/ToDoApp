<?php

// app/Http/Controllers/Api/CategoryApiController.php
// Fix for category API

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
    public function store(Request $request): JsonResponse
    {
        // For unauthenticated access, return unauthorized error
        if (!Auth::check()) {
            Log::info(
                "Unauthenticated access to category store API - returning unauthorized"
            );
            return response()->json(
                [
                    "success" => false,
                    "message" => "Unauthorized",
                ],
                401
            );
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            "name" => "required|string|max:255",
            "color" => "required|string|max:7",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Validation failed",
                    "errors" => $validator->errors(),
                ],
                422
            );
        }

        try {
            $user = Auth::user();

            // Create the category
            $category = Category::create([
                "name" => $request->name,
                "color" => $request->color,
                "user_id" => $user->id,
            ]);

            Log::info(
                "Created new category ID: " .
                    $category->id .
                    " for user ID: " .
                    $user->id
            );

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
    public function update(Request $request, Category $category): JsonResponse
    {
        // For unauthenticated access, return unauthorized error
        if (!Auth::check()) {
            Log::info(
                "Unauthenticated access to category update API - returning unauthorized"
            );
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

        // Validate the request
        $validator = Validator::make($request->all(), [
            "name" => "required|string|max:255",
            "color" => "required|string|max:7",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Validation failed",
                    "errors" => $validator->errors(),
                ],
                422
            );
        }

        try {
            // Update the category
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
            Log::info(
                "Unauthenticated access to category destroy API - returning unauthorized"
            );
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
