<template>
    <div class="fixed inset-0 flex items-center justify-center z-50">
        <!-- 背景オーバーレイ -->
        <div
            class="absolute inset-0 bg-black bg-opacity-30"
            @click="$emit('close')"
        ></div>

        <!-- モーダルコンテンツ -->
        <div
            class="bg-white rounded-lg shadow-md w-full max-w-md relative z-10 p-6"
        >
            <h3 class="text-lg font-medium mb-4">{{ category.name }} の共有</h3>

            <!-- 現在の共有ユーザー一覧 -->
            <div v-if="sharedUsers.length > 0" class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">
                    共有済みユーザー
                </h4>
                <ul class="space-y-2">
                    <li
                        v-for="user in sharedUsers"
                        :key="user.id"
                        class="flex items-center justify-between p-2 bg-gray-50 rounded"
                    >
                        <div>
                            <p class="text-sm font-medium">{{ user.name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ user.email }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <select
                                v-model="user.pivot.permission"
                                @change="updatePermission(user)"
                                class="text-xs border rounded p-1"
                            >
                                <option value="view">閲覧のみ</option>
                                <option value="edit">編集可能</option>
                            </select>
                            <button
                                @click.stop="unshareCategory(user)"
                                class="text-red-500 hover:text-red-700"
                                title="共有解除"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- 新規共有フォーム -->
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">
                    新しいユーザーと共有
                </h4>
                <div class="flex space-x-2">
                    <input
                        type="email"
                        v-model="shareEmail"
                        placeholder="メールアドレスを入力"
                        class="flex-1 border rounded p-2 text-sm"
                    />
                    <select
                        v-model="sharePermission"
                        class="border rounded p-2 text-sm"
                    >
                        <option value="view">閲覧のみ</option>
                        <option value="edit">編集可能</option>
                    </select>
                </div>
                <div class="mt-2">
                    <div class="flex items-center mb-2">
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                id="slack-auth-required"
                                v-model="slackAuthRequired"
                                class="mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            />
                            <label for="slack-auth-required" class="text-sm">
                                Slack認証を必須にする
                            </label>
                        </div>
                        <div class="ml-2 text-xs text-gray-500">
                            <span
                                class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded"
                            >
                                推奨
                            </span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">
                        Slack認証を有効にすると、ユーザーはSlackで承認を行えます
                    </p>
                    <button
                        @click="shareCategory"
                        class="w-full bg-blue-500 text-white rounded py-2 text-sm hover:bg-blue-600 transition"
                        :disabled="!isValidEmail || isSubmitting"
                        :class="{
                            'opacity-50 cursor-not-allowed':
                                !isValidEmail || isSubmitting,
                        }"
                    >
                        {{ isSubmitting ? "処理中..." : "共有する" }}
                    </button>
                </div>
                <div v-if="errorMessage" class="mt-2 text-sm text-red-500">
                    {{ errorMessage }}
                </div>
                <div v-if="successMessage" class="mt-2 text-sm text-green-500">
                    {{ successMessage }}
                </div>
            </div>

            <!-- アクションボタン -->
            <div class="flex justify-end">
                <button
                    @click="$emit('close')"
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
import axios from "axios";

