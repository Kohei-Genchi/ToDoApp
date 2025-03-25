<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShareRequest;
use App\Models\Todo;
use App\Models\User;
use App\Services\ShareNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ShareRequestsController extends Controller
{
    protected $notificationService;

    public function __construct(ShareNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all pending share requests for the authenticated user
     */
    public function index(): JsonResponse
    {
        try {
            // Get pending requests made by the authenticated user
            $outgoingRequests = ShareRequest::where("user_id", Auth::id())
                ->where("status", "pending")
                ->where("expires_at", ">", now())
                ->with(["todo"])
                ->get()
                ->map(function ($request) {
                    return [
                        "id" => $request->id,
                        "recipient_email" => $request->recipient_email,
                        "permission" => $request->permission,
                        "share_type" => $request->share_type,
                        "task_title" => $request->todo
                            ? $request->todo->title
                            : "All Tasks",
                        "created_at" => $request->created_at->format(
                            "Y-m-d H:i"
                        ),
                        "expires_at" => $request->expires_at->format(
                            "Y-m-d H:i"
                        ),
                    ];
                });

            // Get pending requests sent to the authenticated user
            $user = Auth::user();
            $incomingRequests = ShareRequest::where(
                "recipient_email",
                $user->email
            )
                ->where("status", "pending")
                ->where("expires_at", ">", now())
                ->with(["todo", "requester"])
                ->get()
                ->map(function ($request) {
                    return [
                        "id" => $request->id,
                        "requester_name" => $request->requester->name,
                        "requester_email" => $request->requester->email,
                        "permission" => $request->permission,
                        "share_type" => $request->share_type,
                        "task_title" => $request->todo
                            ? $request->todo->title
                            : "All Tasks",
                        "created_at" => $request->created_at->format(
                            "Y-m-d H:i"
                        ),
                        "expires_at" => $request->expires_at->format(
                            "Y-m-d H:i"
                        ),
                        "token" => $request->token,
                    ];
                });

            return response()->json([
                "outgoing_requests" => $outgoingRequests,
                "incoming_requests" => $incomingRequests,
            ]);
        } catch (\Exception $e) {
            Log::error("Error retrieving share requests: " . $e->getMessage());
            return response()->json(
                ["error" => "Failed to retrieve share requests"],
                500
            );
        }
    }

    /**
     * Create a new task share request
     */
    public function storeTaskShare(Request $request, Todo $todo): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            "email" => "required|email|exists:users,email",
            "permission" => "required|in:view,edit",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        // Check if the authenticated user owns this task
        if ($todo->user_id !== Auth::id()) {
            return response()->json(["error" => "Unauthorized"], 403);
        }

        $recipientEmail = $request->input("email");
        $permission = $request->input("permission");

        // Don't allow sharing with oneself
        if ($recipientEmail === Auth::user()->email) {
            return response()->json(
                ["error" => "Cannot share with yourself"],
                400
            );
        }

        // Create share request
        try {
            // Check for existing pending request
            $existingRequest = ShareRequest::where("user_id", Auth::id())
                ->where("todo_id", $todo->id)
                ->where("recipient_email", $recipientEmail)
                ->where("status", "pending")
                ->where("expires_at", ">", now())
                ->first();

            if ($existingRequest) {
                return response()->json(
                    [
                        "message" =>
                            "A share request has already been sent to this email",
                        "request_id" => $existingRequest->id,
                    ],
                    200
                );
            }

            // Get recipient user
            $recipientUser = User::where("email", $recipientEmail)->first();
            if (!$recipientUser) {
                return response()->json(
                    ["error" => "Recipient user not found"],
                    404
                );
            }

            // Create the share request
            $shareRequest = new ShareRequest([
                "user_id" => Auth::id(),
                "todo_id" => $todo->id,
                "recipient_email" => $recipientEmail,
                "token" => ShareRequest::generateToken(),
                "share_type" => "task",
                "permission" => $permission,
                "expires_at" => Carbon::now()->addDays(7),
            ]);

            $shareRequest->save();

            // Send notification
            $notificationSent = $this->notificationService->sendShareRequestNotification(
                $recipientUser,
                Auth::user(),
                $shareRequest,
                $todo
            );

            if (!$notificationSent) {
                Log::warning(
                    "Failed to send notification for share request: " .
                        $shareRequest->id
                );
            }

            return response()->json(
                [
                    "success" => true,
                    "message" => "Task share request has been sent",
                    "request_id" => $shareRequest->id,
                ],
                201
            );
        } catch (\Exception $e) {
            Log::error("Error creating share request: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Failed to create share request: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Create a new global share request
     */
    public function storeGlobalShare(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            "email" => "required|email|exists:users,email",
            "permission" => "required|in:view,edit",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        $recipientEmail = $request->input("email");
        $permission = $request->input("permission");

        // Don't allow sharing with oneself
        if ($recipientEmail === Auth::user()->email) {
            return response()->json(
                ["error" => "Cannot share with yourself"],
                400
            );
        }

        // Create share request
        try {
            // Check for existing pending request
            $existingRequest = ShareRequest::where("user_id", Auth::id())
                ->whereNull("todo_id")
                ->where("share_type", "global")
                ->where("recipient_email", $recipientEmail)
                ->where("status", "pending")
                ->where("expires_at", ">", now())
                ->first();

            if ($existingRequest) {
                return response()->json(
                    [
                        "message" =>
                            "A global share request has already been sent to this email",
                        "request_id" => $existingRequest->id,
                    ],
                    200
                );
            }

            // Get recipient user
            $recipientUser = User::where("email", $recipientEmail)->first();
            if (!$recipientUser) {
                return response()->json(
                    ["error" => "Recipient user not found"],
                    404
                );
            }

            // Create the share request
            $shareRequest = new ShareRequest([
                "user_id" => Auth::id(),
                "todo_id" => null,
                "recipient_email" => $recipientEmail,
                "token" => ShareRequest::generateToken(),
                "share_type" => "global",
                "permission" => $permission,
                "expires_at" => Carbon::now()->addDays(7),
            ]);

            $shareRequest->save();

            // Send notification
            $notificationSent = $this->notificationService->sendGlobalShareRequestNotification(
                $recipientUser,
                Auth::user(),
                $shareRequest
            );

            if (!$notificationSent) {
                Log::warning(
                    "Failed to send notification for global share request: " .
                        $shareRequest->id
                );
            }

            return response()->json(
                [
                    "success" => true,
                    "message" => "Global share request has been sent",
                    "request_id" => $shareRequest->id,
                ],
                201
            );
        } catch (\Exception $e) {
            Log::error(
                "Error creating global share request: " . $e->getMessage()
            );
            return response()->json(
                [
                    "error" =>
                        "Failed to create global share request: " .
                        $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Approve a share request using the token
     */
    public function approve(string $token): JsonResponse
    {
        try {
            $shareRequest = ShareRequest::where("token", $token)
                ->where("status", "pending")
                ->where("expires_at", ">", now())
                ->first();

            if (!$shareRequest) {
                return response()->json(
                    ["error" => "Invalid or expired share request"],
                    404
                );
            }

            $user = Auth::user();

            // Ensure the authenticated user is the intended recipient
            if ($user && $user->email !== $shareRequest->recipient_email) {
                return response()->json(["error" => "Unauthorized"], 403);
            }

            $approved = $shareRequest->approve();

            if (!$approved) {
                return response()->json(
                    ["error" => "Failed to approve share request"],
                    500
                );
            }

            return response()->json([
                "success" => true,
                "message" =>
                    $shareRequest->share_type === "global"
                        ? "Global sharing approved successfully"
                        : "Task sharing approved successfully",
            ]);
        } catch (\Exception $e) {
            Log::error("Error approving share request: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Failed to approve share request: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Reject a share request using the token
     */
    public function reject(string $token): JsonResponse
    {
        try {
            $shareRequest = ShareRequest::where("token", $token)
                ->where("status", "pending")
                ->where("expires_at", ">", now())
                ->first();

            if (!$shareRequest) {
                return response()->json(
                    ["error" => "Invalid or expired share request"],
                    404
                );
            }

            $user = Auth::user();

            // Ensure the authenticated user is the intended recipient
            if ($user && $user->email !== $shareRequest->recipient_email) {
                return response()->json(["error" => "Unauthorized"], 403);
            }

            $rejected = $shareRequest->reject();

            if (!$rejected) {
                return response()->json(
                    ["error" => "Failed to reject share request"],
                    500
                );
            }

            return response()->json([
                "success" => true,
                "message" => "Share request rejected successfully",
            ]);
        } catch (\Exception $e) {
            Log::error("Error rejecting share request: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Failed to reject share request: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Cancel a pending share request
     */
    public function cancel(ShareRequest $shareRequest): JsonResponse
    {
        // Ensure the authenticated user is the requester
        if ($shareRequest->user_id !== Auth::id()) {
            return response()->json(["error" => "Unauthorized"], 403);
        }

        // Ensure the request is still pending
        if ($shareRequest->status !== "pending") {
            return response()->json(
                ["error" => "This request has already been processed"],
                400
            );
        }

        try {
            $shareRequest->status = "cancelled";
            $shareRequest->save();

            return response()->json([
                "success" => true,
                "message" => "Share request cancelled successfully",
            ]);
        } catch (\Exception $e) {
            Log::error("Error cancelling share request: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Failed to cancel share request: " . $e->getMessage(),
                ],
                500
            );
        }
    }
}
