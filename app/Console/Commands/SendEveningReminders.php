<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TaskReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendEveningReminders extends Command
{
    protected $signature = "reminders:evening {--force : Force send reminders regardless of time}";
    protected $description = "Send evening reminders to users about their tasks";

    public function handle()
    {
        try {
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

            foreach ($users as $user) {
                $skipDueToTime = false;

                // Time-based filtering (skip if --force is not provided)
                if (!$forceRun) {
                    if ($user->evening_reminder_time) {
                        $reminderTime = $user->evening_reminder_time;
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
                        // Default for users without a setting (18:00 PM for evening reminders)
                        $defaultReminderTime = 18 * 60; // 18 hours * 60 minutes

                        if (
                            $currentMinutesSinceMidnight != $defaultReminderTime
                        ) {
                            $skipDueToTime = true;
                        }
                    }
                }

                if ($skipDueToTime) {
                    continue;
                }

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

                // Check if an evening notification was sent to this user recently (within the last 30 minutes)
                $cacheKey = "evening_reminder_sent_to_user_" . $user->id;
                $lastSentTime = Cache::get($cacheKey);

                if ($lastSentTime) {
                    $lastSentMinutes = now()->diffInMinutes($lastSentTime);
                    if ($lastSentMinutes < 30) {
                        continue;
                    }
                }

                // Only send if user has an email and has relevant tasks for today
                if (
                    $user->email &&
                    ($pendingCount > 0 || $completedCount > 0)
                ) {
                    try {
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

                        $user->notify(
                            new TaskReminder($message, $pendingCount)
                        );

                        // Store the time this notification was sent
                        Cache::put($cacheKey, now(), 60); // Store for 60 minutes

                        $count++;
                        $this->info("Sent notification to user {$user->id}");
                    } catch (\Exception $e) {
                        $this->error(
                            "Error sending notification to user {$user->id}: {$e->getMessage()}"
                        );
                    }
                }
            }

            $this->info("Completed sending notifications to {$count} users");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error("Error sending evening reminders: " . $e->getMessage());
            $this->error("Error occurred: " . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
