<?php

namespace App\Services;

use App\Models\ShareRequest;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ShareNotificationService
{
    protected $slackNotifyService;

    public function __construct(SlackNotifyService $slackNotifyService)
    {
        $this->slackNotifyService = $slackNotifyService;
    }

    /**
     * Send a notification about a category share request
     *
     * @param User $recipient The user receiving the share request
     * @param User $requester The user making the share request
     * @param ShareRequest $shareRequest The share request object
     * @param Category $category The category being shared
     * @return bool Whether the notification was sent successfully
     */
    public function sendCategoryShareRequestNotification(
        User $recipient,
        User $requester,
        ShareRequest $shareRequest,
        $category
    ): bool {
        // Generate approve and reject URLs
        $approveUrl = URL::signedRoute("share-requests.approve", [
            "token" => $shareRequest->token,
        ]);
        $rejectUrl = URL::signedRoute("share-requests.reject", [
            "token" => $shareRequest->token,
        ]);

        // Create the message
        $message = $this->buildCategoryShareRequestMessage(
            $requester,
            $category,
            $shareRequest,
            $approveUrl,
            $rejectUrl
        );

        // Send notification via Slack if the user has a webhook URL
        if ($recipient->slack_webhook_url) {
            $success = $this->sendSlackNotification($recipient, $message);
            if ($success) {
                return true;
            }
        }

        // No valid notification channels configured
        Log::warning(
            "Failed to send category share request notification to {$recipient->email}. No valid notification channels configured."
        );
        return false;
    }

    /**
     * Build the message for a category share request
     */
    protected function buildCategoryShareRequestMessage(
        User $requester,
        $category,
        ShareRequest $shareRequest,
        string $approveUrl,
        string $rejectUrl
    ): string {
        $permissionText =
            $shareRequest->permission === "edit" ? "編集可能" : "閲覧のみ";

        $message = "\n*【カテゴリー共有リクエスト】*\n\n";
        $message .= "{$requester->name}さんがカテゴリー「{$category->name}」を共有しようとしています。\n\n";
        $message .= "このカテゴリーに属するすべてのタスクが共有されます。\n";
        $message .= "権限: {$permissionText}\n";
        $message .= "有効期限: {$shareRequest->expires_at->format(
            "Y-m-d H:i"
        )}\n\n";
        $message .= "承認するには下のリンクをクリックしてください:\n";
        $message .= "{$approveUrl}\n\n";
        $message .= "拒否するには下のリンクをクリックしてください:\n";
        $message .= "{$rejectUrl}\n";

        return $message;
    }

    /**
     * Send a notification through Slack
     */
    protected function sendSlackNotification(User $user, string $message): bool
    {
        try {
            return $this->slackNotifyService->send(
                $user->slack_webhook_url,
                $message
            );
        } catch (\Exception $e) {
            Log::error("Slack notification error: " . $e->getMessage());
            return false;
        }
    }
}
