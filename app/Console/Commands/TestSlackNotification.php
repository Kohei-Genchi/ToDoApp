<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TaskReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestSlackNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "test:slack-notification {user_id? : The ID of the user to send the test notification to}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Send a test notification to Slack to verify the integration";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $userId = $this->argument("user_id");

            if ($userId) {
                $user = User::findOrFail($userId);
            } else {
                // Get the first user with a Slack webhook URL
                $user = User::whereNotNull("slack_webhook_url")->first();

                if (!$user) {
                    $this->error(
                        "No users found with Slack webhook URLs. Please provide a specific user ID."
                    );
                    return Command::FAILURE;
                }
            }

            $this->info("Sending test notification to: " . $user->name);

            // Create test pending tasks if needed for a more realistic test
            $pendingCount = $user
                ->todos()
                ->where("status", "pending")
                ->whereDate("due_date", today())
                ->count();

            $message =
                "This is a test notification from the TodoList app via Slack. ";
            if ($pendingCount > 0) {
                $message .= "You have {$pendingCount} pending tasks for today.";
            } else {
                $message .= "You have no pending tasks for today.";
            }

            // Send the notification
            $user->notify(new TaskReminder($message, $pendingCount));

            $this->info("Test notification sent successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error(
                "Error sending test notification: " . $e->getMessage()
            );
            Log::error("Test Slack notification error: " . $e->getMessage(), [
                "exception" => $e,
            ]);
            return Command::FAILURE;
        }
    }
}
