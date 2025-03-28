<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomSlackChannel
{
    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            if (!method_exists($notifiable, "routeNotificationForSlack")) {
                Log::warning("Notifiable doesn't support Slack notifications", [
                    "notifiable_class" => get_class($notifiable),
                ]);
                return;
            }

            $webhookUrl = $notifiable->routeNotificationForSlack();
            if (empty($webhookUrl)) {
                Log::warning("No webhook URL for notifiable", [
                    "notifiable_id" => $notifiable->id ?? "unknown",
                ]);
                return;
            }

            // Get message from notification
            $message = $notification->toSlack($notifiable);
            if (empty($message)) {
                Log::warning("Empty Slack message", [
                    "notification_class" => get_class($notification),
                ]);
                return;
            }

            Log::info("Sending Slack notification", [
                "webhook_length" => strlen($webhookUrl),
                "message_length" => is_string($message)
                    ? strlen($message)
                    : json_encode($message),
            ]);

            // Prepare payload
            $payload = is_string($message) ? ["text" => $message] : $message;

            // Send notification via HTTP
            $response = Http::post($webhookUrl, $payload);

            if (!$response->successful()) {
                Log::error("Slack notification failed", [
                    "status" => $response->status(),
                    "response" => $response->body(),
                ]);
                return;
            }

            Log::info("Slack notification sent successfully");
        } catch (\Exception $e) {
            Log::error(
                "Error sending slack notification: " . $e->getMessage(),
                [
                    "exception" => $e,
                    "notifiable_id" => $notifiable->id ?? "unknown",
                ]
            );
        }
    }
}
