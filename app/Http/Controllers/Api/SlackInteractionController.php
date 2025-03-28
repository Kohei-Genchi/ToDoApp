<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShareRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SlackInteractionController extends Controller
{
    /**
     * Handle direct approval from Slack
     *
     * @param string $token
     * @return \Illuminate\Http\Response
     */
    public function approveShare(string $token)
    {
        try {
            Log::info("Slack approveShare called", ["token" => $token]);

            $shareRequest = ShareRequest::where("token", $token)
                ->where("status", "pending")
                ->where("expires_at", ">", now())
                ->first();

            if (!$shareRequest) {
                Log::error("Share request not found or expired", [
                    "token" => $token,
                ]);
                return response()->view(
                    "share-requests.error",
                    [
                        "message" => "リクエストが無効か期限切れです。",
                        "title" => "共有リクエストエラー",
                    ],
                    404
                );
            }

            Log::info("Share request found", [
                "id" => $shareRequest->id,
                "type" => $shareRequest->share_type,
                "category_id" => $shareRequest->category_id,
                "email" => $shareRequest->recipient_email,
            ]);

            // ユーザー情報を詳細にロギング
            // Find the recipient user
            $recipient = User::where(
                "email",
                $shareRequest->recipient_email
            )->first();

            Log::info("Recipient user info", [
                "found" => $recipient ? "yes" : "no",
                "id" => $recipient ? $recipient->id : "none",
                "email" => $recipient ? $recipient->email : "none",
            ]);

            // 重要：現在のログインユーザーをチェック
            Log::info("Current auth status", [
                "logged_in" => Auth::check() ? "yes" : "no",
                "user_id" => Auth::check() ? Auth::id() : "none",
                "user_email" => Auth::check() ? Auth::user()->email : "none",
            ]);

            // Auto-login if not authenticated or wrong user
            if (
                !Auth::check() ||
                Auth::user()->email !== $shareRequest->recipient_email
            ) {
                if ($recipient) {
                    // 強制的にログイン - これが重要！
                    Log::info("Forcing login for recipient", [
                        "id" => $recipient->id,
                    ]);
                    Auth::login($recipient);

                    // ログイン後の状態確認
                    Log::info("After forced login", [
                        "logged_in" => Auth::check() ? "yes" : "no",
                        "user_id" => Auth::check() ? Auth::id() : "none",
                        "user_email" => Auth::check()
                            ? Auth::user()->email
                            : "none",
                    ]);
                }
            }

            // Process the approval
            Log::info("Calling shareRequest->approve()");
            $success = $shareRequest->approve();
            Log::info("Approval result", [
                "success" => $success ? "yes" : "no",
            ]);

            // ここまでのコードは変更なし...
        } catch (\Exception $e) {
            Log::error(
                "Error in Slack share request approval: " . $e->getMessage(),
                ["exception" => $e->getTraceAsString()]
            );

            return response()->view(
                "share-requests.error",
                [
                    "message" =>
                        "処理中にエラーが発生しました: " . $e->getMessage(),
                    "title" => "共有リクエストエラー",
                ],
                500
            );
        }
    }

    /**
     * Handle direct rejection from Slack
     *
     * @param string $token
     * @return \Illuminate\Http\Response
     */
    public function rejectShare(string $token)
    {
        try {
            $shareRequest = ShareRequest::where("token", $token)
                ->where("status", "pending")
                ->where("expires_at", ">", now())
                ->first();

            if (!$shareRequest) {
                return response()->view(
                    "share-requests.error",
                    [
                        "message" => "リクエストが無効か期限切れです。",
                        "title" => "共有リクエスト拒否",
                    ],
                    404
                );
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
                return response()->view(
                    "share-requests.error",
                    [
                        "message" => "共有リクエストの拒否に失敗しました。",
                        "title" => "共有リクエストエラー",
                    ],
                    500
                );
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

            return response()->view("share-requests.rejected", [
                "shareRequest" => $shareRequest,
                "requesterName" => $requesterName,
                "shareType" => $shareTypeName,
            ]);
        } catch (\Exception $e) {
            Log::error(
                "Error in Slack share request rejection: " . $e->getMessage()
            );

            return response()->view(
                "share-requests.error",
                [
                    "message" =>
                        "処理中にエラーが発生しました: " . $e->getMessage(),
                    "title" => "共有リクエストエラー",
                ],
                500
            );
        }
    }
}
