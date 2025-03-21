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

        // Allow if task is individually shared with user
        if ($todo->isSharedWith($user)) {
            Log::info(
                "Task {$todo->id} is individually shared with user {$user->id}"
            );
            return true;
        }

        // Check for global sharing
        $globalShare = $user
            ->globallySharedBy()
            ->where("user_id", $todo->user_id)
            ->first();

        if ($globalShare) {
            Log::info(
                "Task {$todo->id} is globally shared with user {$user->id}"
            );
            return true;
        }

        // Also check if the current user is globally sharing with the task owner
        $reverseGlobalShare = $user
            ->globallySharedWith()
            ->where("shared_with_user_id", $todo->user_id)
            ->first();

        if ($reverseGlobalShare) {
            Log::info(
                "User {$user->id} is globally sharing with task owner {$todo->user_id}"
            );
            return true;
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

        // Allow if task is individually shared with user and they have edit permission
        if ($todo->isSharedWith($user)) {
            $taskShare = $todo
                ->sharedWith()
                ->where("user_id", $user->id)
                ->first();

            if ($taskShare && $taskShare->pivot->permission === "edit") {
                Log::info(
                    "User {$user->id} has edit permission for shared task {$todo->id}"
                );
                return true;
            }

            Log::info(
                "User {$user->id} has view-only permission for shared task {$todo->id}"
            );
        }

        // Check for global sharing from task owner to current user
        $globalShare = $user
            ->globallySharedBy()
            ->where("user_id", $todo->user_id)
            ->first();

        if ($globalShare && $globalShare->permission === "edit") {
            Log::info(
                "User {$user->id} has edit permission via global sharing from task owner {$todo->user_id}"
            );
            return true;
        }

        // Also check if current user is sharing globally with the task owner
        $reverseGlobalShare = $user
            ->globallySharedWith()
            ->where("shared_with_user_id", $todo->user_id)
            ->first();

        if ($reverseGlobalShare && $reverseGlobalShare->permission === "edit") {
            Log::info(
                "User {$user->id} has edit permission via reverse global sharing with task owner {$todo->user_id}"
            );
            return true;
        }

        Log::info(
            "User {$user->id} denied update permission for task {$todo->id}"
        );
        return false;
    }

    public function delete(User $user, Todo $todo): bool
    {
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
