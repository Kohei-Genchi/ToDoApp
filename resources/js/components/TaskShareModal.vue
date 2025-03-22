<template>
    <div class="fixed inset-0 flex items-center justify-center z-50">
        <!-- 背景オーバーレイ -->
        <div
            class="absolute inset-0 bg-black bg-opacity-30"
            @click="close"
        ></div>

        <!-- モーダルコンテンツ -->
        <div
            class="bg-white rounded-lg shadow-md w-full max-w-md relative z-10 p-6"
        >
            <h3 class="text-lg font-medium mb-4">{{ task.title }} の共有</h3>

            <!-- 現在の共有ユーザー一覧 -->
            <shared-users-list
                v-if="sharedUsers.length > 0"
                :shared-users="sharedUsers"
                @update-permission="updatePermission"
                @unshare="unshareTask"
            />

            <!-- 共有上限の通知 -->
            <max-users-warning
                v-if="sharedUsers.length >= MAX_SHARED_USERS"
                :max="MAX_SHARED_USERS"
            />

            <!-- 新規共有フォーム -->
            <share-form
                v-if="sharedUsers.length < MAX_SHARED_USERS"
                v-model:email="shareEmail"
                v-model:permission="sharePermission"
                :is-submitting="isSubmitting"
                :is-valid-email="isValidEmail"
                :error-message="errorMessage"
                @share="shareTask"
            />

            <!-- アクションボタン -->
            <div class="flex justify-end">
                <button
                    @click="close"
                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300"
                >
                    閉じる
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import TaskShareApi from "../api/taskShare";
import GlobalShareApi from "../api/globalShare";
import SharedUsersList from "./share/SharedUsersList.vue";
import MaxUsersWarning from "./share/MaxUsersWarning.vue";
import ShareForm from "./share/ShareForm.vue";

