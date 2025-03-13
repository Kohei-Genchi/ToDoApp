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

    /**
     * Execute the console command.
     */
    // In app/Console/Commands/SendMorningReminders.php

    // app/Console/Commands/SendMorningReminders.php の修正例

    public function handle()
    {
        try {
            $users = User::all();
            $count = 0;

            foreach ($users as $user) {
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
