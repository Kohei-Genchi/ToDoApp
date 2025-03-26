<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\Models\ShareRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class CategoryShareNotificationService
{
    protected $lineNotifyService;

    public function __construct(LineNotifyService $lineNotifyService)
    {
        $this->lineNotifyService = $lineNotifyService;
    }

    /**
     * カテゴリー共有リクエストの通知を送信
     *
     * @param User $recipient 受信者
     * @param User $requester 送信者
     * @param ShareRequest $shareRequest 共有リクエスト
     * @param Category $category 共有するカテゴリー
     * @return bool 送信成功したかどうか
     */
    public function sendCategoryShareRequestNotification(
        User $recipient,
        User $requester,
        ShareRequest $shareRequest,
        Category $category
    ): bool {
        // LINE認証が必要なため、LINE通知トークンが設定されているかチェック
        if (!$recipient->line_notify_token) {
            Log::warning(
                "Recipient has no Line Notify token: {$recipient->email}"
            );
            return false;
        }

        // 承認・拒否用のURLを生成（署名付きリンク）
        $approveUrl = URL::signedRoute("category-share.approve", [
            "token" => $shareRequest->token,
        ]);

        $rejectUrl = URL::signedRoute("category-share.reject", [
            "token" => $shareRequest->token,
        ]);

        // 通知メッセージの作成
        $message = $this->buildCategoryShareMessage(
            $requester,
            $category,
            $shareRequest,
            $approveUrl,
            $rejectUrl
        );

        // LINE Notifyを使用して通知を送信
        try {
            $success = $this->lineNotifyService->send(
                $recipient->line_notify_token,
                $message
            );

            if (!$success) {
                Log::error(
                    "Failed to send Line notification for category share request",
                    [
                        "error" => $this->lineNotifyService->getLastError(),
                        "recipient" => $recipient->email,
                        "category" => $category->name,
                    ]
                );
            }

            return $success;
        } catch (\Exception $e) {
            Log::error(
                "Exception sending Line notification: " . $e->getMessage()
            );
            return false;
        }
    }

    /**
     * カテゴリー共有リクエストのメッセージを作成
     */
    protected function buildCategoryShareMessage(
        User $requester,
        Category $category,
        ShareRequest $shareRequest,
        string $approveUrl,
        string $rejectUrl
    ): string {
        $permissionText =
            $shareRequest->permission === "edit" ? "編集可能" : "閲覧のみ";

        $message = "\n【カテゴリー共有リクエスト】\n\n";
        $message .= "{$requester->name}さんがカテゴリー「{$category->name}」を共有しようとしています。\n\n";
        $message .= "このカテゴリーに属するすべてのタスクが共有されます。\n";
        $message .= "権限: {$permissionText}\n";
        $message .= "有効期限: {$shareRequest->expires_at->format(
            "Y-m-d H:i"
        )}\n\n";
        $message .= "承認するには下のリンクをクリックしてください:\n";
        $message .= "{$approveUrl}\n\n";
        $message .= "拒否するには下のリンクをクリックしてください:\n";
        $message .= "{$rejectUrl}\n";

        return $message;
    }
}
