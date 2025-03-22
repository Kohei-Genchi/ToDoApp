<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlobalShare;
use App\Models\User;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GlobalShareController extends Controller
{
    //標準的なAPIレスポンスを生成する共通メソッド
    private function successResponse(
        $data,
        string $message = "",
        int $status = 200
    ): JsonResponse {
        return response()->json(
            [
                "success" => true,
                "message" => $message,
                "data" => $data,
            ],
            $status
        );
    }

    //エラーレスポンスを生成する共通メソッド
    private function errorResponse(
        \Exception $e,
        string $context,
        int $status = 500
    ): JsonResponse {
        $message = "Error $context: " . $e->getMessage();
        Log::error($message);

        return response()->json(
            [
                "success" => false,
                "error" => $message,
            ],
            $status
        );
    }

    //現在のユーザーが所有者であるか確認
    private function authorizeOwner(GlobalShare $globalShare)
    {
        if ($globalShare->user_id !== Auth::id()) {
            return response()->json(
                [
                    "success" => false,
                    "error" =>
                        "認証エラー：このグローバル共有の権限がありません",
                ],
                403
            );
        }

        return true;
    }

    // 共有相手一覧を返す
    public function index(): JsonResponse
    {
        try {
            // ユーザーがグローバル共有している相手を取得し、必要な情報にマッピング
            $globalShares = Auth::user()
                ->globallySharedWith()
                ->with("sharedWithUser")
                ->get()
                ->map(function ($share) {
                    return [
                        "id" => $share->id,
                        "user_id" => $share->shared_with_user_id,
                        "name" => $share->sharedWithUser->name,
                        "email" => $share->sharedWithUser->email,
                        "permission" => $share->permission,
                    ];
                });

            return response()->json($globalShares);
        } catch (\Exception $e) {
            return $this->errorResponse($e, "retrieving global shares");
        }
    }

    // /新規共有を作成（同じ相手との重複や自分自身との共有を防止）
    public function store(Request $request): JsonResponse
    {
        // リクエストの検証
        $request->validate([
            "email" => "required|email|exists:users,email",
            "permission" => "required|in:view,edit",
        ]);

        try {
            // メールアドレスからユーザーを検索
            $user = User::where("email", $request->email)->first();

            // 自分自身との共有は許可しない
            if ($user->id === Auth::id()) {
                return response()->json(
                    ["error" => "自分自身と共有することはできません"],
                    400
                );
            }

            // すでに共有済みかチェック
            $existingShare = GlobalShare::where("user_id", Auth::id())
                ->where("shared_with_user_id", $user->id)
                ->first();

            if ($existingShare) {
                return response()->json(
                    ["error" => "すでにこのユーザーとグローバル共有しています"],
                    400
                );
            }

            // グローバル共有を作成
            $globalShare = GlobalShare::create([
                "user_id" => Auth::id(),
                "shared_with_user_id" => $user->id,
                "permission" => $request->permission,
            ]);

            return $this->successResponse(
                [
                    "user" => [
                        "id" => $user->id,
                        "name" => $user->name,
                        "email" => $user->email,
                        "permission" => $request->permission,
                    ],
                ],
                "すべてのタスクをユーザーとグローバル共有しました",
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e, "sharing globally");
        }
    }

    //権限を更新（所有者のみ可能）
    public function update(
        Request $request,
        GlobalShare $globalShare
    ): JsonResponse {
        // 権限チェック
        $authCheck = $this->authorizeOwner($globalShare);
        if ($authCheck !== true) {
            return $authCheck;
        }

        // リクエストの検証
        $request->validate([
            "permission" => "required|in:view,edit",
        ]);

        try {
            // 権限を更新
            $globalShare->update([
                "permission" => $request->permission,
            ]);

            return $this->successResponse(
                null,
                "グローバル共有の権限を正常に更新しました"
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e,
                "updating global sharing permission"
            );
        }
    }

    //共有を削除（所有者のみ可能）
    public function destroy(GlobalShare $globalShare): JsonResponse
    {
        // 権限チェック
        $authCheck = $this->authorizeOwner($globalShare);
        if ($authCheck !== true) {
            return $authCheck;
        }

        try {
            $globalShare->delete();

            return $this->successResponse(
                null,
                "グローバル共有を正常に削除しました"
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e, "removing global sharing");
        }
    }

    // 自分に共有されているタスク一覧を返す
    public function sharedWithMe(): JsonResponse
    {
        try {
            // 認証済みユーザーとグローバル共有しているユーザーIDを取得
            $sharerIds = Auth::user()
                ->globallySharedBy()
                ->pluck("user_id")
                ->toArray();

            // それらのユーザーからのすべてのタスクを取得
            $sharedTasks = Todo::whereIn("user_id", $sharerIds)
                ->with(["category", "user"])
                ->get()
                ->map(function ($task) {
                    // 権限を決定するためのグローバル共有を検索
                    $globalShare = Auth::user()
                        ->globallySharedBy()
                        ->where("user_id", $task->user_id)
                        ->first();

                    // タスクにピボット情報を追加（権限情報を含む）
                    $task->pivot = [
                        "permission" => $globalShare
                            ? $globalShare->permission
                            : "view",
                    ];

                    return $task;
                });

            return response()->json($sharedTasks);
        } catch (\Exception $e) {
            return $this->errorResponse($e, "retrieving globally shared tasks");
        }
    }
}
