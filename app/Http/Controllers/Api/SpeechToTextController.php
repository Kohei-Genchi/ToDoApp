<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class SpeechToTextController extends Controller
{
    /**
     * Process speech and convert to tasks
     */
    public function processSpeech(Request $request)
    {
        \Log::info("Speech processing request received", [
            "authenticated" => auth()->check(),
            "user_id" => auth()->id(),
            "has_audio_file" => $request->hasFile("audio"),
        ]);
        try {
            // Validate request
            $request->validate([
                "audio" => "required|file|mimes:webm,mp3,wav,m4a|max:20480", // Max 20MB
            ]);

            // Check if the user has exceeded their usage limit
            if (!$this->checkUserApiUsage()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "APIの使用上限に達しました。明日また試してください。",
                    ],
                    429
                );
            }

            // Store the file temporarily
            $file = $request->file("audio");
            $filename =
                "speech_" .
                Str::random(20) .
                "." .
                $file->getClientOriginalExtension();
            $path = $file->storeAs("temp/audio", $filename, "local");

            // Check if this is a larger file that needs to be processed in the background
            $largeFileThreshold = 3 * 1024 * 1024; // 3MB
            if ($file->getSize() > $largeFileThreshold) {
                // For larger files, handle with queue
                $this->dispatchSpeechProcessingJob($path);

                return response()->json([
                    "success" => true,
                    "message" =>
                        "音声ファイルが大きいため、バックグラウンドで処理します。処理完了後、タスクが追加されます。",
                    "background" => true,
                ]);
            }

            // For smaller files, process immediately
            $result = $this->processAudioFile(
                Storage::disk("local")->path($path)
            );

            // Clean up the temporary file
            Storage::disk("local")->delete($path);

            // Increment user's API usage
            $this->incrementUserApiUsage();

            return response()->json([
                "success" => true,
                "text" => $result["text"] ?? null,
                "tasks" => $result["tasks"] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error("Speech processing error: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error processing speech: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Dispatch job for processing speech in background
     */
    private function dispatchSpeechProcessingJob($path)
    {
        // In a real implementation, you would use Laravel's queue system:
        // ProcessSpeechToTasks::dispatch($path, auth()->id());

        // For this example, we'll log that we would process it in the background
        Log::info("Would dispatch background job for processing speech", [
            "path" => $path,
            "user_id" => auth()->id(),
        ]);

        // Still increment usage as we're using API resources
        $this->incrementUserApiUsage();
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

    /**
     * Check if the user has exceeded their daily API usage limit
     */
    private function checkUserApiUsage()
    {
        if (!auth()->check()) {
            // ゲストは1日3回までなど、制限を設ける
            return true; // または適切な制限を設定
        }

        $user = auth()->user();
        $dailyLimit = 5; // Set daily limit to 5 requests for free tier

        // Get current usage from cache
        $key =
            "api_usage_" . ($user ? $user->id : "guest") . "_" . date("Y-m-d");
        $usage = Cache::get($key, 0);

        return $usage < $dailyLimit;
    }

    /**
     * Increment the user's API usage count
     */
    private function incrementUserApiUsage()
    {
        if (!auth()->check()) {
            // ゲスト用のキャッシュキー
            $key = "api_usage_guest_" . request()->ip() . "_" . date("Y-m-d");
        } else {
            $user = auth()->user();
            $key = "api_usage_" . $user->id . "_" . date("Y-m-d");
        }

        // Get current usage and increment
        $usage = Cache::get($key, 0);
        Cache::put($key, $usage + 1, now()->endOfDay());
    }
}
