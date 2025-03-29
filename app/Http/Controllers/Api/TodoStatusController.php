<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TodoStatusController extends Controller
{
    /**
     * Update task status via AJAX from Kanban board
     */
    public function updateStatus(Request $request, Todo $todo)
    {
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                return response()->json(
                    ["error" => "Authentication required"],
                    401
                );
            }

            // Check if the user is authorized to update this task
            // You can use your existing authorization logic here
            if ($todo->user_id !== Auth::id()) {
                return response()->json(
                    [
                        "error" =>
                            "Unauthorized access. You don't have permission to update this task.",
                    ],
                    403
                );
            }

            // Validate status parameter
            $validated = $request->validate([
                "status" => "required|in:pending,in_progress,paused,completed",
            ]);

            // Update the task status
            $todo->status = $validated["status"];
            $todo->save();

            return response()->json([
                "success" => true,
                "message" => "Task status updated successfully",
                "todo" => $todo,
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating task status: " . $e->getMessage());
            return response()->json(
                ["error" => "Error updating task status: " . $e->getMessage()],
                500
            );
        }
    }
}
