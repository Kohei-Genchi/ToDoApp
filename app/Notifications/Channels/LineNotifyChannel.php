<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\LineNotifyService;
use Illuminate\Support\Facades\Log;

class LineNotifyChannel
{
    protected $lineNotifyService;

    /**
     * Create a new Line Notify channel instance.
     */
    public function __construct(LineNotifyService $lineNotifyService)
    {
        $this->lineNotifyService = $lineNotifyService;
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
        // Get token from notifiable entity
        $token = $notifiable->routeNotificationForLine();

        if (empty($token)) {
            Log::warning("No Line Notify token available for user", [
                "user_id" => $notifiable->id ?? "unknown",
            ]);
            return;
        }

        // Get the message content from the notification
        $message = $notification->toLine($notifiable);

        if (empty($message)) {
            Log::warning("Empty Line notification message", [
                "user_id" => $notifiable->id ?? "unknown",
                "notification" => get_class($notification),
            ]);
            return;
        }

        // Send the notification
        try {
            $result = $this->lineNotifyService->send($token, $message);

            if (!$result) {
                Log::error("Failed to send Line notification", [
                    "error" => $this->lineNotifyService->getLastError(),
                    "user_id" => $notifiable->id ?? "unknown",
                ]);
            }
        } catch (\Exception $e) {
            Log::error(
                "Exception sending Line notification: " . $e->getMessage(),
                [
                    "exception" => $e,
                    "user_id" => $notifiable->id ?? "unknown",
                ]
            );
        }
    }
}
