<?php

namespace App\Policies;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;

class TodoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Todo $todo): bool
    {
        // Allow if user owns the task
        if ($user->id === $todo->user_id) {
            return true;
        }

        // Check for shared categories
        if ($todo->category_id) {
            Log::debug(
                "Checking category share permission for task {$todo->id}, category {$todo->category_id}"
            );

            $sharedCategory = $user
                ->sharedCategories()
                ->where("categories.id", $todo->category_id)
                ->first();

            if ($sharedCategory) {
                Log::info(
                    "Task {$todo->id} is viewable through shared category {$todo->category_id} for user {$user->id}"
                );
                return true;
            }
        }

        Log::info("User {$user->id} denied view access to task {$todo->id}");
        return false;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Todo $todo): bool
    {
        Log::info(
            "Checking update permission for user {$user->id} on task {$todo->id}"
        );

        // Allow if user owns the task
        if ($user->id === $todo->user_id) {
            Log::info("User {$user->id} owns task {$todo->id}");
            return true;
        }

        // Check for shared categories with edit permission
        if ($todo->category_id) {
            Log::debug(
                "Checking category edit permission for task {$todo->id}, category {$todo->category_id}"
            );

            $sharedCategory = $user
                ->sharedCategories()
                ->where("categories.id", $todo->category_id)
                ->first();

            Log::debug("Shared category found:", [
                "has_category" => $sharedCategory ? true : false,
                "permission" => $sharedCategory
                    ? $sharedCategory->pivot->permission
                    : "none",
            ]);

            if (
                $sharedCategory &&
                $sharedCategory->pivot->permission === "edit"
            ) {
                Log::info(
                    "User {$user->id} has edit permission for task {$todo->id} through category {$todo->category_id}"
                );
                return true;
            }
        }

        Log::info(
            "User {$user->id} denied update permission for task {$todo->id}"
        );
        return false;
    }

    public function delete(User $user, Todo $todo): bool
    {
        // Only the owner can delete tasks
        return $user->id === $todo->user_id;
    }

    public function restore(User $user, Todo $todo): bool
    {
        return false;
    }

    public function forceDelete(User $user, Todo $todo): bool
    {
        return false;
    }
}
