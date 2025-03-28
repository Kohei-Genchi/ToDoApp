<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ShareRequest;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class ShareNotification extends Notification
{
    use Queueable;

    protected $shareRequest;
    protected $requesterName;
    protected $itemName;
    protected $itemType;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        ShareRequest $shareRequest,
        $requesterName,
        $itemName,
        $itemType
    ) {
        $this->shareRequest = $shareRequest;
        $this->requesterName = $requesterName;
        $this->itemName = $itemName;
        $this->itemType = $itemType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        // Use our custom Slack channel if webhook URL is set
        if (!empty($notifiable->slack_webhook_url)) {
            $channels[] = "custom-slack";
        } else {
            // Use email as fallback
            $channels[] = "mail";
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $approveUrl = URL::signedRoute("share-requests.web.approve", [
            "token" => $this->shareRequest->token,
        ]);

        $rejectUrl = URL::signedRoute("share-requests.web.reject", [
            "token" => $this->shareRequest->token,
        ]);

        // Ensure requesterName is treated as a string
        $requesterName = is_string($this->requesterName)
            ? $this->requesterName
            : (string) $this->requesterName;
        $itemName = is_string($this->itemName)
            ? $this->itemName
            : (string) $this->itemName;

        return (new MailMessage())
            ->subject(
                "{$requesterName}さんから{$this->itemType}共有のリクエストが届きました"
            )
            ->line(
                "{$requesterName}さんがあなたと{$this->itemType}「{$itemName}」を共有しようとしています。"
            )
            ->line(
                "権限: " .
                    ($this->shareRequest->permission === "edit"
                        ? "編集可能"
                        : "閲覧のみ")
            )
            ->action("リクエストを承認", $approveUrl)
            ->line("または")
            ->action("リクエストを拒否", $rejectUrl)
            ->line("このリクエストは7日間有効です。");
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param mixed $notifiable
     * @return string
     */
    public function toSlack($notifiable): string
    {
        try {
            // Generate URLs for web browser
            $baseUrl = config("app.url");

            // $baseUrl = "http://192.168.0.107:8080";
            $token = $this->shareRequest->token;

            // Direct URLs for API endpoints
            $approveUrl = "{$baseUrl}/share/approve/{$token}";
            $rejectUrl = "{$baseUrl}/api/slack/reject/{$token}";

            // Ensure requesterName is treated as a string
            $requesterName = is_string($this->requesterName)
                ? $this->requesterName
                : (string) $this->requesterName;
            $itemName = is_string($this->itemName)
                ? $this->itemName
                : (string) $this->itemName;

            // Create main message text with clear links
            $messageText = "*{$requesterName}さんから{$this->itemType}共有のリクエストが届きました*\n\n";
            $messageText .= "{$requesterName}さんがあなたと{$this->itemType}「{$itemName}」を共有しようとしています。\n";
            $messageText .=
                "権限: " .
                ($this->shareRequest->permission === "edit"
                    ? "編集可能"
                    : "閲覧のみ") .
                "\n\n";
            $messageText .= "・<{$approveUrl}|承認する>\n";
            $messageText .= "・<{$rejectUrl}|拒否する>\n\n";
            $messageText .= "このリクエストは7日間有効です。";

            // Log for debugging
            Log::debug("ShareNotification: Sending Slack message", [
                "message_length" => strlen($messageText),
                "token" => substr($token, 0, 6) . "...",
            ]);

            return $messageText;
        } catch (\Exception $e) {
            // If anything fails, return a simple error message
            Log::error(
                "Error formatting Slack notification: " . $e->getMessage(),
                ["exception" => $e]
            );

            $baseUrl = config("app.url");
            $token = $this->shareRequest->token;

            $approveUrl = "{$baseUrl}/api/slack/approve/{$token}";
            $rejectUrl = "{$baseUrl}/api/slack/reject/{$token}";

            return "カテゴリー共有リクエストが届いています。\n承認: {$approveUrl}\n拒否: {$rejectUrl}";
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
                //
            ];
    }
}
