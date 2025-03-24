<?php
// app/Notifications/TaskReminder.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use App\Services\LineNotifyService;
use Illuminate\Support\Facades\Log;

class TaskReminder extends Notification
{
    use Queueable;

    protected $message;
    protected $todosCount;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $message, int $todosCount)
    {
        $this->message = $message;
        $this->todosCount = $todosCount;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // If Line notify is configured, we'll handle it separately
        if ($notifiable->line_notify_token) {
            // We'll handle Line separately in the send() method
            return [];
        }

        // For other channels, use the normal notification system
        if (
            config("services.slack.notifications.bot_user_oauth_token") ||
            $notifiable->slack_webhook_url
        ) {
            return ["slack"];
        }

        return ["mail"];
    }

    /**
     * Direct send method that handles Line notifications
     * This is called directly, not through the notification system
     */
    /**
     * Direct send method that handles Line notifications
     * This is called directly, not through the notification system
     *
     * @return array [bool $success, string|null $error]
     */
    public function sendToLine(object $notifiable): array
    {
        if (!$notifiable->line_notify_token) {
            return [false, "Line Notify token not found for user"];
        }

        $message = $this->formatLineMessage($notifiable);
        $lineService = new LineNotifyService();

        $result = $lineService->send($notifiable->line_notify_token, $message);

        return [$result, $result ? null : $lineService->getLastError()];
    }

    /**
     * Format message for Line Notify
     */
    protected function formatLineMessage(object $notifiable): string
    {
        // Start with main message
        $message = "\n" . $this->message;

        // Add list of tasks if there are any
        if ($this->todosCount > 0) {
            $pendingTasks = $notifiable
                ->todos()
                ->where("status", "pending")
                ->whereDate("due_date", today())
                ->get();

            if ($pendingTasks->count() > 0) {
                $message .= "\n\n残っているタスク:";

                // Add each task (limit to 10 to avoid message becoming too long)
                foreach ($pendingTasks->take(10) as $index => $task) {
                    $message .= "\n" . ($index + 1) . ". " . $task->title;
                }

                // If there are more tasks than shown
                if ($pendingTasks->count() > 10) {
                    $message .=
                        "\n...他に " .
                        ($pendingTasks->count() - 10) .
                        " 件のタスクがあります";
                }
            }
        }

        // Add link to app
        $message .= "\n\nタスクを確認: " . url("/todos?view=today");

        return $message;
    }

    // Your existing toMail() and toSlack() methods remain the same...
}
