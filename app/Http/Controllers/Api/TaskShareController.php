<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskShareController extends Controller
{
    /**
     * Get users with whom a task is shared.
     */
    public function index(Todo $todo): JsonResponse
    {
        // Check if the authenticated user owns this task
        if ($todo->user_id !== Auth::id()) {
            return response()->json(["error" => "Unauthorized"], 403);
        }

        // Get the users with whom this task is shared
        $sharedUsers = $todo->sharedWith;

        return response()->json($sharedUsers);
    }

    /**
     * Share a task with a user.
     */
    public function store(Request $request, Todo $todo): JsonResponse
    {
        // Check if the authenticated user owns this task
        if ($todo->user_id !== Auth::id()) {
            return response()->json(["error" => "Unauthorized"], 403);
        }

        // Validate request
        $request->validate([
            "email" => "required|email|exists:users,email",
            "permission" => "required|in:view,edit",
        ]);

        try {
            // Find the user by email
            $user = User::where("email", $request->email)->first();

            // Don't allow sharing with oneself
            if ($user->id === Auth::id()) {
                return response()->json(
                    ["error" => "Cannot share with yourself"],
                    400
                );
            }

            // Check if we've reached the maximum number of shares (5)
            if ($todo->sharedWith->count() >= 5) {
                return response()->json(
                    [
                        "error" =>
                            "Maximum share limit (5) reached for this task",
                    ],
                    400
                );
            }

            // Share the task
            $todo->shareTo($user, $request->permission);

            return response()->json([
                "success" => true,
                "message" => "Task shared successfully",
                "user" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "email" => $user->email,
                    "permission" => $request->permission,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Error sharing task: " . $e->getMessage());
            return response()->json(
                ["error" => "Error sharing task: " . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update sharing permission for a user.
     */
    public function update(
        Request $request,
        Todo $todo,
        User $user
    ): JsonResponse {
        // Check if the authenticated user owns this task
        if ($todo->user_id !== Auth::id()) {
            return response()->json(["error" => "Unauthorized"], 403);
        }

        // Validate request
        $request->validate([
            "permission" => "required|in:view,edit",
        ]);

        try {
            // Check if the task is shared with the user
            if (!$todo->isSharedWith($user)) {
                return response()->json(
                    ["error" => "Task is not shared with this user"],
                    400
                );
            }

            // Update the permission
            $todo->sharedWith()->updateExistingPivot($user->id, [
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
     * Stop sharing a task with a user.
     */
    public function destroy(Todo $todo, User $user): JsonResponse
    {
        // Check if the authenticated user owns this task
        if ($todo->user_id !== Auth::id()) {
            return response()->json(["error" => "Unauthorized"], 403);
        }

        try {
            // Unshare the task
            $todo->unshareFrom($user);

            return response()->json([
                "success" => true,
                "message" => "Task sharing removed successfully",
            ]);
        } catch (\Exception $e) {
            Log::error("Error removing task sharing: " . $e->getMessage());
            return response()->json(
                ["error" => "Error removing task sharing: " . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get tasks shared with the authenticated user.
     */
    public function sharedWithMe(): JsonResponse
    {
        $sharedTasks = Auth::user()
            ->sharedTasks()
            ->with("category", "user")
            ->get();

        return response()->json($sharedTasks);
    }
}
