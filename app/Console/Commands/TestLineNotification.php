<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TaskReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestLineNotification extends Command
{
    protected $signature = "test:line-notification {user_id? : The ID of the user to send the test notification to} {--debug : Show detailed debug info}";

    protected $description = "Send a test notification to Line Notify to verify the integration";

    public function handle()
    {
        try {
            $userId = $this->argument("user_id");
            $debug = $this->option("debug");

            if ($userId) {
                $user = User::findOrFail($userId);
            } else {
                // Get the first user with a Line Notify token
                $user = User::whereNotNull("line_notify_token")->first();

                if (!$user) {
                    $this->error(
                        "No users found with Line Notify tokens. Please provide a specific user ID."
                    );
                    return Command::FAILURE;
                }
            }

            $this->info("Sending test notification to: " . $user->name);
            $this->info("User ID: " . $user->id);
            $this->info(
                "User Line Notify token: " .
                    (empty($user->line_notify_token)
                        ? "Not set"
                        : "Set (length: " .
                            strlen($user->line_notify_token) .
                            ")")
            );

            if ($debug) {
                $this->info("Token: " . $user->line_notify_token);
            }

            // Create test pending tasks if needed for a more realistic test
            $pendingCount = $user
                ->todos()
                ->where("status", "pending")
                ->whereDate("due_date", today())
                ->count();

            $message =
                "This is a test notification from the TodoList app via Line Notify. ";
            if ($pendingCount > 0) {
                $message .= "You have {$pendingCount} pending tasks for today.";
            } else {
                $message .= "You have no pending tasks for today.";
            }

            $this->info("Message to send: " . $message);

            // Create the notification
            $notification = new TaskReminder($message, $pendingCount);

            // Use the direct send method instead of the notification system
            [$result, $error] = $notification->sendToLine($user);

            if ($result) {
                $this->info("Line Notify test message sent successfully!");
                return Command::SUCCESS;
            } else {
                $this->error("Failed to send Line Notify message");
                if ($error) {
                    $this->error("Error details: " . $error);
                }

                // Additional network debug
                $this->info("Testing network connectivity to Line API...");
                try {
                    $testResponse = \Illuminate\Support\Facades\Http::get(
                        "https://notify-api.line.me/"
                    );
                    $this->info(
                        "Connection test status: " . $testResponse->status()
                    );
                } catch (\Exception $e) {
                    $this->error("Connection test failed: " . $e->getMessage());
                }

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error(
                "Error sending test notification: " . $e->getMessage()
            );
            Log::error("Test Line notification error: " . $e->getMessage(), [
                "exception" => $e,
            ]);
            return Command::FAILURE;
        }
    }
}
