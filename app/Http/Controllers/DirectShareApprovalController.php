<?php

namespace App\Http\Controllers;

use App\Models\ShareRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DirectShareApprovalController extends Controller
{
    /**
     * Process direct share approval
     */
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

        Log::info("Logged in as recipient", [
            "user_id" => Auth::id(),
            "email" => Auth::user()->email,
            "recipient_email" => $shareRequest->recipient_email,
        ]);

        // Directly insert into category_shares
        try {
            // Mark request as approved
            $shareRequest->status = "approved";
            $shareRequest->responded_at = now();
            $shareRequest->save();

            // Skip if not a category share
            if (
                $shareRequest->share_type !== "category" ||
                !$shareRequest->category_id
            ) {
                return view("share-requests.error", [
                    "message" => "カテゴリー共有のみサポートしています。",
                    "title" => "エラー",
                ]);
            }

            // Check if already shared
            $exists = DB::table("category_shares")
                ->where("category_id", $shareRequest->category_id)
                ->where("user_id", $recipient->id)
                ->exists();

            if (!$exists) {
                // Create share record
                DB::table("category_shares")->insert([
                    "category_id" => $shareRequest->category_id,
                    "user_id" => $recipient->id,
                    "permission" => $shareRequest->permission,
                    "created_at" => now(),
                    "updated_at" => now(),
                ]);
            }

            // Get requester info
            $requester = User::find($shareRequest->user_id);
            $requesterName = $requester ? $requester->name : "不明なユーザー";

            // Redirect to success page
            return view("share-requests.approved", [
                "shareRequest" => $shareRequest,
                "requesterName" => $requesterName,
                "shareType" => "カテゴリー",
                "message" => "カテゴリー共有が正常に承認されました。", // Add this line
                "title" => "承認完了", // Add this line
            ]);
        } catch (\Exception $e) {
            Log::error("Error processing share approval: " . $e->getMessage(), [
                "trace" => $e->getTraceAsString(),
            ]);

            return view("share-requests.error", [
                "message" =>
                    "共有処理中にエラーが発生しました: " . $e->getMessage(),
                "title" => "エラー",
            ]);
        }
    }
}
