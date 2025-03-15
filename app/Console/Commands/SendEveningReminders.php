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

    // In app/Console/Commands/SendEveningReminders.php
    public function handle()
    {
        try {
            $users = User::all();
            $count = 0;
            $now = now();
            $currentHour = (int)$now->format("H");
            $currentMinute = (int)$now->format("i");
            $currentMinutesSinceMidnight = ($currentHour * 60) + $currentMinute;

            $this->info("現在の時刻: " . $currentHour . ":" . $currentMinute);
            $this->info("ユーザー数: " . $users->count());

            // Check if --force flag is provided to bypass time checks
            $forceRun = $this->option('force') ?? false;

            foreach ($users as $user) {
                $skipDueToTime = false;

                // Time-based filtering (skip if --force is not provided)
                if (!$forceRun) {
                    if ($user->evening_reminder_time) {
                        // Convert to Carbon instance properly if it's not already
                        $reminderTime = $user->evening_reminder_time;
                        $reminderHour = (int)$reminderTime->format("H");
                        $reminderMinute = (int)$reminderTime->format("i");
                        $reminderMinutesSinceMidnight = $reminderHour * 60 + $reminderMinute;

                        $this->info("ユーザー " . $user->id . " のリマインダー時間: " . $reminderTime->format('H:i'));
                        $this->info("リマインダー時間 (分): " . $reminderMinutesSinceMidnight . ", 現在時刻 (分): " . $currentMinutesSinceMidnight);

                        // Allow a window of 15 minutes after the scheduled time
                        $windowEnd = $reminderMinutesSinceMidnight + 15;

                        if ($currentMinutesSinceMidnight < $reminderMinutesSinceMidnight || $currentMinutesSinceMidnight > $windowEnd) {
                            $skipDueToTime = true;
                            $this->info("時間範囲外: " . $reminderMinutesSinceMidnight . "分から" . $windowEnd . "分の間ではありません");
                        }
                    } else {
                        // Default for users without a setting (18:00 PM for evening reminders)
                        $this->info("ユーザー " . $user->id . " はリマインダー時間未設定 (デフォルト: 18:00)");

                        // Default time is 18:00 (1080 minutes since midnight)
                        $defaultReminderTime = 18 * 60; // 18 hours * 60 minutes
                        $windowEnd = $defaultReminderTime + 15;

                        if ($currentMinutesSinceMidnight < $defaultReminderTime || $currentMinutesSinceMidnight > $windowEnd) {
                            $skipDueToTime = true;
                            $this->info("デフォルト時間範囲外: " . $defaultReminderTime . "分から" . $windowEnd . "分の間ではありません");
                        }
                    }
                }

                if ($skipDueToTime) {
                    $this->info("ユーザー " . $user->id . " は時間が一致しないためスキップ");
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

                $this->info("ユーザー " . $user->id . " の完了タスク数: " . $completedCount . ", 保留中タスク数: " . $pendingCount);

                // Check if a notification was sent to this user recently (within the last 30 minutes)
                $cacheKey = 'reminder_sent_to_user_' . $user->id;
                $lastSentTime = Cache::get($cacheKey);

                if ($lastSentTime) {
                    $lastSentMinutes = now()->diffInMinutes($lastSentTime);
                    $this->info("ユーザー " . $user->id . " は " . $lastSentMinutes . " 分前に通知を受け取りました");

                    if ($lastSentMinutes < 30) {
                        $this->info("ユーザー " . $user->id . " は30分以内に通知を受け取ったためスキップします");
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
                            $message .= "Great job completing " . $completedCount . " tasks today! ";
                        }

                        $user->notify(new TaskReminder($message, $pendingCount));

                        // Store the time this notification was sent
                        Cache::put($cacheKey, now(), 60); // Store for 60 minutes

                        $count++;
                        $this->info("ユーザー " . $user->id . " に通知を送信しました");
                    } catch (\Exception $e) {
                        $this->error("通知送信エラー (ユーザー " . $user->id . "): " . $e->getMessage());
                    }
                } else {
                    if (!$user->email) {
                        $this->info("ユーザー " . $user->id . " はメールアドレスがないためスキップ");
                    }
                    if ($pendingCount <= 0 && $completedCount <= 0) {
                        $this->info("ユーザー " . $user->id . " は関連タスクがないためスキップ");
                    }
                }
            }

            $this->info("通知送信完了: " . $count . "ユーザー");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error("Error sending evening reminders: " . $e->getMessage());
            $this->error("エラー発生: " . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
