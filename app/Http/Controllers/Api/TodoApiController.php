<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TodoRequest;
use App\Http\Requests\TodoUpdateRequest;
use App\Models\Todo;
use App\Models\Category;
use App\Traits\HandlesApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Clockwork\Support\Laravel\Facade as Clockwork;

class TodoApiController extends Controller
{
    use HandlesApiResponses;

    /**
     * Get all todos based on view and date.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            if (!Auth::check()) {
                return response()->json([]);
            }

            // クエリパラメータを取得
            // $request->view に値がある場合、その値を $view に代入。null または undefined の場合、デフォルト値 "today"を。
            $view = $request->view ?? "today";
            $date = $request->date ? now()->parse($request->date) : now();

            // クエリの構築
            $query = $this->buildBaseTaskQuery($request);
            $this->applyViewFilters($query, $view, $date, $request);

            // Fetch tasks
            $todos = $query->orderBy("due_time", "asc")->get();

            return response()->json($todos);
        } catch (\Exception $e) {
            return response()->json(
                ["error" => "Error retrieving tasks: " . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Store a new todo.
     *
     * @param TodoRequest $request
     * @return JsonResponse
     */
    public function store(TodoRequest $request): JsonResponse
    {
        try {
            if (!Auth::check()) {
                return response()->json(
                    ["error" => "Authentication required"],
                    401
                );
            }

            $taskData = $this->prepareTaskData($request->validated());

            $todo = Todo::create($taskData);

            // 繰り返しタスクの処理
            if ($this->shouldCreateRecurringTasks($taskData)) {
                $this->createRecurringTasks($taskData);
            }

            return response()->json(
                [
                    "message" => "Task created successfully",
                    "todo" => $todo->load("category"),
                ],
                201
            );
        } catch (\Exception $e) {
            return response()->json(
                ["error" => "Error creating task: " . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get a specific todo.
     *
     * @param Todo $todo
     * @return JsonResponse
     */
    public function show(Todo $todo): JsonResponse
    {
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                return response()->json([], 401);
            }

            // Check if the user is authorized to view this task
            if (Gate::denies("view", $todo)) {
                return response()->json(
                    [
                        "error" =>
                            "Unauthorized access. You don't have permission to view this task.",
                    ],
                    403
                );
            }

            $todo->load("category");

            return response()->json($todo);
        } catch (\Exception $e) {
            return response()->json(
                ["error" => "Error retrieving task: " . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update a todo.
     *
     * @param TodoUpdateRequest $request
     * @param Todo $todo
     * @return JsonResponse
     */
    public function update(Request $request, Todo $todo): JsonResponse
    {
        Log::info("タスク更新リクエスト:", $request->all());
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                return response()->json(
                    ["error" => "Authentication required"],
                    401
                );
            }

            // Check if the user is authorized to update this task
            if (Gate::denies("update", $todo)) {
                return response()->json(
                    [
                        "error" =>
                            "Unauthorized access. You don't have permission to edit this task.",
                    ],
                    403
                );
            }

            // Prepare the data for update
            $updateData = [];

            // Only include fields that are present in the request
            if ($request->has("title")) {
                $updateData["title"] = $request->input("title");
            }

            if ($request->has("status")) {
                $updateData["status"] = $request->input("status");
            }

            if ($request->has("category_id")) {
                $updateData["category_id"] = $request->input("category_id");
            }

            // Handle date and time carefully to prevent timezone issues
            if ($request->has("due_date")) {
                // Use the date as provided without timezone adjustments
                $updateData["due_date"] = $request->input("due_date");
            }

            if ($request->has("due_time")) {
                $updateData["due_time"] = $request->input("due_time");
            }

            // Update the task with only the provided fields
            $todo->update($updateData);
            $todo->refresh();

            // Update location if due date changed
            if ($request->has("due_date")) {
                $this->handleTaskLocation($todo);
                $todo->save();
            }

            $todo->load("category");

            return response()->json([
                "message" => "Task updated successfully",
                "todo" => $todo,
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating task: " . $e->getMessage(), [
                "exception" => $e,
                "request" => $request->all(),
                "task_id" => $todo->id,
            ]);

            return response()->json(
                ["error" => "Error updating task: " . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Toggle todo status between completed and pending.
     *
     * @param Todo $todo
     * @return JsonResponse
     */
    public function toggle(Todo $todo): JsonResponse
    {
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                return response()->json(
                    ["error" => "Authentication required"],
                    401
                );
            }

            // Check if the user is authorized to toggle this task
            if (Gate::denies("update", $todo)) {
                return response()->json(
                    [
                        "error" =>
                            "Unauthorized access. You don't have permission to update this task.",
                    ],
                    403
                );
            }

            // Toggle status
            $todo->status =
                $todo->status === Todo::STATUS_COMPLETED
                    ? Todo::STATUS_PENDING
                    : Todo::STATUS_COMPLETED;
            $todo->save();

            return response()->json([
                "message" => "Task status updated successfully",
                "todo" => $todo->fresh()->load("category"),
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ["error" => "Error toggling task status: " . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Delete a todo.
     *
     * @param Todo $todo
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Todo $todo, Request $request): JsonResponse
    {
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                return response()->json(
                    ["error" => "Authentication required"],
                    401
                );
            }

            // Check if the user is authorized to delete this task
            if (Gate::denies("delete", $todo)) {
                return response()->json(
                    [
                        "error" =>
                            "Unauthorized access. You don't have permission to delete this task.",
                    ],
                    403
                );
            }

            // Delete recurring tasks if requested
            if ($request->has("delete_recurring")) {
                return $this->deleteRecurringTasks($todo, $request);
            }

            // Delete single task
            $todo->delete();

            return response()->json([
                "message" => "Task deleted successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ["error" => "Error deleting task: " . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Delete recurring tasks.
     *
     * @param Todo $todo
     * @param Request $request
     * @return JsonResponse
     */
    private function deleteRecurringTasks(
        Todo $todo,
        Request $request
    ): JsonResponse {
        // Find tasks with the same title and creation time
        $relatedTasks = Todo::where("user_id", $todo->user_id)
            ->where("title", $todo->title)
            ->where("created_at", $todo->created_at)
            ->get();

        // Delete all found tasks
        foreach ($relatedTasks as $task) {
            $task->delete();
        }

        return response()->json([
            "message" => "Recurring tasks deleted successfully",
        ]);
    }

    /**
     * Build base task query.
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildBaseTaskQuery($request = null)
    {
        // リクエストと現在のユーザーが存在するか確認
        if (!$request || !Auth::check()) {
            return Todo::where("user_id", Auth::id())->with("category");
        }

        // 特定のユーザーIDが指定されている場合はそのユーザーのタスクを取得
        if ($request->has("user_id")) {
            $requestedUserId = $request->user_id;
            $currentUserId = Auth::id();

            // ユーザーIDが数値であることを確認
            $requestedUserId = is_numeric($requestedUserId)
                ? (int) $requestedUserId
                : null;

            // 現在のユーザー自身のタスクを要求している場合
            if ($requestedUserId === $currentUserId) {
                return Todo::where("user_id", $currentUserId)->with([
                    "category",
                    "user",
                ]);
            }

            // グローバル共有の権限チェック
            $isSharedGlobally = Auth::user()
                ->globallySharedBy()
                ->where("user_id", $requestedUserId)
                ->exists();

            // 現在のユーザーがグローバル共有しているユーザーのタスクかどうかチェック
            $isUserSharingGlobally = Auth::user()
                ->globallySharedWith()
                ->where("shared_with_user_id", $requestedUserId)
                ->exists();

            if ($isSharedGlobally || $isUserSharingGlobally) {
                // 許可された場合、指定されたユーザーのタスクを返す
                return Todo::where("user_id", $requestedUserId)->with([
                    "category",
                    "user",
                ]);
            }

            // 個別共有タスクのチェック - 指定ユーザーが共有したタスクのみ取得
            $sharedTaskIds = Auth::user()
                ->sharedTasks()
                ->where("user_id", $requestedUserId)
                ->pluck("id");

            if ($sharedTaskIds->isNotEmpty()) {
                // 個別に共有されたタスクのみを返す
                return Todo::where("user_id", $requestedUserId)
                    ->whereIn("id", $sharedTaskIds)
                    ->with(["category", "user"]);
            }
        }

        // デフォルトは現在のユーザーのタスク
        return Todo::where("user_id", Auth::id())->with("category");
    }

    /**
     * Apply view-specific filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $view
     * @param \Carbon\Carbon $date
     * @param Request $request
     * @return void
     */
    private function applyViewFilters($query, $view, $date, $request): void
    {
        switch ($view) {
            case "today":
                $query->whereDate("due_date", $date->format("Y-m-d"));
                break;
            case "scheduled":
                $query
                    ->whereNotNull("due_date")
                    ->whereDate("due_date", ">", now()->format("Y-m-d"))
                    ->where("status", Todo::STATUS_PENDING);
                break;
            case "inbox":
                $query
                    ->whereNull("due_date")
                    ->where("status", Todo::STATUS_PENDING);
                break;
            case "calendar":
                $query->whereBetween("due_date", [
                    $request->start_date ??
                    $date->copy()->startOfMonth()->format("Y-m-d"),
                    $request->end_date ??
                    $date->copy()->endOfMonth()->format("Y-m-d"),
                ]);
                break;
            case "date":
                // For specific date view
                $query->whereDate("due_date", $date->format("Y-m-d"));
                break;
            default:
                // If view is not specified, default to today
                $query->whereDate("due_date", $date->format("Y-m-d"));
                break;
        }
    }

    /**
     * Prepare task data for storage.
     *
     * @param array $validated
     * @return array
     */
    private function prepareTaskData(array $validated): array
    {
        $taskData = $validated;
        $taskData["user_id"] = Auth::id();

        // Set task location
        if (isset($taskData["due_date"]) && $taskData["due_date"]) {
            // Convert date to Carbon instance for comparison
            $dueDate = now()->parse($taskData["due_date"])->startOfDay();
            $today = now()->startOfDay();

            $taskData["location"] = $dueDate->equalTo($today)
                ? Todo::LOCATION_TODAY
                : Todo::LOCATION_SCHEDULED;
        } else {
            $taskData["location"] = Todo::LOCATION_INBOX;
            $taskData["due_date"] = null;
            $taskData["due_time"] = null;
        }

        return $taskData;
    }

    /**
     * Check if recurring tasks should be created.
     *
     * @param array $taskData
     * @return bool
     */
    private function shouldCreateRecurringTasks(array $taskData): bool
    {
        return !empty($taskData["recurrence_type"]) &&
            $taskData["recurrence_type"] !== "none";
    }

    /**
     * Create recurring tasks.
     *
     * @param array $taskData
     * @return void
     */
    private function createRecurringTasks(array $taskData): void
    {
        // Skip if due date is missing
        if (empty($taskData["due_date"])) {
            return;
        }

        // Prepare dates
        $startDate = now()->parse($taskData["due_date"]);
        $endDate = !empty($taskData["recurrence_end_date"])
            ? now()->parse($taskData["recurrence_end_date"])
            : $startDate->copy()->addMonths(1);

        // Skip if start date is after end date
        if ($startDate->greaterThanOrEqualTo($endDate)) {
            return;
        }

        // Generate recurring dates
        $dates = $this->generateRecurringDates(
            $startDate,
            $endDate,
            $taskData["recurrence_type"]
        );

        // Create a task for each date
        foreach ($dates as $date) {
            $newTaskData = $taskData;
            $newTaskData["due_date"] = $date->format("Y-m-d");

            // Set location based on date
            $today = now()->startOfDay();
            $newTaskData["location"] = $date->startOfDay()->equalTo($today)
                ? Todo::LOCATION_TODAY
                : Todo::LOCATION_SCHEDULED;

            // Remove recurrence information
            unset(
                $newTaskData["recurrence_type"],
                $newTaskData["recurrence_end_date"]
            );

            Todo::create($newTaskData);
        }
    }

    /**
     * Generate dates for recurring tasks.
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param string $recurrenceType
     * @return array
     */
    private function generateRecurringDates(
        $startDate,
        $endDate,
        $recurrenceType
    ): array {
        $dates = [];
        $currentDate = $startDate->copy();

        switch ($recurrenceType) {
            case "daily":
                $currentDate->addDay();
                while ($currentDate->lessThanOrEqualTo($endDate)) {
                    $dates[] = $currentDate->copy();
                    $currentDate->addDay();
                }
                break;

            case "weekly":
                $currentDate->addWeek();
                while ($currentDate->lessThanOrEqualTo($endDate)) {
                    $dates[] = $currentDate->copy();
                    $currentDate->addWeek();
                }
                break;

            case "monthly":
                $currentDate->addMonth();
                while ($currentDate->lessThanOrEqualTo($endDate)) {
                    $dates[] = $currentDate->copy();
                    $currentDate->addMonth();
                }
                break;
        }

        return $dates;
    }
}
