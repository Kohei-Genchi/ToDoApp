<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemoApiController extends Controller
{
    /**
     * Get all memos (todos without due date) for the authenticated user.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([]);
        }

        try {
            $memos = Auth::user()
                ->todos()
                ->with("category")
                ->whereNull("due_date")
                ->where("status", "pending")
                ->orderBy("created_at", "desc")
                ->get();

            return response()->json($memos);
        } catch (\Exception $e) {
            return response()->json(
                ["error" => "Error retrieving memos: " . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Quick create a new memo.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(
                ["error" => "Authentication required"],
                401
            );
        }

        // Validate input
        $request->validate([
            "title" => "required|string|max:255",
        ]);

        try {
            // Create a new memo task
            $memo = Todo::create([
                "title" => $request->title,
                "user_id" => Auth::id(),
                "status" => "pending",
                "location" => "INBOX",
            ]);

            return response()->json(
                [
                    "success" => true,
                    "message" => "Memo created successfully",
                    "memo" => $memo->load("category"), // Changed from 'todo' to 'memo'
                ],
                201
            );
        } catch (\Exception $e) {
            return response()->json(
                ["error" => "Error creating memo: " . $e->getMessage()],
                500
            );
        }
    }
}
