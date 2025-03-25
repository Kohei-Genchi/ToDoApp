<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ShareNotificationService;

class TaskShareController extends Controller
{
    protected $shareNotificationService;

    public function __construct(
        ShareNotificationService $shareNotificationService
    ) {
        $this->shareNotificationService = $shareNotificationService;
    }

    private function checkSubscription(): ?JsonResponse
    {
        if (!Auth::user()->subscription_id) {
            return response()->json(
                [
                    "error" =>
                        "共有機能を利用するにはサブスクリプションが必要です。",
                    "subscription_required" => true,
                ],
                403
            );
        }

        return null;
    }

    /**
     * Get users with whom a task is shared.
     */
    public function index(Todo $todo): JsonResponse
    {
        // Check subscription
        if ($subscriptionCheck = $this->checkSubscription()) {
            return $subscriptionCheck;
        }

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
     * This is now replaced with a request-approval workflow
     */
    public function store(Request $request, Todo $todo): JsonResponse
    {
        if ($subscriptionCheck = $this->checkSubscription()) {
            return $subscriptionCheck;
        }

        // Forward to the ShareRequestController
        $shareRequestController = app(ShareRequestsController::class);
        return $shareRequestController->storeTaskShare($request, $todo);
    }

    /**
     * Update sharing permission for a user.
     */
    public function update(
        Request $request,
        Todo $todo,
        User $user
    ): JsonResponse {
        if ($subscriptionCheck = $this->checkSubscription()) {
            return $subscriptionCheck;
        }
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
        if ($subscriptionCheck = $this->checkSubscription()) {
            return $subscriptionCheck;
        }
        // Check if the authenticated user own
        // s this task
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
