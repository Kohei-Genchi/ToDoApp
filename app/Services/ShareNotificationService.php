<?php

namespace App\Services;

use App\Models\ShareRequest;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ShareNotificationService
{
    protected $lineNotifyService;

    public function __construct(LineNotifyService $lineNotifyService)
    {
        $this->lineNotifyService = $lineNotifyService;
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

        // Try Line notification first if the user has a Line token
        if ($recipient->line_notify_token) {
            $success = $this->sendLineNotification($recipient, $message);
            if ($success) {
                return true;
            }
        }

        // Fall back to Slack if Line fails or isn't configured
        if ($recipient->slack_webhook_url) {
            $success = $this->sendSlackNotification($recipient, $message);
            if ($success) {
                return true;
            }
        }

        // Both Line and Slack failed or weren't configured
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

        $message = "\n【カテゴリー共有リクエスト】\n\n";
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
     * Send a notification through Line Notify
     */
    protected function sendLineNotification(User $user, string $message): bool
    {
        try {
            return $this->lineNotifyService->send(
                $user->line_notify_token,
                $message
            );
        } catch (\Exception $e) {
            Log::error("Line notification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a notification through Slack webhook
     */
    protected function sendSlackNotification(User $user, string $message): bool
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post($user->slack_webhook_url, [
                "json" => [
                    "text" => $message,
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            Log::error("Slack notification error: " . $e->getMessage());
            return false;
        }
    }
}
