<?php

namespace App\Console\Commands;

use App\Models\Todo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TodoCleanup extends Command
{
    protected $signature = "todos:cleanup {--days=0 : Number of days to look back}";
    protected $description = "期限切れのタスクを整理する";

    public function handle()
    {
        try {
            $daysBack = (int) $this->option("days");
            $targetDate = Carbon::now()->subDays($daysBack)->format("Y-m-d");

            $this->info("Target date for cleanup: {$targetDate}");

            // Process completed tasks
            $completedCount = $this->processCompletedTasks($targetDate);
            $this->info("完了タスク {$completedCount} 件を削除しました");

            // Process pending tasks
            $pendingCount = $this->processPendingTasks($targetDate);
            $this->info("未完了タスク {$pendingCount} 件をINBOXに戻しました");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error(
                "Todoタスク整理中にエラーが発生しました: " . $e->getMessage()
            );
            $this->error("エラーが発生しました: " . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Process completed tasks (deletion)
     *
     * @param string $targetDate The target date in Y-m-d format
     * @return int Number of processed tasks
     */
    protected function processCompletedTasks(string $targetDate): int
    {
        return Todo::where("status", "completed")
            ->where("location", "TODAY")
            ->whereDate("due_date", $targetDate)
            ->delete();
    }

    /**
     * Process pending tasks (move to inbox)
     *
     * @param string $targetDate The target date in Y-m-d format
     * @return int Number of processed tasks
     */
    protected function processPendingTasks(string $targetDate): int
    {
        return Todo::where("status", "pending")
            ->where("location", "TODAY")
            ->whereDate("due_date", $targetDate)
            ->update([
                "location" => "INBOX",
                "due_date" => null,
                "due_time" => null,
            ]);
    }
}
