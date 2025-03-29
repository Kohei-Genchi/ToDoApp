<?php

namespace App\Http\Controllers;

use App\Models\ShareRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ShareNotificationWebController extends Controller
{
    /**
     * Handle incoming request approval - web UI
     *
     * This controller handles the web route for approving
     * share requests via Line/Slack URLs
     */
    public function approveRequest(string $token): View
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

            // Determine share type for UI
            $shareTypeName = "全タスク"; // Default
            if ($shareRequest->share_type === "task") {
                $shareTypeName = "タスク";
            } elseif ($shareRequest->share_type === "category") {
                $shareTypeName = "カテゴリー";
            }

            return view("share-requests.approved", [
                "shareRequest" => $shareRequest,
                "requesterName" => $requesterName,
                "shareType" => $shareTypeName,
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
     * Handle incoming request rejection - web UI
     */
    public function rejectRequest(string $token): View
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

            // Determine share type for UI
            $shareTypeName = "全タスク"; // Default
            if ($shareRequest->share_type === "task") {
                $shareTypeName = "タスク";
            } elseif ($shareRequest->share_type === "category") {
                $shareTypeName = "カテゴリー";
            }

            return view("share-requests.rejected", [
                "shareRequest" => $shareRequest,
                "requesterName" => $requesterName,
                "shareType" => $shareTypeName,
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

    public function processApproval(string $token)
    {
        Log::info("Direct share approval called", ["token" => $token]);

        // Find the share request
        $shareRequest = ShareRequest::where("token", $token)
            ->where("status", "pending")
            ->where("expires_at", ">", now())
            ->first();

        if (!$shareRequest) {
            return view("share-requests.error", [
                "message" => "無効または期限切れのリクエストです。",
                "title" => "エラー",
            ]);
        }

        // Find recipient user
        $recipient = User::where(
            "email",
            $shareRequest->recipient_email
        )->first();
        if (!$recipient) {
            return view("share-requests.error", [
                "message" => "受信者が見つかりません。",
                "title" => "エラー",
            ]);
        }

        // Force login as recipient
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        Auth::login($recipient, true);
        session()->regenerate();

        // Process the approval (reuse existing method)
        return $this->approveRequest($token);
    }

    /**
     * Show a list of pending share requests for the authenticated user - web UI
     */
    public function index(): View
    {
        $outgoingRequests = ShareRequest::where("user_id", Auth::id())
            ->where("status", "pending")
            ->where("expires_at", ">", now())
            ->with(["todo", "category"])
            ->get();

        $incomingRequests = ShareRequest::where(
            "recipient_email",
            Auth::user()->email
        )
            ->where("status", "pending")
            ->where("expires_at", ">", now())
            ->with(["todo", "category", "requester"])
            ->get();

        return view("share-requests.index", [
            "outgoingRequests" => $outgoingRequests,
            "incomingRequests" => $incomingRequests,
        ]);
    }
}
