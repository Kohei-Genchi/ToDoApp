<?php

namespace App\Http\Controllers;

use App\Http\Requests\TodoRequest;
use App\Models\Todo;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TodoController extends Controller
{
    /**
     * Display the todo list index page.
     *
     * @return View
     */
    public function index(): View
    {
        // Simply return the todos index view
        // The Vue.js component will handle data loading via API
        return view('todos.index');
    }

    /**
     * Store a new todo (web form submission).
     *
     * @param TodoRequest $request
     * @return RedirectResponse
     */
    public function store(TodoRequest $request): RedirectResponse
    {
        try {
            // Prepare task data
            $taskData = $this->prepareTaskData($request->validated());

            // Create the task
            $todo = Todo::create($taskData);

            // Create recurring tasks if needed
            if ($this->shouldCreateRecurringTasks($taskData)) {
                $this->createRecurringTasks($taskData);
            }

            return back()->with('success', 'Task created successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating task: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Toggle task status.
     *
     * @param Todo $todo
     * @return RedirectResponse
     */
    public function toggle(Todo $todo): RedirectResponse
    {
        try {
            // Toggle status
            $todo->status = $todo->status === Todo::STATUS_COMPLETED
                ? Todo::STATUS_PENDING
                : Todo::STATUS_COMPLETED;
            $todo->save();

            return back()->with('success', 'Task status updated');
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating task status');
        }
    }

    /**
     * Delete a todo.
     *
     * @param Todo $todo
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Todo $todo, Request $request): RedirectResponse
    {
        try {
            // Delete recurring tasks if requested
            if ($request->has('delete_recurring')) {
                $this->deleteRecurringTasks($todo);
                return back()->with('success', 'Recurring tasks deleted successfully');
            }

            // Delete single task
            $todo->delete();

            return back()->with('success', 'Task deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting task');
        }
    }

    /**
     * Prepare task data.
     *
     * @param array $validated
     * @return array
     */
    private function prepareTaskData(array $validated): array
    {
        $taskData = $validated;
        $taskData['user_id'] = Auth::id();

        // Set task location
        if (isset($taskData['due_date']) && $taskData['due_date']) {
            // Convert date to Carbon instance for comparison
            $dueDate = now()->parse($taskData['due_date'])->startOfDay();
            $today = now()->startOfDay();

            $taskData['location'] = $dueDate->equalTo($today)
                ? Todo::LOCATION_TODAY
                : Todo::LOCATION_SCHEDULED;
        } else {
            $taskData['location'] = Todo::LOCATION_INBOX;
            $taskData['due_date'] = null;
            $taskData['due_time'] = null;
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
        return !empty($taskData['recurrence_type']) &&
            $taskData['recurrence_type'] !== 'none';
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
        if (empty($taskData['due_date'])) {
            return;
        }

        // Prepare dates
        $startDate = now()->parse($taskData['due_date']);
        $endDate = !empty($taskData['recurrence_end_date'])
            ? now()->parse($taskData['recurrence_end_date'])
            : $startDate->copy()->addMonths(1);

        // Skip if start date is after end date
        if ($startDate->greaterThanOrEqualTo($endDate)) {
            return;
        }

        // Generate recurring dates
        $dates = $this->generateRecurringDates(
            $startDate,
            $endDate,
            $taskData['recurrence_type']
        );

        // Create a task for each date
        foreach ($dates as $date) {
            $newTaskData = $taskData;
            $newTaskData['due_date'] = $date->format('Y-m-d');

            // Set location based on date
            $today = now()->startOfDay();
            $newTaskData['location'] = $date->startOfDay()->equalTo($today)
                ? Todo::LOCATION_TODAY
                : Todo::LOCATION_SCHEDULED;

            // Remove recurrence information
            unset(
                $newTaskData['recurrence_type'],
                $newTaskData['recurrence_end_date']
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
    private function generateRecurringDates($startDate, $endDate, $recurrenceType): array
    {
        $dates = [];
        $currentDate = $startDate->copy();

        switch ($recurrenceType) {
            case 'daily':
                $currentDate->addDay();
                while ($currentDate->lessThanOrEqualTo($endDate)) {
                    $dates[] = $currentDate->copy();
                    $currentDate->addDay();
                }
                break;

            case 'weekly':
                $currentDate->addWeek();
                while ($currentDate->lessThanOrEqualTo($endDate)) {
                    $dates[] = $currentDate->copy();
                    $currentDate->addWeek();
                }
                break;

            case 'monthly':
                $currentDate->addMonth();
                while ($currentDate->lessThanOrEqualTo($endDate)) {
                    $dates[] = $currentDate->copy();
                    $currentDate->addMonth();
                }
                break;
        }

        return $dates;
    }

    /**
     * Delete recurring tasks.
     *
     * @param Todo $todo
     * @return void
     */
    private function deleteRecurringTasks(Todo $todo): void
    {
        // Find tasks with the same title and creation time
        $relatedTasks = Todo::where('user_id', $todo->user_id)
            ->where('title', $todo->title)
            ->where('created_at', $todo->created_at)
            ->get();

        // Delete all found tasks
        foreach ($relatedTasks as $task) {
            $task->delete();
        }
    }
}