<?php
// app/Http/Controllers/Api/TaskExtractionController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskExtractionController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Extract tasks from text
     */
    public function extractTasks(Request $request)
    {
        try {
            $request->validate([
                "text" => "required|string|max:1000",
            ]);

            $text = $request->input("text");
            $tasks = $this->openAIService->extractTasksFromText($text);

            return response()->json([
                "success" => true,
                "tasks" => $tasks,
            ]);
        } catch (\Exception $e) {
            Log::error("Task extraction error: " . $e->getMessage());

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "タスク抽出に失敗しました: " . $e->getMessage(),
                ],
                500
            );
        }
    }
}
