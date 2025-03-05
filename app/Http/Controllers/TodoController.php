<?php

namespace App\Http\Controllers;

use App\Http\Requests\TodoRequest;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class TodoController extends Controller
{
    public function index()
    {
        // Debug what's happening
        Log::info('TodoController index accessed. Is authenticated: ' . (Auth::check() ? 'Yes' : 'No'));

        // This is the main entry point, needs to handle both web and API requests
        // For web requests, it should show the main todos page
        // For API requests, it should handle authentication appropriately

        if (request()->expectsJson()) {
            try {
                // For API requests, return empty data for unauthenticated users
                if (!Auth::check()) {
                    Log::info('Unauthenticated access to todos API - returning empty data');
                    return response()->json([]);
                }

                $user = Auth::user();
                Log::info('Fetching categories for user: ' . $user->id);

                // Return user's categories for API requests
                $categories = $user->categories()->orderBy('name')->get();
                return response()->json($categories);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error fetching data: ' . $e->getMessage()
                ], 500);
            }
        } else {
            // For web requests, render the main todos view
            // If not authenticated, the view should handle login prompts
            return view('todos.index');
        }
    }

    public function store(Request $request)
    {
        // Handle authentication - return empty array for API and redirect for web
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                // Return 200 for API with empty data to prevent frontend errors
                return response()->json([], 200);
            } else {
                return redirect()->route('login');
            }
        }

        $user = Auth::user();

        // Validate the request
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high',
            'recurrence_type' => 'nullable|in:none,daily,weekly,monthly',
            'recurrence_end_date' => 'nullable|date|after_or_equal:due_date',
        ]);

        $data = $validated;
        $data["user_id"] = $user->id;

        if (isset($data["due_date"])) {
            $data["location"] =
                $data["due_date"] === now()->format("Y-m-d")
                    ? "TODAY"
                    : "SCHEDULED";
        } else {
            $data["location"] = "INBOX";
            $data["due_date"] = null;
            $data["due_time"] = null;
        }

        $todo = Todo::create($data);

        if (
            !empty($data["recurrence_type"]) &&
            $data["recurrence_type"] !== "none"
        ) {
            $this->createRecurringTasks($data);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'タスクを追加しました',
                'todo' => $todo
            ], 201);
        }

        return back()->with("success", "タスクを追加しました");
    }

    public function update(Request $request, Todo $todo)
    {
        Log::info('Update method called for todo ID: ' . $todo->id);

        // Handle authentication - return empty array for API and redirect for web
        if (!Auth::check()) {
            Log::warning('Unauthenticated access attempt to update todo ' . $todo->id);
            if ($request->expectsJson()) {
                // Return 200 for API with empty data to prevent frontend errors
                return response()->json([], 200);
            } else {
                return redirect()->route('login');
            }
        }

        // Validate the request
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high',
            'recurrence_type' => 'nullable|in:none,daily,weekly,monthly',
            'recurrence_end_date' => 'nullable|date|after_or_equal:due_date',
        ]);

        $data = $validated;

        // Set location based on due date
        if (isset($data["due_date"])) {
            $data["location"] =
                $data["due_date"] === now()->format("Y-m-d")
                    ? "TODAY"
                    : "SCHEDULED";
        } else {
            $data["location"] = "INBOX";
            $data["due_date"] = null;
            $data["due_time"] = null;
        }

        $todo->update($data);

        // Handle recurring tasks
        if (
            !empty($data["recurrence_type"]) &&
            $data["recurrence_type"] !== "none"
        ) {
            // Add user_id to data for createRecurringTasks
            $data["user_id"] = $todo->user_id;
            $this->createRecurringTasks($data);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'タスクを更新しました',
                'todo' => $todo->fresh()
            ]);
        }

        return back()->with("success", "タスクを更新しました");
    }

    public function restore(Request $request, Todo $todo)
    {
        // Handle authentication - return empty array for API and redirect for web
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                // Return 200 for API with empty data to prevent frontend errors
                return response()->json([], 200);
            } else {
                return redirect()->route('login');
            }
        }

        if ($todo->status !== "trashed") {
            return $request->expectsJson()
                ? response()->json(['error' => 'ゴミ箱にあるタスクのみ復元できます'], 400)
                : back()->with("error", "ゴミ箱にあるタスクのみ復元できます");
        }

        // 期限がある場合は、その日付に復元（今日の場合はTODAY、それ以外はSCHEDULED）
        if ($todo->due_date) {
            if ($todo->due_date->isToday()) {
                $todo->location = "TODAY";
            } else {
                $todo->location = "SCHEDULED";
            }
        } else {
            // 期限がない場合はINBOXに復元
            $todo->location = "INBOX";
        }

        $todo->status = "pending";
        $todo->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'タスクを復元しました',
                'todo' => $todo->fresh()
            ]);
        }

        return back()->with("success", "タスクを復元しました");
    }

    public function toggle(Todo $todo, Request $request)
    {
        // Handle authentication - return empty array for API and redirect for web
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                // Return 200 for API with empty data to prevent frontend errors
                return response()->json([], 200);
            } else {
                return redirect()->route('login');
            }
        }

        $todo->status = $todo->status === "completed" ? "pending" : "completed";
        $todo->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'タスクのステータスを更新しました',
                'todo' => $todo->fresh()
            ]);
        }

        return back();
    }

    public function trash(Todo $todo, Request $request)
    {
        // Handle authentication - return empty array for API and redirect for web
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                // Return 200 for API with empty data to prevent frontend errors
                return response()->json([], 200);
            } else {
                return redirect()->route('login');
            }
        }

        $todo->status = "trashed";
        $todo->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'タスクをゴミ箱に移動しました'
            ]);
        }

        return back()->with("success", "タスクをゴミ箱に移動しました");
    }

    public function trashed(): RedirectResponse
    {
        // This method is no longer needed as we're using the API endpoint trashedApi instead
        // Vue.js will handle the trash view using the API
        return redirect()->route('todos.index');
    }

    public function destroy(Todo $todo, Request $request)
    {
        // Handle authentication - return empty array for API and redirect for web
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                // Return 200 for API with empty data to prevent frontend errors
                return response()->json([], 200);
            } else {
                return redirect()->route('login');
            }
        }

        // Check if we're deleting all recurring tasks too
        if (
            $request->has("delete_recurring") &&
            $todo->recurrence_type &&
            $todo->recurrence_type !== "none"
        ) {
            // This could be expanded to delete all related recurring tasks based on some pattern
            // For now, we'll just delete this specific task
            $todo->delete();

            if ($request->expectsJson()) {
                return response()->json(['message' => '繰り返しタスクを削除しました']);
            }

            return back()->with("success", "繰り返しタスクを削除しました");
        }

        $todo->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'タスクを完全に削除しました']);
        }

        return back()->with("success", "タスクを完全に削除しました");
    }

    public function emptyTrash(Request $request)
    {
        try {
            // Handle authentication - return empty array for API and redirect for web
            if (!Auth::check()) {
                if ($request->expectsJson()) {
                    // Return 200 for API with empty data to prevent frontend errors
                    return response()->json([], 200);
                } else {
                    return redirect()->route('login');
                }
            }

            $user = Auth::user();
            $user->todos()->where("status", "trashed")->delete();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'ゴミ箱を空にしました']);
            }

            return back()->with("success", "ゴミ箱を空にしました");
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Error emptying trash: ' . $e->getMessage()
                ], 500);
            }

            return back()->with("error", "ゴミ箱を空にできませんでした: " . $e->getMessage());
        }
    }

    /**
     * API用のメソッド
     */

    // API用のタスク一覧取得
    public function apiIndex(Request $request): JsonResponse
    {
        // Default to 'today' view if none specified
        $view = $request->view ?? "today";
        $date = $request->date ? now()->parse($request->date) : now();

        try {
            // Handle authentication - return empty array for API
            if (!Auth::check()) {
                Log::info('Unauthenticated access to todos API - returning empty array');
                return response()->json([]);
            }

            $user = Auth::user();
            Log::info('Fetching todos for user: ' . $user->id);

            // Normal query logic continues...
            $query = $user->todos()
                ->with("category")
                ->where("status", "!=", "trashed");

            // Add view-specific filtering
            switch ($view) {
                case "today":
                    $query->where("location", "TODAY");
                    break;
                case "scheduled":
                    $query->where("location", "SCHEDULED");
                    break;
                case "inbox":
                    $query->where("location", "INBOX");
                    break;
                case "completed":
                    $query->where("status", "completed");
                    break;
                case "all":
                    // No additional filters needed
                    break;
                case "date":
                    // Filter by specific date
                    $query->whereDate("due_date", $date->format('Y-m-d'));
                    break;
            }

            $todos = $query->orderBy("created_at", "desc")->get();
            return response()->json($todos);
        }
        catch (\Exception $e) {
            return response()->json([
                'error' => 'Error loading tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    // タスクをゴミ箱に移動（API用）
    public function moveToTrash(Todo $todo): JsonResponse
    {
        // Handle authentication - return empty array for API
        if (!Auth::check()) {
            Log::info('Unauthenticated access to moveToTrash API - returning empty array');
            return response()->json([]);
        }

        $todo->status = "trashed";
        $todo->save();

        return response()->json(['message' => 'タスクをゴミ箱に移動しました']);
    }

    // ゴミ箱内のタスク一覧（API用）
    public function trashedApi(): JsonResponse
    {
        try {
            // Handle authentication - return empty array for API
            if (!Auth::check()) {
                Log::info('Unauthenticated access to trashedApi - returning empty array');
                return response()->json([]);
            }

            $user = Auth::user();
            $todos = $user->todos()
                ->with("category")
                ->where("status", "trashed")
                ->get();

            return response()->json($todos);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error loading trashed tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified todo.
     */
    public function show(Todo $todo)
    {
        // Handle authentication - return empty array for API and redirect for web
        if (!Auth::check()) {
            if (request()->expectsJson()) {
                // Return 200 for API with empty data to prevent frontend errors
                return response()->json([]);
            } else {
                return redirect()->route('login');
            }
        }

        if (request()->expectsJson()) {
            return response()->json($todo->load('category'));
        }

        return view('todos.show', compact('todo'));
    }

    private function createRecurringTasks(array $data): void
    {
        if (empty($data["due_date"])) {
            return;
        }

        // 開始日を取得
        $startDate = now()->parse($data["due_date"]);

        // 終了日を設定（指定されていない場合は1ヶ月後）
        $endDate = !empty($data["recurrence_end_date"])
            ? now()->parse($data["recurrence_end_date"])
            : $startDate->copy()->addMonths(1);

        // 開始日が終了日と同じかそれ以降の場合は何もしない
        if ($startDate->greaterThanOrEqualTo($endDate)) {
            return;
        }

        // 繰り返し日付の生成
        $dates = [];

        // 開始日そのものからスタート（翌日からではなく）
        $currentDate = $startDate->copy();

        // 最初のタスクは別途作成されるので、初回分をスキップして2回目以降を生成
        switch ($data["recurrence_type"]) {
            case "daily":
                // 初回の日付から1日進める
                $currentDate->addDay();
                while ($currentDate->lessThanOrEqualTo($endDate)) {
                    $dates[] = $currentDate->copy();
                    $currentDate->addDay();
                }
                break;

            case "weekly":
                // 初回の日付から1週間進める
                $currentDate->addWeek();
                while ($currentDate->lessThanOrEqualTo($endDate)) {
                    $dates[] = $currentDate->copy();
                    $currentDate->addWeek();
                }
                break;

            case "monthly":
                // 初回の日付から1ヶ月進める
                $currentDate->addMonth();
                while ($currentDate->lessThanOrEqualTo($endDate)) {
                    $dates[] = $currentDate->copy();
                    $currentDate->addMonth();
                }
                break;
        }

        // 各日付に対してタスクを作成
        foreach ($dates as $date) {
            $taskData = $data;
            $taskData["due_date"] = $date->format("Y-m-d");
            $taskData["location"] = $date->isToday() ? "TODAY" : "SCHEDULED";

            unset(
                $taskData["recurrence_type"],
                $taskData["recurrence_end_date"]
            );
            Todo::create($taskData);
        }
    }
}
