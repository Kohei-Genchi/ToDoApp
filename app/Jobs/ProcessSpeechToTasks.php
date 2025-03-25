<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Todo;
use App\Models\User;

class ProcessSpeechToTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Check if file exists
            if (!Storage::disk("local")->exists($this->filePath)) {
                Log::error("Speech file not found: " . $this->filePath);
                return;
            }

            // Get user - ユーザーIDがnullでもエラーにならないよう修正
            $user = null;
            if ($this->userId) {
                $user = User::find($this->userId);
            }

            // Process audio file
            $result = $this->processAudioFile(
                Storage::disk("local")->path($this->filePath)
            );

            // Clean up the temporary file
            Storage::disk("local")->delete($this->filePath);

            // Create tasks
            if (isset($result["tasks"]) && !empty($result["tasks"])) {
                foreach ($result["tasks"] as $taskTitle) {
                    Todo::create([
                        "title" => $taskTitle,
                        "user_id" => $this->userId,
                        "status" => "pending",
                        "location" => "INBOX",
                    ]);
                }

                Log::info(
                    "Created " .
                        count($result["tasks"]) .
                        " tasks from speech for user " .
                        $this->userId
                );
            } elseif (isset($result["text"]) && !empty($result["text"])) {
                // If no tasks were extracted but we have transcription, create a single task
                Todo::create([
                    "title" => $result["text"],
                    "user_id" => $this->userId,
                    "status" => "pending",
                    "location" => "INBOX",
                ]);

                Log::info(
                    "Created a single task from speech for user " .
                        $this->userId
                );
            }
        } catch (\Exception $e) {
            Log::error(
                "Background speech processing error: " . $e->getMessage()
            );
        }
    }

    /**
     * Process audio file using OpenAI Whisper and GPT
     */
    private function processAudioFile($filePath)
    {
        // Step 1: Convert speech to text using Whisper API
        $whisperResponse = Http::withHeaders([
            "Authorization" => "Bearer " . config("services.openai.api_key"),
        ])
            ->attach("file", file_get_contents($filePath), "audio.webm")
            ->post("https://api.openai.com/v1/audio/transcriptions", [
                "model" => "whisper-1",
                "language" => "ja", // Japanese language
            ]);

        if (!$whisperResponse->successful()) {
            Log::error("Whisper API error: " . $whisperResponse->body());
            throw new \Exception("Speech-to-text processing failed");
        }

        $transcription = $whisperResponse->json()["text"] ?? "";

        if (empty($transcription)) {
            return ["text" => ""];
        }

        // Step 2: Extract tasks using ChatGPT API
        $chatResponse = Http::withHeaders([
            "Authorization" => "Bearer " . config("services.openai.api_key"),
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

        if (!$chatResponse->successful()) {
            Log::error("ChatGPT API error: " . $chatResponse->body());
            return ["text" => $transcription]; // Return just the transcription if task extraction fails
        }

        $content =
            $chatResponse->json()["choices"][0]["message"]["content"] ?? "";

        // Extract tasks using regex
        preg_match_all("/<task>(.*?)<\/task>/", $content, $matches);
        $tasks = $matches[1] ?? [];

        return [
            "text" => $transcription,
            "tasks" => $tasks,
        ];
    }
}
