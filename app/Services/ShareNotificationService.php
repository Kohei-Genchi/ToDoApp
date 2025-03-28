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
     * @param Category|string $itemName The category or item name being shared
     * @param string $itemType Type of item being shared ('category', 'task', etc.)
     * @return bool Whether the notification was sent successfully
     */
    public function sendShareRequestNotification(
        User $recipient,
        User $requester,
        ShareRequest $shareRequest,
        $itemName,
        string $itemType = "カテゴリー"
    ): bool {
        try {
            // Generate approve and reject URLs
            $approveUrl = URL::signedRoute("api.share-requests.approve", [
                "token" => $shareRequest->token,
            ]);

            $rejectUrl = URL::signedRoute("api.share-requests.reject", [
                "token" => $shareRequest->token,
            ]);

            $approveWebUrl = URL::signedRoute("share-requests.web.approve", [
                "token" => $shareRequest->token,
            ]);

            $rejectWebUrl = URL::signedRoute("share-requests.web.reject", [
                "token" => $shareRequest->token,
            ]);

            // Extract item name if it's a category object
            $itemNameStr = is_string($itemName)
                ? $itemName
                : (is_object($itemName) &&
                method_exists($itemName, "getAttribute")
                    ? $itemName->getAttribute("name")
                    : "Shared Item");

            // Create the message
            $message = $this->buildShareRequestMessage(
                $requester,
                $itemNameStr,
                $shareRequest,
                $approveUrl,
                $rejectUrl,
                $itemType
            );

            // Send notification via Slack if the user has a webhook URL
            if ($recipient->slack_webhook_url) {
                Log::info("Sending slack notification to user", [
                    "recipient_id" => $recipient->id,
                    "webhook_length" => strlen($recipient->slack_webhook_url),
                ]);

                $success = $this->sendSlackNotification($recipient, $message);
                return $success;
            }

            // No valid notification channels configured
            Log::warning(
                "Failed to send share request notification to {$recipient->email}. No valid notification channels configured."
            );
            return false;
        } catch (\Exception $e) {
            Log::error(
                "Error in sendShareRequestNotification: " . $e->getMessage(),
                [
                    "trace" => $e->getTraceAsString(),
                ]
            );
            return false;
        }
    }

    /**
     * Build the message for a share request
     */
    protected function buildShareRequestMessage(
        User $requester,
        string $itemName,
        ShareRequest $shareRequest,
        string $approveUrl,
        string $rejectUrl,
        string $itemType = "カテゴリー"
    ): string {
        $permissionText =
            $shareRequest->permission === "edit" ? "編集可能" : "閲覧のみ";

        $message = "\n*【{$itemType}共有リクエスト】*\n\n";
        $message .= "{$requester->name}さんが{$itemType}「{$itemName}」を共有しようとしています。\n\n";

        if ($itemType === "カテゴリー") {
            $message .=
                "このカテゴリーに属するすべてのタスクが共有されます。\n";
        }

        $message .= "権限: {$permissionText}\n";
        $message .= "有効期限: {$shareRequest->expires_at->format(
            "Y-m-d H:i"
        )}\n\n";

        // Use Slack's link format: <url|text>
        $message .= "承認するには下のリンクをクリックしてください:\n";
        $message .= "<{$approveUrl}|承認する>\n\n";
        $message .= "拒否するには下のリンクをクリックしてください:\n";
        $message .= "<{$rejectUrl}|拒否する>\n";

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
