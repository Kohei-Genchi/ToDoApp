<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
        $channels = [];

        // Use Line channel if token is set
        if (!empty($notifiable->line_notify_token)) {
            $channels[] = "line";
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
     * Get the Line representation of the notification.
     */
    public function toLine(object $notifiable): string
    {
        // Use the same formatting function as before
        return $this->formatLineMessage($notifiable);
    }

    /**
     * Legacy direct send method for Line Notify
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
}
