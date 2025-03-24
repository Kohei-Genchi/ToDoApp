<?php

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
            // Allow Slack if configured alongside Line
            $channels = [];
            if (!empty($notifiable->slack_webhook_url)) {
                $channels[] = "slack";
            }
            return $channels;
        }

        // For other channels, use the normal notification system
        $channels = [];

        if (!empty($notifiable->slack_webhook_url)) {
            $channels[] = "slack";
            // デバッグログ
            Log::info("Adding Slack channel for notification", [
                "user_id" => $notifiable->id,
                "webhook" =>
                    substr($notifiable->slack_webhook_url, 0, 15) . "...",
            ]);
        } else {
            Log::info("Slack webhook URL not set for user", [
                "user_id" => $notifiable->id,
            ]);
        }

        if (
            config("services.slack.notifications.bot_user_oauth_token") ||
            $notifiable->slack_webhook_url
        ) {
            $channels[] = "slack";
        }

        // Always add mail as a fallback
        $channels[] = "mail";

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject("TodoList Task Reminder")
            ->line($this->message);

        // If there are pending tasks, list them
        if ($this->todosCount > 0) {
            $pendingTasks = $notifiable
                ->todos()
                ->where("status", "pending")
                ->whereDate("due_date", today())
                ->get();

            if ($pendingTasks->count() > 0) {
                $message->line("Pending tasks:");

                foreach ($pendingTasks->take(10) as $task) {
                    $message->line("- " . $task->title);
                }

                if ($pendingTasks->count() > 10) {
                    $message->line(
                        "... and " .
                            ($pendingTasks->count() - 10) .
                            " more tasks"
                    );
                }
            }
        }

        return $message
            ->action("View Tasks", url("/todos?view=today"))
            ->line("Thank you for using TodoList!");
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        Log::info("Preparing Slack notification", [
            "user_id" => $notifiable->id,
            "slack_webhook" => !empty($notifiable->slack_webhook_url)
                ? "Set"
                : "Not set",
            "message" => $this->message,
        ]);

        $slackMessage = (new SlackMessage())
            ->from("TodoList", ":clipboard:")
            ->content($this->message);

        // If there are pending tasks, list them
        if ($this->todosCount > 0) {
            $pendingTasks = $notifiable
                ->todos()
                ->where("status", "pending")
                ->whereDate("due_date", today())
                ->get();

            if ($pendingTasks->count() > 0) {
                $taskList = "";

                foreach ($pendingTasks->take(10) as $index => $task) {
                    $taskList .= $index + 1 . ". " . $task->title . "\n";
                }

                if ($pendingTasks->count() > 10) {
                    $taskList .=
                        "... and " .
                        ($pendingTasks->count() - 10) .
                        " more tasks";
                }

                $slackMessage->attachment(function ($attachment) use (
                    $taskList
                ) {
                    $attachment->title("Pending Tasks")->content($taskList);
                });
            }
        }

        return $slackMessage;
    }

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

    /**
     * Direct send method that handles Slack notifications
     * This is called directly from SendReminders command
     *
     * @return array [bool $success, string|null $error]
     */
    public function sendToSlack(object $notifiable): array
    {
        if (empty($notifiable->slack_webhook_url)) {
            return [false, "Slack webhook URL not found for user"];
        }

        try {
            // Use the webhook URL directly
            $webhookUrl = $notifiable->slack_webhook_url;

            // Create a simple payload instead of using SlackMessage
            $payload = [
                'text' => $this->message,
                'username' => 'TodoList',
                'icon_emoji' => ':clipboard:',
            ];

            // Add attachment for tasks if any
            if ($this->todosCount > 0) {
                $pendingTasks = $notifiable
                    ->todos()
                    ->where("status", "pending")
                    ->whereDate("due_date", today())
                    ->get();

                if ($pendingTasks->count() > 0) {
                    $taskList = "";

                    foreach ($pendingTasks->take(10) as $index => $task) {
                        $taskList .= ($index + 1) . ". " . $task->title . "\n";
                    }

                    if ($pendingTasks->count() > 10) {
                        $taskList .= "... and " . ($pendingTasks->count() - 10) . " more tasks";
                    }

                    $payload['attachments'] = [
                        [
                            'title' => 'Pending Tasks',
                            'text' => $taskList,
                            'color' => '#36a64f',
                        ]
                    ];
                }
            }

            // Send using HTTP client directly
            $client = app(\GuzzleHttp\Client::class);
            $response = $client->post($webhookUrl, [
                'json' => $payload,
            ]);

            Log::info("Slack notification sent", [
                "user_id" => $notifiable->id,
                "status" => $response->getStatusCode()
            ]);

            return [true, null];
        } catch (\Exception $e) {
            Log::error("Error sending Slack notification: " . $e->getMessage());
            return [false, $e->getMessage()];
        }
    }
}
