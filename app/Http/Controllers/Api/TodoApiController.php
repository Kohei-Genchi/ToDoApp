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

            // Subscription check for calendar view
            if ($request->view === "shared" && !Auth::user()->subscription_id) {
                return response()->json(
                    [
                        "error" =>
                            "共有機能を利用するにはサブスクリプションが必要です。",
                        "subscription_required" => true,
                    ],
                    403
                );
            }

            // Get tasks using the service
            $view = $request->view ?? "today";
            $date = $request->date;
            $userId = $request->user_id;

            $todos = $this->taskService->getTasks($view, $date, $userId);

            return response()->json($todos);
        } catch (\Exception $e) {
            Log::error("Error retrieving tasks: " . $e->getMessage(), [
                "exception" => $e,
                "request" => $request->all(),
            ]);
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
                    "todo" => $todo->load("category"),
                ],
                201
            );
        } catch (\Exception $e) {
            Log::error("Error creating task: " . $e->getMessage(), [
                "exception" => $e,
                "request" => $request->all(),
            ]);
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
            Log::error("Error retrieving task: " . $e->getMessage(), [
                "exception" => $e,
                "todo_id" => $todo->id ?? "unknown",
            ]);
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

            // Use the service to toggle the task
            $updatedTodo = $this->taskService->toggleTaskStatus($todo);

            return response()->json([
                "message" => "Task status updated successfully",
                "todo" => $updatedTodo->load("category"),
            ]);
        } catch (\Exception $e) {
            Log::error("Error toggling task status: " . $e->getMessage(), [
                "exception" => $e,
                "todo_id" => $todo->id,
            ]);
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
            Log::error("Error deleting task: " . $e->getMessage(), [
                "exception" => $e,
                "todo_id" => $todo->id,
                "delete_recurring" => $request->has("delete_recurring"),
            ]);
            return response()->json(
                ["error" => "Error deleting task: " . $e->getMessage()],
                500
            );
        }
    }
}
