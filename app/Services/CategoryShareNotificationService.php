<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\Models\ShareRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class CategoryShareNotificationService
{
    protected $slackNotifyService;

    public function __construct(SlackNotifyService $slackNotifyService)
    {
        $this->slackNotifyService = $slackNotifyService;
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
        // Slack認証が必要なため、Slack webhook URLが設定されているかチェック
        if (!$recipient->slack_webhook_url) {
            Log::warning(
                "Recipient has no Slack webhook URL: {$recipient->email}"
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

        // Slack Notifyを使用して通知を送信
        try {
            $success = $this->slackNotifyService->send(
                $recipient->slack_webhook_url,
                $message
            );

            if (!$success) {
                Log::error(
                    "Failed to send Slack notification for category share request",
                    [
                        "error" => $this->slackNotifyService->getLastError(),
                        "recipient" => $recipient->email,
                        "category" => $category->name,
                    ]
                );
            }

            return $success;
        } catch (\Exception $e) {
            Log::error(
                "Exception sending Slack notification: " . $e->getMessage()
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

        $message = "\n*【カテゴリー共有リクエスト】*\n\n";
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
