<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\SlackNotifyService;
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
    public function toSlack(object $notifiable): string
    {
        return $this->formatSlackMessage($notifiable);
    }

    /**
     * Format message for Slack
     */
    protected function formatSlackMessage(object $notifiable): string
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
                $message .= "\n\n*残っているタスク:*";

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
}
