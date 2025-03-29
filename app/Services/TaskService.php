<?php

namespace App\Services;

use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskService
{
    /**
     * Get tasks based on view type and date
     *
     * @param string $view View type (today, scheduled, inbox, date)
     * @param string|null $date Date string
     * @param int|null $userId User ID
     * @param int|null $categoryId Category ID for filtering
     * @return Collection Tasks collection
     */
    public function getTasks(
        string $view = "today",
        ?string $date = null,
        ?int $userId = null,
        ?int $categoryId = null
    ): Collection {
        $date = $date ? now()->parse($date) : now();
        $query = $this->buildBaseTaskQuery($userId);

        // If category filter is applied
        if ($categoryId) {
            $query->where("category_id", $categoryId);
        }

        switch ($view) {
            case "today":
                $query->whereDate("due_date", $date->format("Y-m-d"));
                break;

            case "scheduled":
                $query
                    ->whereNotNull("due_date")
                    ->whereDate("due_date", ">", now()->format("Y-m-d"))
                    ->where("status", "!=", Todo::STATUS_TRASHED);
                break;

            case "inbox":
                $query
                    ->whereNull("due_date")
                    ->where("status", "!=", Todo::STATUS_TRASHED);
                break;

            case "date":
                // For specific date view
                $query->whereDate("due_date", $date->format("Y-m-d"));
                break;

            case "kanban":
            case "shared":
                // For kanban and shared views, load all non-trashed tasks
                $query->where("status", "!=", Todo::STATUS_TRASHED);
                break;

            default:
                // Default to today
                $query->whereDate("due_date", $date->format("Y-m-d"));
                break;
        }

        return $query->orderBy("due_time", "asc")->get();
    }

    /**
     * Get all tasks for kanban view without date restrictions
     *
     * @param int|null $userId User ID
     * @param int|null $categoryId Category ID for filtering
     * @param string|null $status Status for filtering
     * @return Collection Tasks collection
     */
    public function getAllTasks(
        ?int $userId = null,
        ?int $categoryId = null,
        ?string $status = null
    ): Collection {
        $query = $this->buildBaseTaskQuery($userId);

        // Exclude trashed tasks
        $query->where("status", "!=", Todo::STATUS_TRASHED);

        // Apply category filter if provided
        if ($categoryId) {
            $query->where("category_id", $categoryId);
        }

        // Apply status filter if provided
        if ($status) {
            $query->where("status", $status);
        }

        return $query
            ->with(["category", "user"])
            ->orderBy("created_at", "desc")
            ->get();
    }

    /**
     * Create a new task with appropriate data preparation
     *
     * @param array $data Task data
     * @return Todo Created task
     */
    public function createTask(array $data): Todo
    {
        $taskData = $this->prepareTaskData($data);

        // Log for debugging
        Log::info("Creating task with data: ", $taskData);

        $todo = Todo::create($taskData);

        // Create recurring tasks if needed
        if ($this->shouldCreateRecurringTasks($taskData)) {
            $this->createRecurringTasks($taskData);
        }

        return $todo;
    }

    /**
     * Toggle task status between completed and pending
     *
     * @param Todo $todo Task to toggle
     * @return Todo Updated task
     */
    public function toggleTaskStatus(Todo $todo): Todo
    {
        $todo->status =
            $todo->status === Todo::STATUS_COMPLETED
                ? Todo::STATUS_PENDING
                : Todo::STATUS_COMPLETED;

        $todo->save();

        return $todo->fresh();
    }

    /**
     * Delete a task (with optional recurring tasks deletion)
     *
     * @param Todo $todo Task to delete
     * @param bool $deleteRecurring Whether to delete recurring tasks
     * @return int Number of deleted tasks
     */
    public function deleteTask(Todo $todo, bool $deleteRecurring = false): int
    {
        if ($deleteRecurring) {
            return Todo::where("user_id", $todo->user_id)
                ->where("title", $todo->title)
                ->where("created_at", $todo->created_at)
                ->delete();
        }

        return $todo->delete();
    }

    /**
     * Build the base query for tasks with proper access controls
     *
     * @param int|null $userId User ID to filter by
     * @return \Illuminate\Database\Eloquent\Builder Query builder
     */
    protected function buildBaseTaskQuery(?int $userId = null)
    {
        $currentUserId = Auth::id();

        // If no specific user requested, return current user's tasks
        if (!$userId) {
            return Todo::where("user_id", $currentUserId)->with([
                "category",
                "user",
            ]);
        }

        // If user is requesting their own tasks
        if ($userId === $currentUserId) {
            return Todo::where("user_id", $currentUserId)->with([
                "category",
                "user",
            ]);
        }

        // Check shared category access
        $user = Auth::user();
        $sharedCategoryIds = $user
            ->sharedCategories()
            ->where("user_id", $userId)
            ->pluck("categories.id");

        if ($sharedCategoryIds->isNotEmpty()) {
            return Todo::where("user_id", $userId)
                ->whereIn("category_id", $sharedCategoryIds)
                ->with(["category", "user"]);
        }

        // Default to current user's tasks if no access to requested user
        Log::warning(
            "User {$currentUserId} attempted to access tasks of user {$userId} without permission"
        );
        return Todo::where("user_id", $currentUserId)->with([
            "category",
            "user",
        ]);
    }

    /**
     * Prepare task data for storage with proper formatting
     *
     * @param array $data Task data
     * @return array Prepared task data
     */
    protected function prepareTaskData(array $data): array
    {
        $taskData = $data;
        $taskData["user_id"] = Auth::id();

        // Handle kanban-specific statuses
        if (isset($taskData["status"])) {
            // Validate against allowed statuses
            $allowedStatuses = [
                Todo::STATUS_PENDING,
                Todo::STATUS_IN_PROGRESS,
                Todo::STATUS_REVIEW,
                Todo::STATUS_COMPLETED,
                Todo::STATUS_TRASHED,
            ];

            if (!in_array($taskData["status"], $allowedStatuses)) {
                $taskData["status"] = Todo::STATUS_PENDING;
            }
        } else {
            $taskData["status"] = Todo::STATUS_PENDING;
        }

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
     * Determine if recurring tasks should be created
     *
     * @param array $taskData Task data
     * @return bool Whether to create recurring tasks
     */
    protected function shouldCreateRecurringTasks(array $taskData): bool
    {
        return !empty($taskData["recurrence_type"]) &&
            $taskData["recurrence_type"] !== "none";
    }

    /**
     * Create recurring tasks based on task data
     *
     * @param array $taskData Task data
     * @return void
     */
    protected function createRecurringTasks(array $taskData): void
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
     * Generate dates for recurring tasks
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @param string $recurrenceType Recurrence type (daily, weekly, monthly)
     * @return array Array of Carbon dates
     */
    protected function generateRecurringDates(
        Carbon $startDate,
        Carbon $endDate,
        string $recurrenceType
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
