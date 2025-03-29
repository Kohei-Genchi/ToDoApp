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
                return $task->status === "in_progress";
            });

            $reviewTasks = $allTasks->filter(function ($task) {
                return $task->status === "review";
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
        $sharedWithMe = Auth::user()
            ->sharedCategories()
            ->with("user") // Include the owner of the category
            ->get();

        // Get categories that current user has shared with others
        $mySharedCategories = Category::where("user_id", Auth::id())
            ->with("sharedWith") // Include users with whom the category is shared
            ->whereHas("sharedWith") // Only include categories that are actually shared
            ->get();

        // Get unique team members (both directions of sharing)
        $teamMembers = collect();

        // Add users who shared categories with me
        foreach ($sharedWithMe as $category) {
            if (!$teamMembers->contains("id", $category->user_id)) {
                $teamMembers->push([
                    "id" => $category->user_id,
                    "name" => $category->user->name,
                    "email" => $category->user->email,
                    "relationship" => "shared_with_me",
                    "categories" => [$category->name],
                ]);
            }
        }

        // Add users with whom I've shared categories
        foreach ($mySharedCategories as $category) {
            foreach ($category->sharedWith as $user) {
                $existingMember = $teamMembers->firstWhere("id", $user->id);

                if ($existingMember) {
                    // Add category to existing member if not already present
                    if (
                        !in_array(
                            $category->name,
                            $existingMember["categories"]
                        )
                    ) {
                        $existingMember["categories"][] = $category->name;
                    }
                } else {
                    $teamMembers->push([
                        "id" => $user->id,
                        "name" => $user->name,
                        "email" => $user->email,
                        "relationship" => "shared_by_me",
                        "categories" => [$category->name],
                    ]);
                }
            }
        }

        return view("tasks.team-members", [
            "teamMembers" => $teamMembers,
            "sharedWithMe" => $sharedWithMe,
            "mySharedCategories" => $mySharedCategories,
        ]);
    }

    /**
     * Display analytics dashboard for task progress
     *
     * @return \Illuminate\View\View
     */
    public function analytics()
    {
        return view("tasks.analytics");
    }
}
