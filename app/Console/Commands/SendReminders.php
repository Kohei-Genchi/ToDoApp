<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TaskReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendReminders extends Command
{
    protected $signature = "reminders:send {type=both : Reminder type (morning, evening, or both)} {--force : Force send reminders regardless of time}";
    protected $description = "Send reminders to users about their tasks";

    // Default reminder times
    protected const MORNING_DEFAULT_TIME = 8 * 60; // 8:00 AM
    protected const EVENING_DEFAULT_TIME = 18 * 60; // 6:00 PM

    public function handle()
    {
        try {
            $types =
                $this->argument("type") === "both"
                    ? ["morning", "evening"]
                    : [$this->argument("type")];

            foreach ($types as $type) {
                if (!in_array($type, ["morning", "evening"])) {
                    $this->error(
                        "Invalid reminder type: {$type}. Must be 'morning', 'evening', or 'both'."
                    );
                    continue;
                }

                $this->info("Sending {$type} reminders...");
                $this->sendReminders($type);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error("Error sending reminders: " . $e->getMessage());
            $this->error("Error occurred: " . $e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function sendReminders(string $type)
    {
        $users = User::all();
        $count = 0;
        $now = now();
        $currentHour = (int) $now->format("H");
        $currentMinute = (int) $now->format("i");
        $currentMinutesSinceMidnight = $currentHour * 60 + $currentMinute;

        $this->info("Current time: {$currentHour}:{$currentMinute}");
        $this->info("Total users: {$users->count()}");

        // Check if --force flag is provided to bypass time checks
        $forceRun = $this->option("force") ?? false;

        // Set default time and cache key based on type
        $defaultReminderTime =
            $type === "morning"
                ? self::MORNING_DEFAULT_TIME
                : self::EVENING_DEFAULT_TIME;
        $reminderTimeField = "{$type}_reminder_time";
        $cacheKeyPrefix = "{$type}_reminder_sent_to_user_";

        foreach ($users as $user) {
            $skipDueToTime = false;

            // Time-based filtering (skip if --force is not provided)
            if (!$forceRun) {
                if ($user->$reminderTimeField) {
                    $reminderTime = $user->$reminderTimeField;
                    $reminderHour = (int) $reminderTime->format("H");
                    $reminderMinute = (int) $reminderTime->format("i");
                    $reminderMinutesSinceMidnight =
                        $reminderHour * 60 + $reminderMinute;

                    // Only send at the exact minute
                    if (
                        $currentMinutesSinceMidnight !=
                        $reminderMinutesSinceMidnight
                    ) {
                        $skipDueToTime = true;
                    }
                } else {
                    if ($currentMinutesSinceMidnight != $defaultReminderTime) {
                        $skipDueToTime = true;
                    }
                }
            }

            if ($skipDueToTime) {
                continue;
            }

            // Prepare task counts based on reminder type
            $completedCount = 0;
            $pendingTasks = collect();
            $pendingCount = 0;

            if ($type === "morning") {
                $pendingTasks = $user
                    ->todos()
                    ->where("status", "pending")
                    ->whereDate("due_date", today())
                    ->get();
                $pendingCount = $pendingTasks->count();
            } else {
                // evening
                $completedCount = $user
                    ->todos()
                    ->where("status", "completed")
                    ->whereDate("due_date", today())
                    ->count();

                $pendingCount = $user
                    ->todos()
                    ->where("status", "pending")
                    ->whereDate("due_date", today())
                    ->count();
            }

            // Check if a notification was sent recently (within the last 30 minutes)
            $cacheKey = $cacheKeyPrefix . $user->id;
            $lastSentTime = Cache::get($cacheKey);

            if ($lastSentTime) {
                $lastSentMinutes = now()->diffInMinutes($lastSentTime);
                if ($lastSentMinutes < 30) {
                    continue;
                }
            }

            // Only send if user has an email and has relevant tasks
            $shouldSend = false;
            if ($type === "morning") {
                $shouldSend = $user->email && $pendingTasks->count() > 0;
            } else {
                // evening
                $shouldSend =
                    ($user->email || $user->line_notify_token) &&
                    ($pendingCount > 0 || $completedCount > 0);
            }

            if ($shouldSend) {
                try {
                    // Prepare message based on type
                    $message = $this->getMessageForType(
                        $type,
                        $pendingCount,
                        $completedCount
                    );

                    $notification = new TaskReminder($message, $pendingCount);

                    $notificationSent = false;

                    // Send Line notification if configured
                    if (!empty($user->line_notify_token)) {
                        $lineResult = $notification->sendToLine($user);
                        if ($lineResult[0]) {
                            $notificationSent = true;
                            $this->info(
                                "Sent Line notification to user {$user->id}"
                            );
                        } else {
                            $this->error(
                                "Error sending Line notification to user {$user->id}"
                            );
                        }
                    }

                    // Send Slack notification if configured
                    if (!empty($user->slack_webhook_url)) {
                        $slackResult = $notification->sendToSlack($user);
                        if ($slackResult[0]) {
                            $notificationSent = true;
                            $this->info("Sent Slack notification to user {$user->id}");
                        } else {
                            $this->error("Error sending Slack notification to user {$user->id}");
                        }
                    }

                    // Use the regular notification system if no direct notifications were sent
                    if (!$notificationSent && $user->email) {
                        $user->notify($notification);
                        $notificationSent = true;
                        $this->info("Sent email notification to user {$user->id}");
                    }

                    // Increment count if any notification was sent
                    if ($notificationSent) {
                        $count++;
                    }

                    // Store the time this notification was sent
                    Cache::put($cacheKey, now(), 60); // Store for 60 minutes
                } catch (\Exception $e) {
                    $this->error(
                        "Error sending notification to user {$user->id}: {$e->getMessage()}"
                    );
                }
            }
        }

        $this->info(
            "Completed sending {$type} notifications to {$count} users"
        );
    }

    /**
     * Get the appropriate message based on reminder type and task counts
     */
    protected function getMessageForType(
        string $type,
        int $pendingCount,
        int $completedCount
    ): string {
        if ($type === "morning") {
            return "今日のタスクのリマインダーです";
        } else {
            // evening
            $message = "こんばんは! ";
            if ($pendingCount > 0) {
                $message .= "明日のタスク作成は済んでいますか？ ";
            }
            if ($completedCount > 0) {
                $message .=
                    "Great job completing " .
                    $completedCount .
                    " tasks today! ";
            }
            return $message;
        }
    }
}
