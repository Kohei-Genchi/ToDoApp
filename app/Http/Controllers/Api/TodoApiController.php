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

            // Temporarily disabled subscription check for development
            // @Todo
            // if (
            //     ($request->view === "shared" || $request->view === "kanban") &&
            //     !Auth::user()->subscription_id
            // ) {
            //     return response()->json(
            //         [
            //             "error" =>
            //                 "共有機能を利用するにはサブスクリプションが必要です。",
            //             "subscription_required" => true,
            //         ],
            //         403
            //     );
            // }

            // Get tasks using the service
            $view = $request->view ?? "today";
            $date = $request->date;
            $userId = $request->user_id;
            $categoryId = $request->category_id; // Added for kanban filtering
            $status = $request->status; // Added for kanban filtering

            // Log the request parameters for debugging
            Log::info("Task request parameters:", [
                "view" => $view,
                "date" => $date,
                "user_id" => $userId,
                "category_id" => $categoryId,
                "status" => $status,
            ]);

            // If a specific category is requested, we check if it's shared with the current user
            if ($categoryId) {
                $category = Category::find($categoryId);

                if ($category) {
                    Log::info("Category requested:", [
                        "category_id" => $categoryId,
                        "category_name" => $category->name,
                        "owner_id" => $category->user_id,
                    ]);

                    // If the category is owned by someone else
                    if ($category->user_id !== Auth::id()) {
                        // Check if it's shared with current user
                        $isShared = Auth::user()
                            ->sharedCategories()
                            ->where("category_id", $categoryId)
                            ->exists();

                        Log::info("Category shared status:", [
                            "is_shared" => $isShared,
                        ]);

                        if (!$isShared) {
                            return response()->json(
                                [
                                    "error" =>
                                        "You do not have access to this category",
                                ],
                                403
                            );
                        }

                        // If shared, we'll get all tasks from this category
                        $todos = Todo::where("category_id", $categoryId)
                            ->with(["category", "user"])
                            ->get();

                        Log::info("Returning tasks from shared category", [
                            "count" => $todos->count(),
                        ]);

                        return response()->json($todos);
                    }
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
                    "todo" => $todo->load("category", "user"),
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

            $todo->load("category", "user");

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

            // Update the task with only the provided fields
            $todo->update($updateData);
            $todo->refresh();

            // Update location if due date changed
            if ($request->has("due_date")) {
                $this->handleTaskLocation($todo);
                $todo->save();
            }

            $todo->load("category", "user"); // Added loading user relation for kanban

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
                "todo" => $updatedTodo->load("category", "user"),
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
