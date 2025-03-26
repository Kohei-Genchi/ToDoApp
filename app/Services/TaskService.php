<?php

namespace App\Services;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskService
{
    /**
     * Get tasks based on view type and date
     */
    public function getTasks(
        string $view = "today",
        ?string $date = null,
        ?int $userId = null
    ) {
        $date = $date ? now()->parse($date) : now();
        $query = $this->buildBaseTaskQuery($userId);

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
                    $date->copy()->startOfMonth()->format("Y-m-d"),
                    $date->copy()->endOfMonth()->format("Y-m-d"),
                ]);
                break;

            case "date":
                // For specific date view
                $query->whereDate("due_date", $date->format("Y-m-d"));
                break;

            default:
                // Default to today
                $query->whereDate("due_date", $date->format("Y-m-d"));
                break;
        }

        return $query->orderBy("due_time", "asc")->get();
    }

    /**
     * Create a new task with appropriate data preparation
     */
    public function createTask(array $data)
    {
        $taskData = $this->prepareTaskData($data);
        $todo = Todo::create($taskData);

        // Create recurring tasks if needed
        if ($this->shouldCreateRecurringTasks($taskData)) {
            $this->createRecurringTasks($taskData);
        }

        return $todo;
    }

    /**
     * Toggle task status between completed and pending
     */
    public function toggleTaskStatus(Todo $todo)
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
     */
    public function deleteTask(Todo $todo, bool $deleteRecurring = false)
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
     */
    protected function buildBaseTaskQuery(?int $userId = null)
    {
        $currentUserId = Auth::id();

        // If no specific user requested, return current user's tasks
        if (!$userId) {
            return Todo::where("user_id", $currentUserId)->with("category");
        }

        // If user is requesting their own tasks
        if ($userId === $currentUserId) {
            return Todo::where("user_id", $currentUserId)->with([
                "category",
                "user",
            ]);
        }

        // Check global share access
        $user = Auth::user();
        $isSharedGlobally = $user
            ->globallySharedBy()
            ->where("user_id", $userId)
            ->exists();

        $isUserSharingGlobally = $user
            ->globallySharedWith()
            ->where("shared_with_user_id", $userId)
            ->exists();

        if ($isSharedGlobally || $isUserSharingGlobally) {
            return Todo::where("user_id", $userId)->with(["category", "user"]);
        }

        // Check individual task sharing
        $sharedTaskIds = $user
            ->sharedTasks()
            ->where("user_id", $userId)
            ->pluck("id");

        if ($sharedTaskIds->isNotEmpty()) {
            return Todo::where("user_id", $userId)
                ->whereIn("id", $sharedTaskIds)
                ->with(["category", "user"]);
        }

        // Check category sharing
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
        return Todo::where("user_id", $currentUserId)->with("category");
    }

    /**
     * Prepare task data for storage with proper formatting
     */
    protected function prepareTaskData(array $data): array
    {
        $taskData = $data;
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
     * Determine if recurring tasks should be created
     */
    protected function shouldCreateRecurringTasks(array $taskData): bool
    {
        return !empty($taskData["recurrence_type"]) &&
            $taskData["recurrence_type"] !== "none";
    }

    /**
     * Create recurring tasks based on task data
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
