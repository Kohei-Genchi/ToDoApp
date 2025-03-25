<?php

namespace App\Jobs;

use App\Models\Todo;
use App\Models\User;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessSpeechToTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string Path to the audio file
     */
    protected $filePath;

    /**
     * @var int|null User ID
     */
    protected $userId;

    /**
     * Create a new job instance.
     *
     * @param string $filePath Path to stored audio file
     * @param int|null $userId User ID
     */
    public function __construct($filePath, $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @param OpenAIService $openAIService
     * @return void
     */
    public function handle(OpenAIService $openAIService)
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

            // Get complete file path
            $fullPath = Storage::disk("local")->path($this->filePath);

            // Process audio file using the service
            $result = $openAIService->processAudioFile($fullPath);

            // Clean up the temporary file
            Storage::disk("local")->delete($this->filePath);

            // Create tasks based on processing results
            $this->createTasksFromResult($result);
        } catch (\Exception $e) {
            Log::error(
                "Background speech processing error: " . $e->getMessage(),
                [
                    "line" => $e->getLine(),
                    "file" => $e->getFile(),
                ]
            );
        }
    }

    /**
     * Create tasks from processing result
     *
     * @param array $result Processing result containing 'text' and 'tasks'
     * @return void
     */
    private function createTasksFromResult(array $result): void
    {
        // Create tasks from extracted tasks
        if (isset($result["tasks"]) && !empty($result["tasks"])) {
            foreach ($result["tasks"] as $taskTitle) {
                $this->createTask($taskTitle);
            }

            Log::info(
                "Created " .
                    count($result["tasks"]) .
                    " tasks from speech for user " .
                    $this->userId
            );
        }
        // Fall back to creating a single task from transcription if no tasks were extracted
        elseif (isset($result["text"]) && !empty($result["text"])) {
            $this->createTask($result["text"]);

            Log::info(
                "Created a single task from speech for user " . $this->userId
            );
        }
    }

    /**
     * Create a single task
     *
     * @param string $title Task title
     * @return void
     */
    private function createTask(string $title): void
    {
        Todo::create([
            "title" => $title,
            "user_id" => $this->userId,
            "status" => "pending",
            "location" => "INBOX",
        ]);
    }
}