// 定数定義
const MAX_SHARED_USERS = 5;
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export default {
    name: "TaskShareModal",

    components: {
        SharedUsersList,
        MaxUsersWarning,
        ShareForm,
    },

    props: {
        /**
         * 共有するタスク情報
         * グローバル共有モードの場合は id: "global-share", isGlobalShare: true を含む
         */
        task: {
            type: Object,
            required: true,
        },
    },

    emits: ["close"],

    setup(props, { emit }) {
        // =============== 状態管理 ===============
        const sharedUsers = ref([]);
        const shareEmail = ref("");
        const sharePermission = ref("view");
        const isSubmitting = ref(false);
        const errorMessage = ref("");

        // =============== 計算プロパティ ===============
        /**
         * メールアドレスの形式が正しいかをチェック
         */
        const isValidEmail = computed(() => {
            return EMAIL_REGEX.test(shareEmail.value);
        });

        /**
         * グローバル共有モードかどうかを判定
         */
        const isGlobalShareMode = computed(() => {
            return props.task && props.task.id === "global-share";
        });

        // =============== メソッド - データ操作 ===============
        /**
         * 共有ユーザー一覧を取得
         */
        const loadSharedUsers = async () => {
            clearError();

            try {
                // グローバル共有モードと個別タスク共有モードで処理を分ける
                if (isGlobalShareMode.value) {
                    await loadGlobalSharedUsers();
                } else {
                    await loadTaskSharedUsers();
                }
            } catch (error) {
                console.error(
                    "共有ユーザー情報の取得中にエラーが発生しました:",
                    error,
                );
                showError("ユーザー情報の読み込みに失敗しました。");
                sharedUsers.value = [];
            }
        };

        /**
         * グローバル共有ユーザー一覧を取得
         */
        const loadGlobalSharedUsers = async () => {
            try {
                const response = await GlobalShareApi.getGlobalShares();

                // APIレスポンスをコンポーネントで扱いやすい形式に変換
                sharedUsers.value = response.data.map((share) => ({
                    id: share.user_id,
                    name: share.name,
                    email: share.email,
                    pivot: { permission: share.permission },
                    globalShareId: share.id, // グローバル共有ID
                }));
            } catch (error) {
                console.error(
                    "グローバル共有ユーザーの読み込みに失敗しました:",
                    error,
                );
                throw error;
            }
        };

        /**
         * 個別タスクの共有ユーザー一覧を取得
         */
        const loadTaskSharedUsers = async () => {
            try {
                const response = await TaskShareApi.getSharedUsers(
                    props.task.id,
                );

                // 各ユーザーにpivotプロパティがなければ追加（エラー防止）
                sharedUsers.value = response.data.map((user) => ({
                    ...user,
                    pivot: user.pivot || { permission: "view" }, // デフォルト権限
                }));
            } catch (error) {
                console.error(
                    "個別タスク共有ユーザーの読み込みに失敗しました:",
                    error,
                );
                throw error;
            }
        };

        // =============== メソッド - 共有操作 ===============
        /**
         * 新しいユーザーとタスクを共有
         */
        const shareTask = async () => {
            // 入力検証
            if (!validateShareInput()) return;

            clearError();
            isSubmitting.value = true;

            try {
                if (isGlobalShareMode.value) {
                    await shareGlobally();
                } else {
                    await shareIndividualTask();
                }

                // フォームをリセット
                resetForm();

                // 成功メッセージを一時的に表示
                showTemporaryMessage("ユーザーを共有リストに追加しました。");
            } catch (error) {
                handleShareError(error);
            } finally {
                isSubmitting.value = false;
            }
        };

        /**
         * 共有入力の検証
         */
        const validateShareInput = () => {
            // メールアドレスの形式検証
            if (!isValidEmail.value) {
                showError("有効なメールアドレスを入力してください。");
                return false;
            }

            // 既に共有済みのユーザーかチェック
            if (isDuplicateEmail()) {
                showError("指定されたユーザーは既に共有設定されています。");
                return false;
            }

            return true;
        };

        /**
         * フォームのリセット
         */
        const resetForm = () => {
            shareEmail.value = "";
            sharePermission.value = "view";
        };

        /**
         * ユーザーをグローバル共有に追加
         */
        const shareGlobally = async () => {
            // APIを呼び出し
            const response = await GlobalShareApi.addGlobalShare(
                shareEmail.value,
                sharePermission.value,
            );

            // レスポンスからユーザー情報を抽出
            const userData = response.data?.user;

            // ユーザー情報の処理
            if (!userData) {
                if (response.data?.success) {
                    // 成功は返ってきているが、user情報がない場合は仮データを作成
                    addTemporaryUser();
                } else {
                    throw new Error("ユーザー情報が見つかりません");
                }
            } else {
                // 正常にユーザー情報が取得できた場合
                addSharedUser(userData);
            }
        };

        /**
         * 一時的なユーザーを追加（APIからの情報が不足している場合）
         */
        const addTemporaryUser = () => {
            const tempId = `temp-${Date.now()}`;

            // 新しいユーザーを一覧に追加
            const newUser = {
                id: tempId,
                name: shareEmail.value.split("@")[0], // メールアドレスのユーザー部分を仮の名前として使用
                email: shareEmail.value,
                pivot: { permission: sharePermission.value },
                globalShareId: tempId, // 一時的なIDを設定
                isTemporary: true, // 一時的なフラグを追加
            };

            sharedUsers.value.push(newUser);
        };

        /**
         * 新しい共有ユーザーを追加
         */
        const addSharedUser = (userData) => {
            const newUser = {
                id: userData.id || `temp-${Date.now()}`,
                name: userData.name || shareEmail.value.split("@")[0],
                email: userData.email || shareEmail.value,
                pivot: {
                    permission: userData.permission || sharePermission.value,
                },
                globalShareId: userData.id || null,
            };

            sharedUsers.value.push(newUser);
        };

        /**
         * 個別タスクを特定のユーザーと共有
         */
        const shareIndividualTask = async () => {
            const response = await TaskShareApi.shareTask(
                props.task.id,
                shareEmail.value,
                sharePermission.value,
            );

            // 新しいユーザーを一覧に追加
            const newUser = {
                ...response.data.user,
                pivot: { permission: sharePermission.value },
            };

            sharedUsers.value.push(newUser);
        };

        /**
         * 共有権限を更新
         */
        const updatePermission = async (user) => {
            // pivotがない場合は更新できない
            if (!user.pivot) {
                console.error("権限情報がありません");
                return;
            }

            try {
                if (isGlobalShareMode.value) {
                    await updateGlobalPermission(user);
                } else {
                    await updateTaskPermission(user);
                }
            } catch (error) {
                console.error("権限の更新に失敗しました:", error);
                showError("権限の更新に失敗しました。");
                // UIを元に戻すために再読み込み
                await loadSharedUsers();
            }
        };

        /**
         * グローバル共有の権限を更新
         */
        const updateGlobalPermission = async (user) => {
            // ユーザーIDの取得（グローバル共有IDがない場合はユーザーID）
            const targetId = user.globalShareId || user.id;

            if (!targetId) {
                showError(
                    "グローバル共有情報が不足しているため更新できません。",
                );
                return;
            }

            // 権限情報の有無をチェック
            if (!user.pivot || !user.pivot.permission) {
                console.error("権限情報が見つかりません:", user);
                showError("権限情報が見つかりません。");
                return;
            }

            await GlobalShareApi.updateGlobalSharePermission(
                targetId,
                user.pivot.permission,
            );
        };

        /**
         * 個別タスク共有の権限を更新
         */
        const updateTaskPermission = async (user) => {
            await TaskShareApi.updatePermission(
                props.task.id,
                user.id,
                user.pivot.permission,
            );
        };

        /**
         * 共有を解除
         */
        const unshareTask = async (user) => {
            // 確認ダイアログ
            if (
                !confirm(
                    `${user.name || user.email} との共有を解除してもよろしいですか？`,
                )
            ) {
                return;
            }

            try {
                if (isGlobalShareMode.value) {
                    await unshareGlobally(user);
                } else {
                    await unshareTaskFromUser(user);
                }

                // ユーザー一覧から削除
                sharedUsers.value = sharedUsers.value.filter(
                    (u) => u.id !== user.id,
                );
            } catch (error) {
                console.error("共有解除に失敗しました:", error);
                showError("共有解除に失敗しました。");
            }
        };

        /**
         * グローバル共有を解除
         */
        const unshareGlobally = async (user) => {
            // ユーザーIDの取得（グローバル共有IDがない場合はユーザーID）
            const targetId = user.globalShareId || user.id;

            if (!targetId) {
                showError(
                    "グローバル共有情報が不足しているため解除できません。",
                );
                return;
            }

            await GlobalShareApi.removeGlobalShare(targetId);
        };

        /**
         * 個別タスクの共有を解除
         */
        const unshareTaskFromUser = async (user) => {
            await TaskShareApi.unshareTask(props.task.id, user.id);
        };

        // =============== メソッド - UI操作 ===============
        /**
         * モーダルを閉じる
         */
        const close = () => {
            emit("close", { sharedUsers: sharedUsers.value });
        };

        /**
         * 一時的なメッセージを表示し、自動的に消す
         */
        const showTemporaryMessage = (message, duration = 3000) => {
            errorMessage.value = message;
            setTimeout(() => {
                errorMessage.value = "";
            }, duration);
        };

        /**
         * エラーメッセージを表示
         */
        const showError = (message) => {
            errorMessage.value = message;
        };

        /**
         * エラーメッセージをクリア
         */
        const clearError = () => {
            errorMessage.value = "";
        };

        /**
         * 共有エラーを処理
         */
        const handleShareError = (error) => {
            // エラー情報の詳細ログ
            console.error("共有処理でエラーが発生しました:", error);

            // エラータイプによって異なるメッセージを表示
            if (
                error instanceof Error &&
                error.message.includes("APIレスポンス")
            ) {
                showError(
                    "ユーザー共有に成功しましたが、最新情報の取得に失敗しました。画面を再読み込みしてください。",
                );
            } else if (
                error instanceof TypeError &&
                error.message.includes("Cannot read properties of undefined")
            ) {
                showError(
                    "ユーザー共有は完了しましたが、表示の更新に失敗しました。",
                );
            } else if (error.response) {
                handleApiError(error.response);
            } else {
                showError(error.message || "共有に失敗しました。");
            }
        };

        /**
         * APIエラーのハンドリング
         */
        const handleApiError = (response) => {
            if (response.status === 422) {
                // バリデーションエラー
                if (response.data.errors && response.data.errors.email) {
                    const emailErrors = response.data.errors.email;
                    if (emailErrors.some((err) => err.includes("exists"))) {
                        showError(
                            "指定されたメールアドレスのユーザーは存在しません。",
                        );
                    } else {
                        showError(emailErrors[0]);
                    }
                } else {
                    showError("入力内容に誤りがあります。");
                }
            } else if (response.status === 400) {
                // Bad Request
                showError(
                    response.data?.error ||
                        "リクエストが不正です。入力内容を確認してください。",
                );
            } else if (response.status === 500) {
                // サーバーエラー
                showError(
                    "サーバー側でエラーが発生しました。しばらく経ってから再試行してください。",
                );
            } else {
                // その他のエラー
                showError(response.data?.error || "共有に失敗しました。");
            }
        };

        /**
         * 既に共有済みのメールアドレスかどうかをチェック
         */
        const isDuplicateEmail = () => {
            return sharedUsers.value.some(
                (user) =>
                    user.email.toLowerCase() === shareEmail.value.toLowerCase(),
            );
        };

        // ライフサイクルフック
        onMounted(() => {
            loadSharedUsers();
        });

        return {
            // 定数
            MAX_SHARED_USERS,

            // リアクティブ変数
            sharedUsers,
            shareEmail,
            sharePermission,
            isSubmitting,
            errorMessage,

            // 計算プロパティ
            isValidEmail,
            isGlobalShareMode,

            // メソッド
            shareTask,
            updatePermission,
            unshareTask,
            close,
        };
    },
};
</script>
