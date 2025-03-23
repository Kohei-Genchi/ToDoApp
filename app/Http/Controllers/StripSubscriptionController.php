<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Stripe\Webhook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Log;
/**
 * =========================================
 *  Stripe サブスク　コントローラー
 * =========================================
 */
class StripSubscriptionController extends Controller
{
    /**
     * サブスク申請ページ(チェックアウトに進む前のページ)
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view("stripe.subscription.index");
    }

    /**
     * 支払い完了
     *
     * @return \Illuminate\View\View
     */
    public function comp(Request $request)
    {
        // セッションIDがURLクエリパラメータに含まれている場合（Stripeからのリダイレクト）
        if ($request->has("session_id")) {
            try {
                $sessionId = $request->session_id;
                Log::info(
                    "Stripeチェックアウト完了ページ - セッションID: " .
                        $sessionId
                );

                // APIキーを設定
                Stripe::setApiKey(config("stripe.secret_key"));

                // セッション情報を取得
                $session = Session::retrieve($sessionId);

                Log::info("セッション情報を取得しました", [
                    "session_id" => $sessionId,
                    "mode" => $session->mode,
                    "subscription" => $session->subscription ?? "none",
                    "customer" => $session->customer ?? "none"
                ]);

                // サブスクリプションIDが含まれている場合
                if (
                    $session->mode === "subscription" &&
                    isset($session->subscription)
                ) {
                    $user = Auth::user();
                    if ($user) {
                        // トランザクションを開始
                        DB::beginTransaction();
                        try {
                            $user->subscription_id = $session->subscription;
                            $user->stripe_id = $session->customer;
                            $result = $user->save();
                            DB::commit();

                            Log::info("サブスクリプション直接更新", [
                                "user_id" => $user->id,
                                "subscription_id" => $session->subscription,
                                "stripe_id" => $session->customer,
                                "result" => $result ? "成功" : "失敗"
                            ]);

                            // 保存後に確認
                            $updatedUser = User::find($user->id);
                            Log::info("保存後のユーザー情報", [
                                "user_id" => $updatedUser->id,
                                "subscription_id" => $updatedUser->subscription_id,
                                "stripe_id" => $updatedUser->stripe_id
                            ]);

                            // 保存されていない場合は直接クエリで更新
                            if ($updatedUser->subscription_id !== $session->subscription) {
                                Log::warning("サブスクリプションIDが保存されていません。直接クエリで更新します。");
                                DB::table('users')
                                    ->where('id', $user->id)
                                    ->update([
                                        'subscription_id' => $session->subscription,
                                        'stripe_id' => $session->customer
                                    ]);
                            }
                        } catch (\Exception $innerEx) {
                            DB::rollBack();
                            Log::error("トランザクション内でのエラー", [
                                "error" => $innerEx->getMessage(),
                                "file" => $innerEx->getFile(),
                                "line" => $innerEx->getLine()
                            ]);

                            // エラー時も直接クエリで更新を試みる
                            try {
                                DB::table('users')
                                    ->where('id', $user->id)
                                    ->update([
                                        'subscription_id' => $session->subscription,
                                        'stripe_id' => $session->customer
                                    ]);
                                Log::info("エラー後の直接クエリによる更新を試みました");
                            } catch (\Exception $queryEx) {
                                Log::error("直接クエリによる更新も失敗", [
                                    "error" => $queryEx->getMessage()
                                ]);
                            }
                        }
                    } else {
                        Log::error("認証済みユーザーが見つかりません");
                    }
                } else {
                    Log::warning("セッションにサブスクリプション情報が含まれていません", [
                        "mode" => $session->mode,
                        "has_subscription" => isset($session->subscription)
                    ]);
                }
            } catch (\Exception $e) {
                Log::error(
                    "チェックアウト完了ページでのサブスクリプション処理エラー",
                    [
                        "error" => $e->getMessage(),
                        "file" => $e->getFile(),
                        "line" => $e->getLine(),
                        "session_id" => $request->session_id ?? "none",
                    ]
                );
            }
        } else {
            Log::warning("セッションIDがリクエストに含まれていません");
        }

        return view("stripe.subscription.comp");
    }

