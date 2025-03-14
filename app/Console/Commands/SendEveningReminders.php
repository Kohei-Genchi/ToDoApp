<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TaskReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendEveningReminders extends Command
{
    protected $signature = "reminders:evening";
    protected $description = "Send evening reminders to users about their tasks";

    // In app/Console/Commands/SendEveningReminders.php
    public function handle()
    {
        try {
            $users = User::all();
            $count = 0;
            $currentHour = now()->format("H");
            $currentMinute = now()->format("i");

            foreach ($users as $user) {
                // Skip users who don't have their reminder time set to now
                if ($user->evening_reminder_time) {
                    $reminderHour = $user->evening_reminder_time->format("H");
                    $reminderMinute = $user->evening_reminder_time->format("i");

                    if (
                        $reminderHour !== $currentHour ||
                        $reminderMinute !== $currentMinute
                    ) {
                        continue; // Skip if not time for this user's reminder
                    }
                } else {
                    // Default for users without a setting (8:00 PM)
                    if ($currentHour !== "20" || $currentMinute !== "00") {
                        continue;
                    }
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

                // Only send if user has an email and has relevant tasks for today
                if (
                    $user->email &&
                    ($pendingCount > 0 || $completedCount > 0)
                ) {
                    $message = "こんばんは! ";
                    if ($pendingCount > 0) {
                        $message .= "明日のタスク作成は済んでいますか？ ";
                    }
                    if ($completedCount > 0) {
                        $message .= "Great job completing {$completedCount} tasks today! ";
                    }

                    $user->notify(new TaskReminder($message, $pendingCount));
                    $count++;
                }
            }

            $this->info("Evening reminders sent to {$count} users");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error("Error sending evening reminders: " . $e->getMessage());
            $this->error("Error sending reminders: " . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