export default {
    name: "CategoryShareModal",

    props: {
        category: {
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
        const slackAuthRequired = ref(true); // デフォルトでSlack認証を有効に
        const isSubmitting = ref(false);
        const errorMessage = ref("");
        const successMessage = ref("");
        const notificationSent = ref(false);

        // メールアドレスの正規表現
        const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        // =============== 計算プロパティ ===============
        const isValidEmail = computed(() => {
            return EMAIL_REGEX.test(shareEmail.value);
        });

        // =============== メソッド ===============
        // 共有ユーザー一覧の取得
        const loadSharedUsers = async () => {
            try {
                const response = await axios.get(
                    `/api/categories/${props.category.id}/shares`,
                    {
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );
                sharedUsers.value = response.data;
            } catch (error) {
                errorMessage.value = "共有ユーザーの読み込みに失敗しました";
                console.error("Error loading shared users:", error);
            }
        };

        // カテゴリーの共有
        const shareCategory = async () => {
            if (!isValidEmail.value) {
                errorMessage.value = "有効なメールアドレスを入力してください";
                return;
            }

            isSubmitting.value = true;
            errorMessage.value = "";
            successMessage.value = "";

            try {
                // CSRFトークンを取得
                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content");

                // APIリクエストを送信
                const response = await axios.post(
                    `/api/categories/${props.category.id}/shares`,
                    {
                        email: shareEmail.value,
                        permission: sharePermission.value,
                        slack_auth_required: slackAuthRequired.value,
                    },
                    {
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": csrfToken,
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );

                const data = response.data;

                // 共有リクエストが既に送信済みの場合
                if (
                    data.message &&
                    data.message.includes("already been sent")
                ) {
                    successMessage.value =
                        "カテゴリー共有リクエストが既に送信されています。相手はSlackで承認する必要があります。";
                    setTimeout(() => {
                        emit("close");
                    }, 2000);
                    return;
                }

                // 成功した場合
                if (data.success) {
                    // Slack通知が送信された場合
                    if (data.message && data.message.includes("Slack")) {
                        successMessage.value =
                            "カテゴリー共有リクエストをSlackに送信しました。承認されるまでお待ちください。";
                    } else {
                        successMessage.value = slackAuthRequired.value
                            ? "カテゴリー共有リクエストが送信されました。相手はSlackで承認する必要があります。"
                            : "カテゴリーが共有されました";
                    }

                    shareEmail.value = "";

                    // 共有ユーザー一覧を更新
                    await loadSharedUsers();

                    // 成功メッセージを表示した後、モーダルを閉じる
                    setTimeout(() => {
                        emit("close");
                    }, 2000);
                } else {
                    errorMessage.value = data.error || "共有に失敗しました";
                }
            } catch (error) {
                console.error("共有エラー:", error);

                if (error.response && error.response.data) {
                    // API からのエラーメッセージを表示
                    errorMessage.value =
                        error.response.data.error ||
                        (error.response.data.errors
                            ? Object.values(error.response.data.errors)
                                  .flat()
                                  .join(", ")
                            : "共有処理中にエラーが発生しました");
                } else {
                    errorMessage.value = "共有処理中にエラーが発生しました";
                }
            } finally {
                isSubmitting.value = false;
            }
        };

        // 共有権限の更新
        const updatePermission = async (user) => {
            try {
                await axios.put(
                    `/api/categories/${props.category.id}/shares/${user.id}`,
                    {
                        permission: user.pivot.permission,
                    },
                    {
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );

                successMessage.value = "権限を更新しました";
                setTimeout(() => {
                    successMessage.value = "";
                }, 2000);
            } catch (error) {
                console.error("Error updating permission:", error);
                errorMessage.value = "権限の更新に失敗しました";
                // 変更を元に戻す（再読み込み）
                await loadSharedUsers();
            }
        };

        // カテゴリーの共有解除
        const unshareCategory = async (user) => {
            if (!confirm(`${user.name} との共有を解除してもよろしいですか？`)) {
                return;
            }

            try {
                await axios.delete(
                    `/api/categories/${props.category.id}/shares/${user.id}`,
                    {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );
                // 成功したら共有ユーザー一覧から削除
                sharedUsers.value = sharedUsers.value.filter(
                    (u) => u.id !== user.id,
                );

                successMessage.value = "共有を解除しました";
                setTimeout(() => {
                    successMessage.value = "";
                }, 2000);
            } catch (error) {
                console.error("Error unsharing category:", error);
                errorMessage.value = "共有解除に失敗しました";
            }
        };

        // コンポーネントのマウント時に共有ユーザー一覧を取得
        onMounted(() => {
            loadSharedUsers();
        });

        return {
            sharedUsers,
            shareEmail,
            sharePermission,
            slackAuthRequired,
            isSubmitting,
            errorMessage,
            successMessage,
            notificationSent,
            isValidEmail,
            shareCategory,
            updatePermission,
            unshareCategory,
        };
    },
};
</script>
