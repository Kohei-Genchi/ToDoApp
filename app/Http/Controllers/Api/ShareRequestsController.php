<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShareRequest;
use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
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
                ->with(["todo", "category"])
                ->get()
                ->map(function ($request) {
                    $itemName = "";

                    if ($request->share_type === "task" && $request->todo) {
                        $itemName = $request->todo->title;
                    } elseif (
                        $request->share_type === "category" &&
                        $request->category
                    ) {
                        $itemName = $request->category->name;
                    } else {
                        $itemName = "All Tasks";
                    }

                    return [
                        "id" => $request->id,
                        "recipient_email" => $request->recipient_email,
                        "permission" => $request->permission,
                        "share_type" => $request->share_type,
                        "item_name" => $itemName,
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
                ->with(["todo", "category", "requester"])
                ->get()
                ->map(function ($request) {
                    $itemName = "";

                    if ($request->share_type === "task" && $request->todo) {
                        $itemName = $request->todo->title;
                    } elseif (
                        $request->share_type === "category" &&
                        $request->category
                    ) {
                        $itemName = $request->category->name;
                    } else {
                        $itemName = "All Tasks";
                    }

                    return [
                        "id" => $request->id,
                        "requester_name" => $request->requester->name,
                        "requester_email" => $request->requester->email,
                        "permission" => $request->permission,
                        "share_type" => $request->share_type,
                        "item_name" => $itemName,
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
     * Create a new task share request - Deprecated in favor of category sharing
     */
    public function storeTaskShare(Request $request, Todo $todo): JsonResponse
    {
        // Return a message indicating that individual task sharing is deprecated
        return response()->json(
            [
                "error" =>
                    "個別タスク共有は廃止されました。代わりにカテゴリー共有を使用してください。",
                "use_category_sharing" => true,
            ],
            400
        );
    }

    /**
     * Create a new category share request
     */
    public function storeCategoryShare(
        Request $request,
        Category $category
    ): JsonResponse {
        // Validate request
        $validator = Validator::make($request->all(), [
            "email" => "required|email|exists:users,email",
            "permission" => "required|in:view,edit",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        // Check if the authenticated user owns this category
        if ($category->user_id !== Auth::id()) {
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
                ->where("category_id", $category->id)
                ->where("recipient_email", $recipientEmail)
                ->where("status", "pending")
                ->where("expires_at", ">", now())
                ->first();

            if ($existingRequest) {
                return response()->json(
                    [
                        "message" =>
                            "A category share request has already been sent to this email",
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
                "category_id" => $category->id,
                "todo_id" => null,
                "recipient_email" => $recipientEmail,
                "token" => ShareRequest::generateToken(),
                "share_type" => "category",
                "permission" => $permission,
                "expires_at" => Carbon::now()->addDays(7),
            ]);

            $shareRequest->save();

            // Send notification
            $notificationSent = $this->notificationService->sendCategoryShareRequestNotification(
                $recipientUser,
                Auth::user(),
                $shareRequest,
                $category
            );

            if (!$notificationSent) {
                Log::warning(
                    "Failed to send notification for category share request: " .
                        $shareRequest->id
                );
            }

            return response()->json(
                [
                    "success" => true,
                    "message" => "Category share request has been sent",
                    "request_id" => $shareRequest->id,
                ],
                201
            );
        } catch (\Exception $e) {
            Log::error(
                "Error creating category share request: " . $e->getMessage()
            );
            return response()->json(
                [
                    "error" =>
                        "Failed to create category share request: " .
                        $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Create a new global share request - Deprecated in favor of category sharing
     */
    public function storeGlobalShare(Request $request): JsonResponse
    {
        // Return a message indicating that global task sharing is deprecated
        return response()->json(
            [
                "error" =>
                    "グローバルタスク共有は廃止されました。代わりにカテゴリー共有を使用してください。",
                "use_category_sharing" => true,
            ],
            400
        );
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

            $shareType = $shareRequest->share_type;
            $shareTypeMessage =
                $shareType === "category"
                    ? "Category sharing approved successfully"
                    : "Share request approved successfully";

            return response()->json([
                "success" => true,
                "message" => $shareTypeMessage,
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
