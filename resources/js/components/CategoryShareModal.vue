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
                                @click="unshareCategory(user)"
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
                        <input
                            type="checkbox"
                            id="line-auth-required"
                            v-model="lineAuthRequired"
                            class="mr-2"
                        />
                        <label for="line-auth-required" class="text-sm">
                            LINE認証を必須にする
                        </label>
                    </div>
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
                <p v-if="errorMessage" class="mt-2 text-sm text-red-500">
                    {{ errorMessage }}
                </p>
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
        const lineAuthRequired = ref(true); // LINE認証をデフォルトで必須に
        const isSubmitting = ref(false);
        const errorMessage = ref("");

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

            try {
                const response = await axios.post(
                    `/api/categories/${props.category.id}/shares`,
                    {
                        email: shareEmail.value,
                        permission: sharePermission.value,
                        line_auth_required: lineAuthRequired.value,
                    },
                    {
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );

                if (response.data.success) {
                    // フォームをリセット
                    shareEmail.value = "";
                    // 成功メッセージを表示
                    alert(
                        lineAuthRequired.value
                            ? "カテゴリー共有リクエストが送信されました。相手はLINEで承認する必要があります。"
                            : "カテゴリーが共有されました",
                    );
                    // 共有ユーザー一覧を再読み込み
                    loadSharedUsers();
                } else {
                    errorMessage.value =
                        response.data.error || "共有に失敗しました";
                }
            } catch (error) {
                console.error("Error sharing category:", error);
                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.error
                ) {
                    errorMessage.value = error.response.data.error;
                } else {
                    errorMessage.value = "カテゴリーの共有に失敗しました";
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
            } catch (error) {
                console.error("Error updating permission:", error);
                alert("権限の更新に失敗しました");
                // 変更を元に戻す（再読み込み）
                loadSharedUsers();
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
            } catch (error) {
                console.error("Error unsharing category:", error);
                alert("共有解除に失敗しました");
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
            lineAuthRequired,
            isSubmitting,
            errorMessage,
            isValidEmail,
            shareCategory,
            updatePermission,
            unshareCategory,
        };
    },
};
</script>
