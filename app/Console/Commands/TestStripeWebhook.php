<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class TestStripeWebhook extends Command
{
    protected $signature = "stripe:test-webhook {event? : イベントタイプ (例: checkout.session.completed)}";
    protected $description = "Stripeウェブフック処理のテスト";

    public function handle()
    {
        $eventType = $this->argument("event") ?? "checkout.session.completed";
        $this->info("テストイベント: {$eventType}");

        // テスト用のユーザーを取得
        $user = User::first();
        if (!$user) {
            $this->error("テスト用ユーザーが見つかりません。");
            return Command::FAILURE;
        }

        // ユーザーにStripe IDが設定されていることを確認
        if (!$user->stripe_id) {
            $this->warn(
                "ユーザーにStripe IDが設定されていません。テスト用IDを一時的に設定します。"
            );
            $user->stripe_id = "cus_test_" . time();
            $user->save();
        }

        // イベントタイプ別のモックデータ
        $mockData = $this->getMockData($eventType, $user->stripe_id);

        // ウェブフックコントローラーを呼び出す
        $controller = app()->make(
            \App\Http\Controllers\StripSubscriptionController::class
        );

        // リクエストを作成
        $request = request();
        $request->headers->set("Stripe-Signature", "test_signature");
        $request->initialize([], [], [], [], [], [], json_encode($mockData));

        $this->info(
            "モックデータの生成に成功しました。ログを確認してください。"
        );
        Log::info("ウェブフックテスト", [
            "event" => $eventType,
            "data" => $mockData,
        ]);

        // ウェブフックメソッドを直接呼び出す
        try {
            // webhookメソッドをリフレクションで直接呼び出す（署名検証をスキップ）
            $reflectionMethod = new \ReflectionMethod(
                $controller,
                "handleCustomerSubscriptionUpdate"
            );
            $reflectionMethod->setAccessible(true);
            $result = $reflectionMethod->invoke(
                $controller,
                (object) $mockData["data"]["object"]
            );

            $this->info("処理結果: " . json_encode($result->original));
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("エラー: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function getMockData($eventType, $customerId)
    {
        $subscriptionId = "sub_test_" . time();

        switch ($eventType) {
            case "checkout.session.completed":
                return [
                    "id" => "evt_test_checkout",
                    "type" => "checkout.session.completed",
                    "data" => [
                        "object" => [
                            "id" => "cs_test_" . time(),
                            "customer" => $customerId,
                            "mode" => "subscription",
                            "subscription" => $subscriptionId,
                            "status" => "complete",
                        ],
                    ],
                ];

            case "customer.subscription.created":
            case "customer.subscription.updated":
                return [
                    "id" => "evt_test_subscription",
                    "type" => $eventType,
                    "data" => [
                        "object" => [
                            "id" => $subscriptionId,
                            "customer" => $customerId,
                            "status" => "active",
                            "items" => [
                                "data" => [
                                    [
                                        "id" => "si_test_" . time(),
                                        "price" => [
                                            "id" => "price_test_" . time(),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];

            default:
                return [
                    "id" => "evt_test_unknown",
                    "type" => $eventType,
                    "data" => [
                        "object" => [
                            "id" => "unknown_" . time(),
                            "customer" => $customerId,
                        ],
                    ],
                ];
        }
    }
}
