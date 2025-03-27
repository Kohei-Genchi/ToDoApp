<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleCalendarService;

class GoogleCalendarController extends Controller
{
    protected $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Googleアカウント認証用のURLを生成してリダイレクト
     */
    public function redirectToGoogle()
    {
        try {
            // サブスクリプションチェック（カレンダー連携はサブスクリプションが必要）
            if (!Auth::user()->subscription_id) {
                return response()->json(
                    [
                        "error" =>
                            "このサービスを利用するにはサブスクリプションが必要です。",
                    ],
                    403
                );
            }

            $authUrl = $this->googleCalendarService->getAuthUrl();
            return response()->json(["auth_url" => $authUrl]);
        } catch (\Exception $e) {
            Log::error("Google認証URLの生成に失敗: " . $e->getMessage());
            return response()->json(
                ["error" => "認証の準備に失敗しました"],
                500
            );
        }
    }

    /**
     * Google認証後のコールバック処理
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            // 認証コードを取得
            $code = $request->code;
            if (!$code) {
                return redirect()
                    ->route("todos.index")
                    ->with("error", "Google認証に失敗しました");
            }

            // 認証コードをトークンに交換し、ユーザーのトークンを保存
            $this->googleCalendarService->handleAuthCallback($code);

            return redirect()
                ->route("todos.index")
                ->with("success", "Googleカレンダーとの連携に成功しました");
        } catch (\Exception $e) {
            Log::error("Google認証コールバック処理に失敗: " . $e->getMessage());
            return redirect()
                ->route("todos.index")
                ->with(
                    "error",
                    "Googleカレンダー連携に失敗: " . $e->getMessage()
                );
        }
    }

    /**
     * 認証状態の取得
     */
    public function getAuthStatus()
    {
        try {
            $user = Auth::user();
            $isConnected = $this->googleCalendarService->isConnected($user);

            $data = [
                "is_authenticated" => $isConnected,
                "last_sync" => $user->google_calendar_last_sync,
            ];

            if ($isConnected) {
                $data["email"] = $user->google_email;
            }

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error("認証状態の取得に失敗: " . $e->getMessage());
            return response()->json(
                ["error" => "認証状態の取得に失敗しました"],
                500
            );
        }
    }

    /**
     * 利用可能なカレンダー一覧を取得
     */
    public function getCalendars()
    {
        try {
            // サブスクリプションチェック
            if (!Auth::user()->subscription_id) {
                return response()->json(
                    [
                        "error" =>
                            "このサービスを利用するにはサブスクリプションが必要です。",
                    ],
                    403
                );
            }

            $calendars = $this->googleCalendarService->listCalendars();
            return response()->json($calendars);
        } catch (\Exception $e) {
            Log::error("カレンダー一覧の取得に失敗: " . $e->getMessage());
            return response()->json(
                ["error" => "カレンダー一覧の取得に失敗しました"],
                500
            );
        }
    }

    /**
     * タスクとGoogleカレンダーを同期
     */
    public function syncCalendar(Request $request)
    {
        try {
            // バリデーション
            $validated = $request->validate([
                "calendar_id" => "required|string",
                "sync_to_google" => "boolean",
                "sync_from_google" => "boolean",
                "sync_completion" => "boolean",
            ]);

            // サブスクリプションチェック
            if (!Auth::user()->subscription_id) {
                return response()->json(
                    [
                        "error" =>
                            "このサービスを利用するにはサブスクリプションが必要です。",
                    ],
                    403
                );
            }

            // 同期処理の実行
            $result = $this->googleCalendarService->synchronize(
                $validated["calendar_id"],
                $validated["sync_to_google"] ?? true,
                $validated["sync_from_google"] ?? true,
                $validated["sync_completion"] ?? true
            );

            // 最終同期時間を更新
            $user = Auth::user();
            $user->google_calendar_last_sync = now();
            $user->save();

            return response()->json([
                "success" => true,
                "message" => "同期が完了しました",
                "result" => $result,
            ]);
        } catch (\Exception $e) {
            Log::error("カレンダー同期に失敗: " . $e->getMessage());
            return response()->json(
                [
                    "error" =>
                        "カレンダー同期に失敗しました: " . $e->getMessage(),
                ],
                500
            );
        }
    }

    /**
     * Google連携を解除
     */
    public function disconnect()
    {
        try {
            $user = Auth::user();

            // トークンの削除
            $this->googleCalendarService->revokeToken($user);

            // ユーザー情報の更新
            $user->google_refresh_token = null;
            $user->google_access_token = null;
            $user->google_token_expires = null;
            $user->google_email = null;
            $user->google_calendar_id = null;
            $user->google_calendar_last_sync = null;
            $user->save();

            return response()->json([
                "success" => true,
                "message" => "Google連携を解除しました",
            ]);
        } catch (\Exception $e) {
            Log::error("Google連携解除に失敗: " . $e->getMessage());
            return response()->json(["error" => "連携解除に失敗しました"], 500);
        }
    }
}
