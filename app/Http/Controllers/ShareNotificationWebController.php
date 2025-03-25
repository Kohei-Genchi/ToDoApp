<?php

namespace App\Http\Controllers;

use App\Models\ShareRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShareNotificationWebController extends Controller
{
    /**
     * Handle incoming request approval
     *
     * This controller handles the web route for approving
     * share requests via Line/Slack URLs
     */
    public function approveRequest(string $token)
    {
        try {
            $shareRequest = ShareRequest::where("token", $token)
                ->where("status", "pending")
                ->where("expires_at", ">", now())
                ->first();

            if (!$shareRequest) {
                return view("share-requests.error", [
                    "message" => "リクエストが無効か期限切れです。",
                    "title" => "共有リクエストエラー",
                ]);
            }

            // Find the recipient user
            $recipient = User::where(
                "email",
                $shareRequest->recipient_email
            )->first();

            // Auto-login if not authenticated or wrong user
            if (
                !Auth::check() ||
                Auth::user()->email !== $shareRequest->recipient_email
            ) {
                if ($recipient) {
                    Auth::login($recipient);
                }
            }

            // Process the approval
            $success = $shareRequest->approve();

            if (!$success) {
                return view("share-requests.error", [
                    "message" => "共有リクエストの承認に失敗しました。",
                    "title" => "共有リクエストエラー",
                ]);
            }

            // Get requester info for UI
            $requester = User::find($shareRequest->user_id);
            $requesterName = $requester ? $requester->name : "不明なユーザー";

            return view("share-requests.approved", [
                "shareRequest" => $shareRequest,
                "requesterName" => $requesterName,
                "shareType" =>
                    $shareRequest->share_type === "task"
                        ? "タスク"
                        : "全タスク",
            ]);
        } catch (\Exception $e) {
            Log::error("Error in share request approval: " . $e->getMessage());

            return view("share-requests.error", [
                "message" =>
                    "処理中にエラーが発生しました: " . $e->getMessage(),
                "title" => "共有リクエストエラー",
            ]);
        }
    }

    /**
     * Handle incoming request rejection
     */
    public function rejectRequest(string $token)
    {
        try {
            $shareRequest = ShareRequest::where("token", $token)
                ->where("status", "pending")
                ->where("expires_at", ">", now())
                ->first();

            if (!$shareRequest) {
                return view("share-requests.error", [
                    "message" => "リクエストが無効か期限切れです。",
                    "title" => "共有リクエスト拒否",
                ]);
            }

            // Find the recipient user
            $recipient = User::where(
                "email",
                $shareRequest->recipient_email
            )->first();

            // Auto-login if not authenticated or wrong user
            if (
                !Auth::check() ||
                Auth::user()->email !== $shareRequest->recipient_email
            ) {
                if ($recipient) {
                    Auth::login($recipient);
                }
            }

            // Process the rejection
            $success = $shareRequest->reject();

            if (!$success) {
                return view("share-requests.error", [
                    "message" => "共有リクエストの拒否に失敗しました。",
                    "title" => "共有リクエストエラー",
                ]);
            }

            // Get requester info for UI
            $requester = User::find($shareRequest->user_id);
            $requesterName = $requester ? $requester->name : "不明なユーザー";

            return view("share-requests.rejected", [
                "shareRequest" => $shareRequest,
                "requesterName" => $requesterName,
                "shareType" =>
                    $shareRequest->share_type === "task"
                        ? "タスク"
                        : "全タスク",
            ]);
        } catch (\Exception $e) {
            Log::error("Error in share request rejection: " . $e->getMessage());

            return view("share-requests.error", [
                "message" =>
                    "処理中にエラーが発生しました: " . $e->getMessage(),
                "title" => "共有リクエストエラー",
            ]);
        }
    }

    /**
     * Show a list of pending share requests for the authenticated user
     */
    public function index()
    {
        $outgoingRequests = ShareRequest::where("user_id", Auth::id())
            ->where("status", "pending")
            ->where("expires_at", ">", now())
            ->with(["todo"])
            ->get();

        $incomingRequests = ShareRequest::where(
            "recipient_email",
            Auth::user()->email
        )
            ->where("status", "pending")
            ->where("expires_at", ">", now())
            ->with(["todo", "requester"])
            ->get();

        return view("share-requests.index", [
            "outgoingRequests" => $outgoingRequests,
            "incomingRequests" => $incomingRequests,
        ]);
    }
}
