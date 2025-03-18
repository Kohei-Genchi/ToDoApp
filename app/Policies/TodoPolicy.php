<?php

namespace App\Policies;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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

        // Allow if task is shared with user (any permission level)
        return $todo->isSharedWith($user);
    }


    public function create(User $user): bool
    {
        return true;
    }


    public function update(User $user, Todo $todo): bool
    {
        // Allow if user owns the task
        if ($user->id === $todo->user_id) {
            return true;
        }

        // Allow if task is shared with user and they have edit permission
        if ($todo->isSharedWith($user)) {
            $sharePermission = $todo->sharedWith()
                ->where('user_id', $user->id)
                ->first()
                ->pivot
                ->permission ?? null;

            return $sharePermission === 'edit';
        }

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
