<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotifyService
{
    /**
     * Last error message from sending attempt
     */
    protected $lastError = null;

    /**
     * Get the last error message
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Send a message via Slack Webhook.
     *
     * @param string $webhookUrl The Slack webhook URL
     * @param string $message The message to send
     * @param string $username Optional username to display
     * @param string $channel Optional channel to post to
     * @return bool Whether the message was sent successfully
     */
    public function send(
        string $webhookUrl,
        string $message,
        string $username = null,
        string $channel = null
    ): bool {
        $this->lastError = null;

        try {
            Log::info("Attempting to send Slack notification", [
                "webhook_length" => strlen($webhookUrl),
                "webhook_prefix" => substr($webhookUrl, 0, 15) . "...",
                "message_length" => strlen($message),
                "message_preview" =>
                    substr($message, 0, 50) .
                    (strlen($message) > 50 ? "..." : ""),
            ]);

            $payload = [
                "text" => $message,
            ];

            // Add optional parameters if provided
            if ($username) {
                $payload["username"] = $username;
            }

            if ($channel) {
                $payload["channel"] = $channel;
            }

            $response = Http::post($webhookUrl, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();

            Log::info("Slack API response", [
                "status" => $statusCode,
                "body" => $responseBody,
            ]);

            if (!$response->successful()) {
                $this->lastError = "HTTP Error: Status $statusCode - $responseBody";
                Log::error("Slack Notification Error", [
                    "status" => $statusCode,
                    "body" => $responseBody,
                ]);
                return false;
            }

            Log::info("Slack notification sent successfully");
            return true;
        } catch (\Exception $e) {
            $this->lastError = "Exception: " . $e->getMessage();
            Log::error("Slack Notification Exception", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
