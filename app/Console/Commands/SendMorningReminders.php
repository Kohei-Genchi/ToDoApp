<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TaskReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendMorningReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "reminders:morning {--force : Force send reminders regardless of time}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Send morning reminders to users about their tasks";

    /**
     * Execute the console command.
     */
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
                    if ($user->morning_reminder_time) {
                        $reminderTime = $user->morning_reminder_time;
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
                        // Default for users without a setting (8:00 AM)
                        $defaultReminderTime = 8 * 60; // 8 hours * 60 minutes

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

                $pendingTasks = $user
                    ->todos()
                    ->where("status", "pending")
                    ->whereDate("due_date", today())
                    ->get();

                // Check if a morning notification was sent recently (within the last 30 minutes)
                $cacheKey = "morning_reminder_sent_to_user_" . $user->id;
                $lastSentTime = Cache::get($cacheKey);

                if ($lastSentTime) {
                    $lastSentMinutes = now()->diffInMinutes($lastSentTime);
                    if ($lastSentMinutes < 30) {
                        continue;
                    }
                }

                if ($user->email && $pendingTasks->count() > 0) {
                    try {
                        $user->notify(
                            new TaskReminder(
                                "今日のタスクのリマインダーです",
                                $pendingTasks->count()
                            )
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
            $this->error("Error occurred: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
