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
                    if ($user->morning_reminder_time) {
                        // Convert to Carbon instance properly if it's not already
                        $reminderTime = $user->morning_reminder_time;
                        $reminderHour = (int)$reminderTime->format("H");
                        $reminderMinute = (int)$reminderTime->format("i");
                        $reminderMinutesSinceMidnight = $reminderHour * 60 + $reminderMinute;

                        $this->info("ユーザー " . $user->id . " のリマインダー時間: " . $reminderTime->format('H:i'));
                        $this->info("リマインダー時間 (分): " . $reminderMinutesSinceMidnight . ", 現在時刻 (分): " . $currentMinutesSinceMidnight);

                        // Allow a window of only 1 minute to match the exact time
                        $windowEnd = $reminderMinutesSinceMidnight + 1;

                        if ($currentMinutesSinceMidnight < $reminderMinutesSinceMidnight || $currentMinutesSinceMidnight > $windowEnd) {
                            $skipDueToTime = true;
                            $this->info("時間範囲外: " . $reminderMinutesSinceMidnight . "分から" . $windowEnd . "分の間ではありません");
                        }
                    } else {
                        // Default for users without a setting (8:00 AM)
                        $this->info("ユーザー " . $user->id . " はリマインダー時間未設定 (デフォルト: 08:00)");

                        // Default time is 8:00 AM (480 minutes since midnight)
                        $defaultReminderTime = 8 * 60; // 8 hours * 60 minutes
                        $windowEnd = $defaultReminderTime + 1;

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

                $pendingTasks = $user
                    ->todos()
                    ->where("status", "pending")
                    ->whereDate("due_date", today())
                    ->get();

                $this->info("ユーザー " . $user->id . " の保留中タスク数: " . $pendingTasks->count());

                // Check if a morning notification was sent to this user recently (within the last 30 minutes)
                $cacheKey = 'morning_reminder_sent_to_user_' . $user->id;
                $lastSentTime = Cache::get($cacheKey);

                if ($lastSentTime) {
                    $lastSentMinutes = now()->diffInMinutes($lastSentTime);
                    $this->info("ユーザー " . $user->id . " は " . $lastSentMinutes . " 分前に通知を受け取りました");

                    if ($lastSentMinutes < 30) {
                        $this->info("ユーザー " . $user->id . " は30分以内に通知を受け取ったためスキップします");
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
                        $this->info("ユーザー " . $user->id . " に通知を送信しました");
                    } catch (\Exception $e) {
                        $this->error("通知送信エラー (ユーザー " . $user->id . "): " . $e->getMessage());
                    }
                } else {
                    if (!$user->email) {
                        $this->info("ユーザー " . $user->id . " はメールアドレスがないためスキップ");
                    }
                    if ($pendingTasks->count() <= 0) {
                        $this->info("ユーザー " . $user->id . " は保留中タスクがないためスキップ");
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
