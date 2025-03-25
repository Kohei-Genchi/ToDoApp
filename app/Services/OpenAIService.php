<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    /**
     * Transcribe audio using OpenAI Whisper API
     *
     * @param string $filePath Path to the audio file
     * @param string $language Language code (default: 'ja')
     * @return array|null Transcription result or null on error
     */
    public function transcribeAudio(
        string $filePath,
        string $language = "ja"
    ): ?array {
        try {
            $response = Http::withHeaders([
                "Authorization" =>
                    "Bearer " . config("services.openai.api_key"),
            ])
                ->attach("file", file_get_contents($filePath), "audio.webm")
                ->post("https://api.openai.com/v1/audio/transcriptions", [
                    "model" => "whisper-1",
                    "language" => $language,
                ]);

            if (!$response->successful()) {
                Log::error("Whisper API error: " . $response->body());
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Transcription error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract tasks from transcription text using ChatGPT
     *
     * @param string $transcription The transcription text
     * @return array Array of extracted tasks
     */
    public function extractTasksFromText(string $transcription): array
    {
        if (empty($transcription)) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                "Authorization" =>
                    "Bearer " . config("services.openai.api_key"),
                "Content-Type" => "application/json",
            ])->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-3.5-turbo",
                "messages" => [
                    [
                        "role" => "system",
                        "content" =>
                            "あなたはタスク管理アシスタントです。ユーザーの発言からタスクを抽出し、個別のタスクとして返してください。タスクは簡潔にして、各タスクを1行ずつ、「<task>タスク内容</task>」のフォーマットでリストしてください。発言内容をそのまま返すのではなく、実行すべきタスクだけを抽出してください。",
                    ],
                    [
                        "role" => "user",
                        "content" => $transcription,
                    ],
                ],
                "temperature" => 0.7,
            ]);

            if (!$response->successful()) {
                Log::error("ChatGPT API error: " . $response->body());
                return [];
            }

            $content =
                $response->json()["choices"][0]["message"]["content"] ?? "";

            // Extract tasks using regex
            preg_match_all("/<task>(.*?)<\/task>/", $content, $matches);
            return $matches[1] ?? [];
        } catch (\Exception $e) {
            Log::error("Task extraction error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Process audio file to extract transcription and tasks
     *
     * @param string $filePath Path to the audio file
     * @return array Containing 'text' (transcription) and 'tasks' (extracted tasks)
     */
    public function processAudioFile(string $filePath): array
    {
        // Step 1: Transcribe audio
        $whisperResult = $this->transcribeAudio($filePath);

        if (!$whisperResult) {
            return ["text" => "", "tasks" => []];
        }

        $transcription = $whisperResult["text"] ?? "";

        if (empty($transcription)) {
            return ["text" => "", "tasks" => []];
        }

        // Step 2: Extract tasks from transcription
        $tasks = $this->extractTasksFromText($transcription);

        return [
            "text" => $transcription,
            "tasks" => $tasks,
        ];
    }
}
