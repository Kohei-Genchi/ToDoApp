<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TodoRequest;
use App\Http\Requests\TodoUpdateRequest;
use App\Models\Todo;
use App\Models\Category;
use App\Services\TaskService;
use App\Traits\HandlesApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TodoApiController extends Controller
{
    use HandlesApiResponses;

    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    private function addPermissionsToTasks($tasks)
    {
        return $tasks->map(function ($task) {
            // タスクの所有者なら完全な権限を持つ
            if ($task->user_id === Auth::id()) {
                $task->can_edit = true;
                $task->can_delete = true;
                return $task;
            }

            // 所有者でない場合（共有タスク）
            // カテゴリーを通じた共有権限をチェック
            if ($task->category_id) {
                $sharedCategory = Auth::user()
                    ->sharedCategories()
                    ->where("categories.id", $task->category_id)
                    ->first();

                if ($sharedCategory) {
                    // 編集権限は共有設定によって決まる
                    $task->can_edit =
                        $sharedCategory->pivot->permission === "edit";
                    // 削除権限は所有者のみ
                    $task->can_delete = false;
                    return $task;
                }
            }

            // デフォルトでは権限なし
            $task->can_edit = false;
            $task->can_delete = false;
            return $task;
        });
    }

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

            // Get tasks using the service
            $view = $request->view ?? "today";
            $date = $request->date;
            $userId = $request->user_id;
            $categoryId = $request->category_id;
            $status = $request->status;
            $location = $request->location;

            if ($location) {
                Log::info("Filtering tasks by location: {$location}");
                // Directly filter by location when specified
                $todos = Auth::user()
                    ->todos()
                    ->where("location", $location)
                    ->when($status, function ($query, $status) {
                        return $query->where("status", $status);
                    })
                    ->with(["category", "user"])
                    ->get();

                Log::info(
                    "Found " .
                        count($todos) .
                        " tasks for location: {$location}"
                );
                return response()->json($todos);
            }
            // Handle "shared" view specifically for shared tasks
            if ($view === "shared") {
                try {
                    // 1. 共有カテゴリーからのタスク
                    $sharedCategoryIds = Auth::user()
                        ->sharedCategories()
                        ->pluck("categories.id");

                    $sharedTasks = collect([]);

                    if ($sharedCategoryIds->isNotEmpty()) {
                        $sharedTasks = Todo::whereIn(
                            "category_id",
                            $sharedCategoryIds
                        )
                            ->with(["category", "user"])
                            ->get();
                    }

                    // 2. 自分のタスクも追加
                    $ownTasks = Auth::user()
                        ->todos()
                        ->with(["category", "user"])
                        ->get();

                    // 3. タスクをマージして返す
                    $combinedTasks = $sharedTasks
                        ->concat($ownTasks)
                        ->unique("id");

                    // 権限情報を含める
                    if ($request->boolean("include_permissions", false)) {
                        $combinedTasks = $this->addPermissionsToTasks(
                            $combinedTasks
                        );
                    }

                    return response()->json($combinedTasks);
                } catch (\Exception $e) {
                    Log::error(
                        "Error retrieving shared tasks: " . $e->getMessage()
                    );
                    return response()->json([]);
                }
            }

            // New "all" view type for kanban board that returns all tasks regardless of date
            if ($view === "all") {
                $todos = $this->taskService->getAllTasks(
                    $userId,
                    $categoryId,
                    $status
                );
                return response()->json($todos);
            }

            $todos = $this->taskService->getTasks(
                $view,
                $date,
                $userId,
                $categoryId
            );

            return response()->json($todos);
        } catch (\Exception $e) {
            Log::error("Error retrieving tasks: " . $e->getMessage());
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

            $todo = $this->taskService->createTask($request->validated());

            return response()->json(
                [
                    "message" => "Task created successfully",
                    "todo" => $todo->load("category", "user"),
                ],
                201
            );
        } catch (\Exception $e) {
            Log::error("Error creating task: " . $e->getMessage());
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

            $todo->load("category", "user");

            return response()->json($todo);
        } catch (\Exception $e) {
            Log::error("Error retrieving task: " . $e->getMessage());
            return response()->json(
                ["error" => "Error retrieving task: " . $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update a todo.
     *
     * @param Request $request
     * @param Todo $todo
     * @return JsonResponse
     */
    public function update(Request $request, Todo $todo): JsonResponse
    {
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
                // Validate status is one of the allowed values for kanban
                $allowedStatuses = [
                    "pending",
                    "in_progress",
                    "review",
                    "completed",
                    "trashed",
                ];
                $status = $request->input("status");

                if (in_array($status, $allowedStatuses)) {
                    $updateData["status"] = $status;
                } else {
                    return response()->json(
                        [
                            "error" =>
                                "Invalid status value. Allowed values are: " .
                                implode(", ", $allowedStatuses),
                        ],
                        422
                    );
                }
            }

            if ($request->has("category_id")) {
                $updateData["category_id"] = $request->input("category_id");
            }

            // Added for kanban to support description
            if ($request->has("description")) {
                $updateData["description"] = $request->input("description");
            }

            // Handle date and time carefully to prevent timezone issues
            if ($request->has("due_date")) {
                // Use the date as provided without timezone adjustments
                $updateData["due_date"] = $request->input("due_date");
            }

            if ($request->has("due_time")) {
                $updateData["due_time"] = $request->input("due_time");
            }

            // Recurrence fields
            if ($request->has("recurrence_type")) {
                $updateData["recurrence_type"] = $request->input(
                    "recurrence_type"
                );
            }

            if ($request->has("recurrence_end_date")) {
                $updateData["recurrence_end_date"] = $request->input(
                    "recurrence_end_date"
                );
            }

            // Log the update data for debugging
            Log::info("Updating task with data: ", $updateData);

            // Update the task with only the provided fields
            $todo->update($updateData);
            $todo->refresh();

            // Update location if due date changed
            if ($request->has("due_date")) {
                $this->handleTaskLocation($todo);
                $todo->save();
            }

            $todo->load("category", "user");

            return response()->json([
                "message" => "Task updated successfully",
                "todo" => $todo,
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating task: " . $e->getMessage());

            return response()->json(
                ["error" => "Error updating task: " . $e->getMessage()],
                500
            );
        }
    }

    public function updateStatus(Request $request, Todo $todo)
    {
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                return response()->json(
                    [
                        "error" => "Authentication required",
                    ],
                    401
                );
            }

            // Check if the user is authorized to update this task
            if (Gate::denies("update", $todo)) {
                return response()->json(
                    [
                        "error" =>
                            "Unauthorized access. You don't have permission to update this task.",
                    ],
                    403
                );
            }

            // Validate status parameter
            $validated = $request->validate([
                "status" =>
                    "required|in:pending,in_progress,review,completed,trashed",
            ]);

            // Update the task status
            $todo->status = $validated["status"];
            $todo->save();

            return response()->json([
                "success" => true,
                "message" => "Task status updated successfully",
                "todo" => $todo,
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating task status: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "Error updating task status: " . $e->getMessage(),
                ],
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

            // Use the service to toggle the task
            $updatedTodo = $this->taskService->toggleTaskStatus($todo);

            return response()->json([
                "message" => "Task status updated successfully",
                "todo" => $updatedTodo->load("category", "user"),
            ]);
        } catch (\Exception $e) {
            Log::error("Error toggling task status: " . $e->getMessage());
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

            // Use service to delete the task(s)
            $deleteRecurring = $request->has("delete_recurring");
            $this->taskService->deleteTask($todo, $deleteRecurring);

            return response()->json([
                "message" => $deleteRecurring
                    ? "Recurring tasks deleted successfully"
                    : "Task deleted successfully",
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting task: " . $e->getMessage());
            return response()->json(
                ["error" => "Error deleting task: " . $e->getMessage()],
                500
            );
        }
    }
}
