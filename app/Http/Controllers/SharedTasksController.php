<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\CategoryShareController;
use App\Models\Todo;
use App\Models\Category;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class SharedTasksController extends Controller
{
    /**
     * Display the kanban board for shared tasks
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Check if user has a subscription
        // if (!Auth::user()->subscription_id) {
        //     return redirect()
        //         ->route("stripe.subscription")
        //         ->with(
        //             "error",
        //             "この機能を利用するにはサブスクリプションが必要です。"
        //         );
        // }

        try {
            // Get the user's own tasks
            $ownTasks = Auth::user()
                ->todos()
                ->with("category", "user")
                ->where("status", "!=", "trashed")
                ->get();

            // Get tasks from shared categories
            $sharedCategoryIds = Auth::user()
                ->sharedCategories()
                ->pluck("categories.id");
            $sharedTasks = collect([]);

            if ($sharedCategoryIds->isNotEmpty()) {
                $sharedTasks = Todo::whereIn("category_id", $sharedCategoryIds)
                    ->with("category", "user")
                    ->where("status", "!=", "trashed")
                    ->get();
            }

            // Combine tasks and filter by status
            $allTasks = $ownTasks->concat($sharedTasks);

            $pendingTasks = $allTasks->filter(function ($task) {
                return $task->status === "pending";
            });

            $inProgressTasks = $allTasks->filter(function ($task) {
                return $task->status === "in_progress" ||
                    $task->status === "ongoing";
            });

            $reviewTasks = $allTasks->filter(function ($task) {
                return $task->status === "review" || $task->status === "paused";
            });

            $completedTasks = $allTasks->filter(function ($task) {
                return $task->status === "completed";
            });

            return view(
                "tasks.kanban",
                compact(
                    "pendingTasks",
                    "inProgressTasks",
                    "reviewTasks",
                    "completedTasks"
                )
            );
        } catch (\Exception $e) {
            Log::error("Error loading kanban board: " . $e->getMessage());

            // Return the view with empty collections
            return view("tasks.kanban", [
                "pendingTasks" => collect([]),
                "inProgressTasks" => collect([]),
                "reviewTasks" => collect([]),
                "completedTasks" => collect([]),
            ]);
        }
    }

    /**
     * Display team members page for task collaboration
     *
     * @return \Illuminate\View\View
     */
    public function teamMembers()
    {
        // Check if user has a subscription
        // if (!Auth::user()->subscription_id) {
        //     return redirect()
        //         ->route("stripe.subscription")
        //         ->with(
        //             "error",
        //             "この機能を利用するにはサブスクリプションが必要です。"
        //         );
        // }

        return view("tasks.team");
    }

    /**
     * Display analytics dashboard for task progress
     *
     * @return \Illuminate\View\View
     */
    public function analytics()
    {
        // Check if user has a subscription
        // if (!Auth::user()->subscription_id) {
        //     return redirect()
        //         ->route("stripe.subscription")
        //         ->with(
        //             "error",
        //             "この機能を利用するにはサブスクリプションが必要です。"
        //         );
        // }

        return view("tasks.analytics");
    }
}
