<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TaskReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendMorningReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "reminders:morning";
    protected $description = "Send morning reminders to users about their tasks";

    /**
     * The console command description.
     *
     * @var string
     */

    // In app/Console/Commands/SendMorningReminders.php
    public function handle()
    {
        try {
            $users = User::all();
            $count = 0;
            $currentHour = now()->format("H");
            $currentMinute = now()->format("i");

            foreach ($users as $user) {
                // Skip users who don't have their reminder time set to now
                if ($user->morning_reminder_time) {
                    $reminderHour = $user->morning_reminder_time->format("H");
                    $reminderMinute = $user->morning_reminder_time->format("i");

                    if (
                        $reminderHour !== $currentHour ||
                        $reminderMinute !== $currentMinute
                    ) {
                        continue; // Skip if not time for this user's reminder
                    }
                } else {
                    // Default for users without a setting (8:00 AM)
                    if ($currentHour !== "08" || $currentMinute !== "00") {
                        continue;
                    }
                }

                $pendingTasks = $user
                    ->todos()
                    ->where("status", "pending")
                    ->whereDate("due_date", today())
                    ->get();

                if ($user->email && $pendingTasks->count() > 0) {
                    try {
                        $user->notify(
                            new TaskReminder(
                                "今日のタスクのリマインダーです",
                                $pendingTasks->count()
                            )
                        );
                        $count++;
                    } catch (\Exception $e) {
                        $this->error("通知送信エラー: " . $e->getMessage());
                    }
                }
            }

            $this->info("通知送信完了: {$count}ユーザー");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("エラー発生: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
