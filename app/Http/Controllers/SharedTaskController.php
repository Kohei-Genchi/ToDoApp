<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SharedTaskController extends Controller
{
    /**
     * Display tasks shared with the authenticated user
     *
     * @return View
     */
    public function index(): View
    {
        // Get tasks shared with the current user
        $sharedTasks = Auth::user()
            ->sharedTasks()
            ->with(["category", "user"])
            ->get();

        return view("todos.shared", compact("sharedTasks"));
    }

    /**
     * Display the task sharing settings for a specific task
     *
     * @param Todo $todo
     * @return View
     */
    public function showSharing(Todo $todo): View
    {
        // Check if the authenticated user owns this task
        if ($todo->user_id !== Auth::id()) {
            abort(403, "Unauthorized action.");
        }

        // Get users with whom this task is shared
        $sharedUsers = $todo->sharedWith;

        return view("todos.sharing", compact("todo", "sharedUsers"));
    }

    /**
     * Share a task with a user
     *
     * @param Request $request
     * @param Todo $todo
     * @return RedirectResponse
     */
    public function storeSharing(Request $request, Todo $todo): RedirectResponse
    {
        // Check if the authenticated user owns this task
        if ($todo->user_id !== Auth::id()) {
            abort(403, "Unauthorized action.");
        }

        // Validate request
        $validated = $request->validate([
            "email" => "required|email|exists:users,email",
            "permission" => "required|in:view,edit",
        ]);

        try {
            // Find the user by email
            $user = User::where("email", $validated["email"])->first();

            // Don't allow sharing with oneself
            if ($user->id === Auth::id()) {
                return back()->withErrors([
                    "email" => "自分自身と共有することはできません",
                ]);
            }

            // Check if we've reached the maximum number of shares (5)
            if ($todo->sharedWith->count() >= 5) {
                return back()->withErrors([
                    "email" =>
                        "最大共有数 (5人) に達しています。新しく共有するには、既存の共有を解除してください。",
                ]);
            }

            // Share the task
            $todo->shareTo($user, $validated["permission"]);

            return back()->with("success", "タスクが正常に共有されました");
        } catch (\Exception $e) {
            Log::error("Error sharing task: " . $e->getMessage());
            return back()->withErrors([
                "email" =>
                    "タスク共有中にエラーが発生しました: " . $e->getMessage(),
            ]);
        }
    }

    /**
     * Update sharing permission for a user
     *
     * @param Request $request
     * @param Todo $todo
     * @param User $user
     * @return RedirectResponse
     */
    public function updateSharing(
        Request $request,
        Todo $todo,
        User $user
    ): RedirectResponse {
        // Check if the authenticated user owns this task
        if ($todo->user_id !== Auth::id()) {
            abort(403, "Unauthorized action.");
        }

        // Validate request
        $validated = $request->validate([
            "permission" => "required|in:view,edit",
        ]);

        try {
            // Check if the task is shared with the user
            if (!$todo->isSharedWith($user)) {
                return back()->withErrors([
                    "permission" => "タスクはこのユーザーと共有されていません",
                ]);
            }

            // Update the permission
            $todo->sharedWith()->updateExistingPivot($user->id, [
                "permission" => $validated["permission"],
            ]);

            return back()->with("success", "共有権限が更新されました");
        } catch (\Exception $e) {
            Log::error(
                "Error updating sharing permission: " . $e->getMessage()
            );
            return back()->withErrors([
                "permission" =>
                    "共有権限の更新中にエラーが発生しました: " .
                    $e->getMessage(),
            ]);
        }
    }

    /**
     * Stop sharing a task with a user
     *
     * @param Todo $todo
     * @param User $user
     * @return RedirectResponse
     */
    public function destroySharing(Todo $todo, User $user): RedirectResponse
    {
        // Check if the authenticated user owns this task
        if ($todo->user_id !== Auth::id()) {
            abort(403, "Unauthorized action.");
        }

        try {
            // Unshare the task
            $todo->unshareFrom($user);

            return back()->with("success", "タスク共有が解除されました");
        } catch (\Exception $e) {
            Log::error("Error removing task sharing: " . $e->getMessage());
            return back()->withErrors([
                "general" =>
                    "タスク共有解除中にエラーが発生しました: " .
                    $e->getMessage(),
            ]);
        }
    }
}
