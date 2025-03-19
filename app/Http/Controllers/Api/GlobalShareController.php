<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlobalShare;
use App\Models\User;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GlobalShareController extends Controller
{
    /**
     * Get users with whom the authenticated user has globally shared tasks.
     */
    public function index(): JsonResponse
    {
        try {
            $globalShares = Auth::user()
                ->globallySharedWith()
                ->with("sharedWithUser")
                ->get()
                ->map(function ($share) {
                    return [
                        "id" => $share->id,
                        "user_id" => $share->shared_with_user_id,
                        "name" => $share->sharedWithUser->name,
                        "email" => $share->sharedWithUser->email,
                        "permission" => $share->permission,
                    ];
                });

            return response()->json($globalShares);
        } catch (\Exception $e) {
            Log::error("Error retrieving global shares: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Error retrieving global shares: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Share all tasks globally with a user.
     */
    public function store(Request $request): JsonResponse
    {
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

            // Check if already shared with this user
            $existingShare = GlobalShare::where("user_id", Auth::id())
                ->where("shared_with_user_id", $user->id)
                ->first();

            if ($existingShare) {
                return response()->json(
                    ["error" => "Already shared globally with this user"],
                    400
                );
            }

            // Create the global share
            $globalShare = GlobalShare::create([
                "user_id" => Auth::id(),
                "shared_with_user_id" => $user->id,
                "permission" => $request->permission,
            ]);

            return response()->json([
                "success" => true,
                "message" => "All tasks shared globally with user",
                "user" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "email" => $user->email,
                    "permission" => $request->permission,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Error sharing globally: " . $e->getMessage());
            return response()->json(
                ["error" => "Error sharing globally: " . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update global sharing permission for a user.
     */
    public function update(
        Request $request,
        GlobalShare $globalShare
    ): JsonResponse {
        // Check if the authenticated user owns this global share
        if ($globalShare->user_id !== Auth::id()) {
            return response()->json(["error" => "Unauthorized"], 403);
        }

        // Validate request
        $request->validate([
            "permission" => "required|in:view,edit",
        ]);

        try {
            // Update the permission
            $globalShare->update([
                "permission" => $request->permission,
            ]);

            return response()->json([
                "success" => true,
                "message" => "Global sharing permission updated successfully",
            ]);
        } catch (\Exception $e) {
            Log::error(
                "Error updating global sharing permission: " . $e->getMessage()
            );
            return response()->json(
                [
                    "error" =>
                        "Error updating global sharing permission: " .
                        $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Stop sharing globally with a user.
     */
    public function destroy(GlobalShare $globalShare): JsonResponse
    {
        // Check if the authenticated user owns this global share
        if ($globalShare->user_id !== Auth::id()) {
            return response()->json(["error" => "Unauthorized"], 403);
        }

        try {
            // Delete the global share
            $globalShare->delete();

            return response()->json([
                "success" => true,
                "message" => "Global sharing removed successfully",
            ]);
        } catch (\Exception $e) {
            Log::error("Error removing global sharing: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Error removing global sharing: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Get all tasks shared with the authenticated user via global sharing.
     */
    public function sharedWithMe(): JsonResponse
    {
        try {
            // Get IDs of users who have globally shared with the authenticated user
            $sharerIds = Auth::user()
                ->globallySharedBy()
                ->pluck("user_id")
                ->toArray();

            // Get all tasks from those users
            $sharedTasks = Todo::whereIn("user_id", $sharerIds)
                ->with(["category", "user"])
                ->get()
                ->map(function ($task) {
                    // Find the global share to determine permission
                    $globalShare = Auth::user()
                        ->globallySharedBy()
                        ->where("user_id", $task->user_id)
                        ->first();

                    $task->pivot = [
                        "permission" => $globalShare
                            ? $globalShare->permission
                            : "view",
                    ];

                    return $task;
                });

            return response()->json($sharedTasks);
        } catch (\Exception $e) {
            Log::error(
                "Error retrieving globally shared tasks: " . $e->getMessage()
            );
            return response()->json(
                [
                    "error" =>
                        "Error retrieving globally shared tasks: " .
                        $e->getMessage(),
                ],
                500
            );
        }
    }
}
