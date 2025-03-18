<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

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
        // Check if the environment is configured to use Slack
        // If SLACK_BOT_USER_OAUTH_TOKEN is set, use Slack, otherwise use mail
        return config("services.slack.notifications.bot_user_oauth_token")
            ? ["slack"]
            : ["mail"];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = new MailMessage();
        $mailMessage
            ->subject("Todo Task Reminder")
            ->greeting("Hi {$notifiable->name}!")
            ->line($this->message);

        // Add list of tasks if there are any
        if ($this->todosCount > 0) {
            $pendingTasks = $notifiable
                ->todos()
                ->where("status", "pending")
                ->whereDate("due_date", today())
                ->get();

            $mailMessage->line("残っているタスク:");

            foreach ($pendingTasks as $task) {
                $mailMessage->line("- {$task->title}");
            }
        }

        return $mailMessage
            ->action("View Tasks", url("/todos?view=today"))
            ->line("Thank you for using our application!");
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $slackMessage = (new SlackMessage())
            ->from("TodoList Bot", ":clipboard:")
            ->to(config("services.slack.notifications.channel"))
            ->content($this->message);

        // Add list of tasks if there are any
        if ($this->todosCount > 0) {
            $pendingTasks = $notifiable
                ->todos()
                ->where("status", "pending")
                ->whereDate("due_date", today())
                ->get();

            if ($pendingTasks->count() > 0) {
                $taskFields = [];

                // Create fields for each task
                foreach ($pendingTasks as $index => $task) {
                    // Limit to 10 tasks to avoid exceeding Slack message limits
                    if ($index < 10) {
                        $taskFields[] = $task->title;
                    }
                }

                if (count($taskFields) > 0) {
                    $slackMessage->attachment(function ($attachment) use (
                        $taskFields,
                        $pendingTasks
                    ) {
                        $attachment
                            ->title(
                                "残っているタスク (" .
                                    $pendingTasks->count() .
                                    ")"
                            )
                            ->color("warning");

                        // Add each task as a field
                        foreach ($taskFields as $index => $taskTitle) {
                            $attachment->field(function ($field) use (
                                $index,
                                $taskTitle
                            ) {
                                $field
                                    ->title("Task " . ($index + 1))
                                    ->content($taskTitle)
                                    ->long(false);
                            });
                        }

                        // If there are more tasks than we're showing
                        if ($pendingTasks->count() > count($taskFields)) {
                            $attachment->field(function ($field) use (
                                $pendingTasks,
                                $taskFields
                            ) {
                                $field
                                    ->title("And more...")
                                    ->content(
                                        "Plus " .
                                            ($pendingTasks->count() -
                                                count($taskFields)) .
                                            " more tasks"
                                    )
                                    ->long(false);
                            });
                        }
                    });
                }
            }
        }

        // Add action button for the task list
        $slackMessage->attachment(function ($attachment) {
            $attachment->action("View Tasks", url("/todos?view=today"));
        });

        return $slackMessage;
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