    /* ~ */
    /**
     * checkout
     *
     * @return \Illuminate\Http\Response
     */
    public function checkout()
    {
        try {
            Log::info("Stripeチェックアウトプロセスを開始します");

            // Stripe設定が存在するか確認
            $stripe_secret_key = config("stripe.secret_key");
            $price_id = config("stripe.price_id");

            if (empty($stripe_secret_key) || empty($price_id)) {
                Log::error(
                    "Stripe設定が不足しています: secret_keyまたはprice_idが空です"
                );
                return redirect()
                    ->route("stripe.subscription")
                    ->with(
                        "error",
                        "Stripe設定が見つかりません。管理者にお問い合わせください。"
                    );
            }

            // シークレットキーの設定
            Stripe::setApiKey($stripe_secret_key);

            // 顧客情報を取得
            $user = Auth::user();
            if (!$user) {
                Log::error(
                    "チェックアウト中に認証済みユーザーが見つかりません"
                );
                return redirect()
                    ->route("login")
                    ->with("error", "ログインしてください。");
            }

            $customer = $user->createOrGetStripeCustomer();
            $user->stripe_id = $customer->id;
            $user->save();
            Log::info("Stripeカスタマーを取得しました", [
                "customer_id" => $customer->id,
                "user_id" => $user->id,
            ]);

            // チェックアウトセッション作成
            Log::info("チェックアウトセッションを作成します", [
                "price_id" => $price_id,
            ]);

            $checkout_session = Session::create([
                "customer" => $customer->id,
                "customer_update" => ["address" => "auto"],
                "payment_method_types" => ["card"],
                "metadata" => [
                    "user_id" => $user->id,
                    "email" => $user->email,
                    "app" => "TodoList"
                ],

                "line_items" => [
                    [
                        "price" => $price_id,
                        "quantity" => 1,
                    ],
                ],

                "payment_method_options" => [
                    "card" => [
                        "request_three_d_secure" => "any",
                    ],
                ],

                "mode" => "subscription",
                "success_url" => route("stripe.subscription.comp") . "?session_id={CHECKOUT_SESSION_ID}",
                "cancel_url" => route("stripe.subscription"),
            ]);

            Log::info("チェックアウトセッションが正常に作成されました", [
                "session_id" => $checkout_session->id,
                "checkout_url" => $checkout_session->url,
            ]);

            return redirect()->away($checkout_session->url);
        } catch (\Exception $e) {
            Log::error("Stripeチェックアウトエラー", [
                "message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
            ]);

            return redirect()
                ->route("stripe.subscription")
                ->with(
                    "error",
                    "Stripeとの通信中にエラーが発生しました：" .
                        $e->getMessage()
                );
        }
    }

    /**
     * カスタマーポータル
     *
     * @return \Illuminate\Http\Response
     */
    public function customer_portal()
    {
        Stripe::setApiKey(config("stripe.secret_key"));

        # 顧客情報
        $user = Auth::user();
        $customer = $user->createOrGetStripeCustomer();

        # Stripeクライアントを初期化
        $stripe = new StripeClient(config("stripe.secret_key"));

        # カスタマーポータルセッションを作成
        $session = $stripe->billingPortal->sessions->create([
            "customer" => $customer->id,
            "return_url" => route("home"), // ポータルを終了した後にリダイレクト
        ]);

        # カスタマーポータルへのリダイレクト
        return redirect($session->url);
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header("Stripe-Signature");
        $endpointSecret = config("stripe.subscription_endpoint_secret");

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\Exception $e) {
            Log::error("Webhook検証エラー: " . $e->getMessage());
            return response()->json(["error" => $e->getMessage()], 400);
        }

        // イベントデータをログに記録（デバッグ用）
        Log::info("Stripeウェブフックイベント受信", [
            "type" => $event->type,
            "object" => $event->data->object,
        ]);

        $eventObject = $event->data->object;

