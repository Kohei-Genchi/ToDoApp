<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ShareRequest;
use Illuminate\Support\Facades\URL;

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

        // Use Slack channel if webhook URL is set
        if (!empty($notifiable->slack_webhook_url)) {
            $channels[] = "slack";
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
     */
    public function toSlack(object $notifiable): string
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

        $message = "*{$requesterName}さんから{$this->itemType}共有のリクエストが届きました*\n\n";
        $message .= "{$requesterName}さんがあなたと{$this->itemType}「{$itemName}」を共有しようとしています。\n";
        $message .=
            "権限: " .
            ($this->shareRequest->permission === "edit"
                ? "編集可能"
                : "閲覧のみ") .
            "\n\n";
        $message .=
            "リクエストを承認するには、下のリンクをクリックしてください:\n";
        $message .= $approveUrl . "\n\n";
        $message .=
            "リクエストを拒否するには、下のリンクをクリックしてください:\n";
        $message .= $rejectUrl . "\n\n";
        $message .= "このリクエストは7日間有効です。";

        return $message;
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
