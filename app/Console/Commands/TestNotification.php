<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TaskReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestNotification extends Command
{
    protected $signature = "test:notification
                           {channel=both : Channel to test (line, slack, or both)}
                           {user_id? : The ID of the user to send the test notification to}
                           {--debug : Show detailed debug info}";

    protected $description = "Send a test notification to verify integrations";

    public function handle()
    {
        try {
            $channels =
                $this->argument("channel") === "both"
                    ? ["line", "slack"]
                    : [$this->argument("channel")];

            $userId = $this->argument("user_id");
            $debug = $this->option("debug");

            foreach ($channels as $channel) {
                if (!in_array($channel, ["line", "slack"])) {
                    $this->error(
                        "Invalid channel: {$channel}. Must be 'line', 'slack', or 'both'."
                    );
                    continue;
                }

                $this->info("Testing {$channel} notification...");

                // Find appropriate user based on channel and user_id
                $user = $this->findUser($channel, $userId);

                if (!$user) {
                    $this->error(
                        "No suitable user found for {$channel} notification testing."
                    );
                    continue;
                }

                $this->testNotification($channel, $user, $debug);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error(
                "Error sending test notification: " . $e->getMessage()
            );
            Log::error("Test notification error: " . $e->getMessage(), [
                "exception" => $e,
            ]);
            return Command::FAILURE;
        }
    }

    protected function findUser(string $channel, ?string $userId)
    {
        if ($userId) {
            return User::findOrFail($userId);
        }

        // Find a user with the appropriate channel configured
        if ($channel === "line") {
            $user = User::whereNotNull("line_notify_token")->first();
            if (!$user) {
                $this->error(
                    "No users found with Line Notify tokens. Please provide a specific user ID."
                );
                return null;
            }
            return $user;
        } else {
            // slack
            $user = User::whereNotNull("slack_webhook_url")->first();
            if (!$user) {
                $this->error(
                    "No users found with Slack webhook URLs. Please provide a specific user ID."
                );
                return null;
            }
            return $user;
        }
    }

    protected function testNotification(
        string $channel,
        User $user,
        bool $debug
    ) {
        $this->info("Sending test notification to: " . $user->name);
        $this->info("User ID: " . $user->id);

        if ($channel === "line") {
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
        } else {
            // slack
            $this->info(
                "User Slack webhook URL: " .
                    (empty($user->slack_webhook_url)
                        ? "Not set"
                        : "Set (length: " .
                            strlen($user->slack_webhook_url) .
                            ")")
            );

            if ($debug) {
                $this->info("Webhook URL: " . $user->slack_webhook_url);
            }
        }

        // Get pending tasks count for a more realistic test
        $pendingCount = $user
            ->todos()
            ->where("status", "pending")
            ->whereDate("due_date", today())
            ->count();

        $message =
            "This is a test notification from the TodoList app via " .
            ucfirst($channel) .
            ". ";

        if ($pendingCount > 0) {
            $message .= "You have {$pendingCount} pending tasks for today.";
        } else {
            $message .= "You have no pending tasks for today.";
        }

        $this->info("Message to send: " . $message);

        // Create and send notification
        $notification = new TaskReminder($message, $pendingCount);

        if ($channel === "line") {
            // Use the direct send method for Line
            [$result, $error] = $notification->sendToLine($user);

            if ($result) {
                $this->info("Line Notify test message sent successfully!");
            } else {
                $this->error("Failed to send Line Notify message");
                if ($error) {
                    $this->error("Error details: " . $error);
                }

                // Additional network debug
                if ($debug) {
                    $this->info("Testing network connectivity to Line API...");
                    try {
                        $testResponse = \Illuminate\Support\Facades\Http::get(
                            "https://notify-api.line.me/"
                        );
                        $this->info(
                            "Connection test status: " . $testResponse->status()
                        );
                    } catch (\Exception $e) {
                        $this->error(
                            "Connection test failed: " . $e->getMessage()
                        );
                    }
                }
            }
        } else {
            // slack
            // Use the direct send method for Slack
            [$result, $error] = $notification->sendToSlack($user);

            if ($result) {
                $this->info("Slack test message sent successfully!");
            } else {
                $this->error("Failed to send Slack message");
                if ($error) {
                    $this->error("Error details: " . $error);
                }

                // Additional debug
                if ($debug) {
                    $this->info("Slack webhook URL: " . substr($user->slack_webhook_url, 0, 30) . "...");
                    $this->info("Testing Slack webhook connectivity...");
                    try {
                        $testResponse = \Illuminate\Support\Facades\Http::get(
                            "https://hooks.slack.com/"
                        );
                        $this->info(
                            "Connection test status: " . $testResponse->status()
                        );
                    } catch (\Exception $e) {
                        $this->error(
                            "Connection test failed: " . $e->getMessage()
                        );
                    }
                }
            }
        }
    }
}
