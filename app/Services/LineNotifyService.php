<?php
// app/Services/LineNotifyService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineNotifyService
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
     * Send a message via Line Notify.
     *
     * @param string $token The Line Notify token
     * @param string $message The message to send
     * @return bool Whether the message was sent successfully
     */
    public function send(string $token, string $message): bool
    {
        $this->lastError = null;

        try {
            Log::info("Attempting to send Line Notify message", [
                "token_length" => strlen($token),
                "token_prefix" => substr($token, 0, 4) . "...",
                "message_length" => strlen($message),
                "message_preview" =>
                    substr($message, 0, 50) .
                    (strlen($message) > 50 ? "..." : ""),
            ]);

            $response = Http::asForm()
                ->withHeaders([
                    "Authorization" => "Bearer {$token}",
                ])
                ->post("https://notify-api.line.me/api/notify", [
                    "message" => $message,
                ]);

            $statusCode = $response->status();
            $responseBody = $response->body();

            Log::info("Line Notify API response", [
                "status" => $statusCode,
                "body" => $responseBody,
            ]);

            if (!$response->successful()) {
                $this->lastError = "HTTP Error: Status $statusCode - $responseBody";
                Log::error("Line Notify Error", [
                    "status" => $statusCode,
                    "body" => $responseBody,
                ]);
                return false;
            }

            Log::info("Line Notify message sent successfully");
            return true;
        } catch (\Exception $e) {
            $this->lastError = "Exception: " . $e->getMessage();
            Log::error("Line Notify Exception", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
