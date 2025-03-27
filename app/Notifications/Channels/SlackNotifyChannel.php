<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\SlackNotifyService;
use Illuminate\Support\Facades\Log;

class SlackNotifyChannel
{
    protected $slackNotifyService;

    /**
     * Create a new Slack Notification channel instance.
     */
    public function __construct(SlackNotifyService $slackNotifyService)
    {
        $this->slackNotifyService = $slackNotifyService;
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        // Get webhook URL from notifiable entity
        $webhookUrl = $notifiable->routeNotificationForSlack();

        if (empty($webhookUrl)) {
            Log::warning("No Slack Webhook URL available for user", [
                "user_id" => $notifiable->id ?? "unknown",
            ]);
            return;
        }

        // Get the message content from the notification
        $message = $notification->toSlack($notifiable);

        if (empty($message)) {
            Log::warning("Empty Slack notification message", [
                "user_id" => $notifiable->id ?? "unknown",
                "notification" => get_class($notification),
            ]);
            return;
        }

        // Send the notification
        try {
            $result = $this->slackNotifyService->send($webhookUrl, $message);

            if (!$result) {
                Log::error("Failed to send Slack notification", [
                    "error" => $this->slackNotifyService->getLastError(),
                    "user_id" => $notifiable->id ?? "unknown",
                ]);
            }
        } catch (\Exception $e) {
            Log::error(
                "Exception sending Slack notification: " . $e->getMessage(),
                [
                    "exception" => $e,
                    "user_id" => $notifiable->id ?? "unknown",
                ]
            );
        }
    }
}
