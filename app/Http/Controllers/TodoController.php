<?php

namespace App\Http\Controllers;

use App\Http\Requests\TodoRequest;
use App\Models\Todo;
use App\Models\Category;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TodoController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Display the todo list index page.
     *
     * @return View
     */
    public function index(): View
    {
        // Simply return the todos index view
        // The Vue.js component will handle data loading via API
        return view("todos.index");
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
            // Use the service to create the task
            $this->taskService->createTask($request->validated());

            return back()->with("success", "Task created successfully");
        } catch (\Exception $e) {
            return back()
                ->with("error", "Error creating task: " . $e->getMessage())
                ->withInput();
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
            // Use the service to toggle the task
            $this->taskService->toggleTaskStatus($todo);

            return back()->with("success", "Task status updated");
        } catch (\Exception $e) {
            return back()->with(
                "error",
                "Error updating task status: " . $e->getMessage()
            );
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
            // Use the service to delete the task(s)
            $deleteRecurring = $request->has("delete_recurring");
            $this->taskService->deleteTask($todo, $deleteRecurring);

            return back()->with(
                "success",
                $deleteRecurring
                    ? "Recurring tasks deleted successfully"
                    : "Task deleted successfully"
            );
        } catch (\Exception $e) {
            return back()->with(
                "error",
                "Error deleting task: " . $e->getMessage()
            );
        }
    }
}