        switch ($event->type) {
            case "checkout.session.completed":
                return $this->handleCheckoutSessionCompleted($eventObject);

            case "customer.subscription.created":
            case "customer.subscription.updated":
                return $this->handleCustomerSubscriptionUpdate($eventObject);

            case "customer.subscription.deleted":
                return $this->handleCustomerSubscriptionDeleted($eventObject);

            default:
                Log::info("未処理のイベントタイプ", ["type" => $event->type]);
                return response()->json(["status" => "received"], 200);
        }
    }

    private function handleCheckoutSessionCompleted($session)
    {
        Log::info("チェックアウトセッション完了", [
            "session_id" => $session->id,
            "customer" => $session->customer,
            "mode" => $session->mode,
            "subscription" => $session->subscription ?? "none",
        ]);

        // サブスクリプションモードの場合のみ処理
        if (
            $session->mode === "subscription" &&
            isset($session->subscription)
        ) {
            $subscriptionId = $session->subscription;
            $customerId = $session->customer;

            // ユーザーを検索
            $user = User::find($session->metadata['user_id']);
            if ($user) {
                // トランザクションを開始
                DB::beginTransaction();
                try {
                    $user->subscription_id = $subscriptionId;
                    $user->stripe_id = $customerId;
                    $result = $user->save();
                    DB::commit();

                    Log::info(
                        "チェックアウトからユーザーサブスクリプションを更新",
                        [
                            "user_id" => $user->id,
                            "subscription_id" => $subscriptionId,
                            "stripe_id" => $customerId,
                            "result" => $result ? "成功" : "失敗"
                        ]
                    );

                    // 保存後に確認
                    $updatedUser = User::find($user->id);
                    Log::info("保存後のユーザー情報", [
                        "user_id" => $updatedUser->id,
                        "subscription_id" => $updatedUser->subscription_id,
                        "stripe_id" => $updatedUser->stripe_id
                    ]);

                    // 保存されていない場合は直接クエリで更新
                    if ($updatedUser->subscription_id !== $subscriptionId) {
                        Log::warning("サブスクリプションIDが保存されていません。直接クエリで更新します。");
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'subscription_id' => $subscriptionId,
                                'stripe_id' => $customerId
                            ]);
                    }
                } catch (\Exception $innerEx) {
                    DB::rollBack();
                    Log::error("トランザクション内でのエラー", [
                        "error" => $innerEx->getMessage(),
                        "file" => $innerEx->getFile(),
                        "line" => $innerEx->getLine()
                    ]);

                    // エラー時も直接クエリで更新を試みる
                    try {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'subscription_id' => $subscriptionId,
                                'stripe_id' => $customerId
                            ]);
                        Log::info("エラー後の直接クエリによる更新を試みました");
                    } catch (\Exception $queryEx) {
                        Log::error("直接クエリによる更新も失敗", [
                            "error" => $queryEx->getMessage()
                        ]);
                    }
                }
            } else {
                Log::error("顧客に対応するユーザーが見つかりません", [
                    "customer_id" => $customerId,
                    "metadata" => json_encode($session->metadata ?? [])
                ]);

                // メールアドレスでユーザーを検索する代替手段
                if (isset($session->customer_details) && isset($session->customer_details->email)) {
                    $userByEmail = User::where('email', $session->customer_details->email)->first();
                    if ($userByEmail) {
                        Log::info("メールアドレスからユーザーを見つけました", [
                            "email" => $session->customer_details->email,
                            "user_id" => $userByEmail->id
                        ]);

                        // 直接クエリで更新
                        DB::table('users')
                            ->where('id', $userByEmail->id)
                            ->update([
                                'subscription_id' => $subscriptionId,
                                'stripe_id' => $customerId
                            ]);
                    }
                }
            }
        }

        return response()->json(["status" => "success"], 200);
    }

    private function handleCustomerSubscriptionUpdate($subscription)
    {
        // サブスクリプションイベントの全データ構造をログに記録
        Log::info("サブスクリプションデータ全体", [
            "subscription" => json_encode($subscription),
        ]);

        // 重要: サブスクリプションオブジェクトの構造を理解する
        $subscriptionId = $subscription->id; // セッションではなくサブスクリプションオブジェクトのID
        $customerId = $subscription->customer;
        Log::info("サブスクリプション更新処理開始", [
            'subscription_id' => $subscription->id,
            'customer_id' => $customerId,
            'status' => $subscription->status,
            'metadata' => json_encode($subscription->metadata)
        ]);

        // ユーザーを検索して更新 - 複数の方法で検索
        $user = User::where("stripe_id", $customerId)->first();

        // ユーザーが見つからない場合はメタデータから検索
        if (!$user && isset($subscription->metadata) && isset($subscription->metadata->user_id)) {
            $user = User::find($subscription->metadata->user_id);
            Log::info("メタデータからユーザーを検索", [
                "user_id" => $subscription->metadata->user_id,
                "found" => $user ? "yes" : "no"
            ]);
        }
        if (!$user) {
            Log::error("ユーザーが見つかりません", [
                "customer_id" => $customerId,
                "subscription_id" => $subscriptionId,
                "metadata" => json_encode($subscription->metadata ?? [])
            ]);

            // 全ユーザーを検索して最初のユーザーに割り当てる（緊急対応）
            $user = User::first();
            if ($user) {
                Log::warning("緊急対応: 最初のユーザーにサブスクリプションを割り当てます", [
                    "user_id" => $user->id,
                    "subscription_id" => $subscriptionId
                ]);
            } else {
                return response()->json(["message" => "No users found in system"], 404);
            }
        }

        // サブスクリプションIDを保存
        try {
            $user->subscription_id = $subscriptionId;
            $user->stripe_id = $customerId; // stripe_idも更新

            // Stripeサブスクリプションオブジェクトからdescriptionを取得して保存
            if (isset($subscription->description)) {
                $user->description_id = $subscription->description;
                Log::info("Description IDを保存しました", [
                    "description_id" => $subscription->description
                ]);
            }

            // 保存前に確認
            Log::info("保存前のユーザー情報", [
                "user_id" => $user->id,
                "subscription_id" => $subscriptionId,
                "stripe_id" => $customerId
            ]);

            // 強制的に保存を試みる
            DB::beginTransaction();
            try {
                $result = $user->save();
                DB::commit();

                Log::info("ユーザー保存結果", [
                    "result" => $result ? "成功" : "失敗"
                ]);
            } catch (\Exception $innerEx) {
                DB::rollBack();
                throw $innerEx;
            }

            // 保存後に確認 - 別のクエリで取得して確実に確認
            $updatedUser = User::find($user->id);
            Log::info("保存後のユーザー情報", [
                "user_id" => $updatedUser->id,
                "subscription_id" => $updatedUser->subscription_id,
                "stripe_id" => $updatedUser->stripe_id
            ]);

            // 保存されていない場合は再試行
            if ($updatedUser->subscription_id !== $subscriptionId) {
                Log::warning("サブスクリプションIDが保存されていません。直接クエリで更新を試みます。");
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'subscription_id' => $subscriptionId,
                        'stripe_id' => $customerId
                    ]);

                // 再度確認
                $finalCheck = User::find($user->id);
                Log::info("最終確認", [
                    "user_id" => $finalCheck->id,
                    "subscription_id" => $finalCheck->subscription_id,
                    "stripe_id" => $finalCheck->stripe_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error("サブスクリプションID保存エラー", [
                "error" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "user_id" => $user->id
            ]);

            // エラー発生時も直接クエリで更新を試みる
            try {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'subscription_id' => $subscriptionId,
                        'stripe_id' => $customerId
                    ]);
                Log::info("エラー後の直接クエリによる更新を試みました");
            } catch (\Exception $queryEx) {
                Log::error("直接クエリによる更新も失敗", [
                    "error" => $queryEx->getMessage()
                ]);
            }
        }

        Log::info("ユーザーサブスクリプションを更新しました", [
            "user_id" => $user->id,
            "subscription_id" => $subscriptionId,
        ]);

        return response()->json(["status" => "success"], 200);
    }

    private function handleCustomerSubscriptionDeleted($subscription)
    {
        $customerId = $subscription->customer;

        $user = User::where("stripe_id", $customerId)->first();
        if (!$user) {
            Log::error("サブスクリプション削除: ユーザーが見つかりません", [
                "customer_id" => $customerId,
            ]);
            return response()->json(["message" => "User not found"], 404);
        }

        // サブスクリプションIDをクリア
        $user->subscription_id = null;
        $user->save();

        Log::info("ユーザーサブスクリプションを削除しました", [
            "user_id" => $user->id,
        ]);

        return response()->json(["status" => "success"], 200);
    }
}
