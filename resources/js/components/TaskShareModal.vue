<template>
    <div class="fixed inset-0 flex items-center justify-center z-50">
        <!-- Background overlay -->
        <div
            class="absolute inset-0 bg-black bg-opacity-30"
            @click="close"
        ></div>

        <!-- Modal content -->
        <div
            class="bg-white rounded-lg shadow-md w-full max-w-md relative z-10 p-6"
        >
            <h3 class="text-lg font-medium mb-4">{{ task.title }} の共有</h3>

            <!-- Current shares -->
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
                                v-if="user.pivot"
                                v-model="user.pivot.permission"
                                @change="updatePermission(user)"
                                class="text-xs border rounded p-1"
                            >
                                <option value="view">閲覧のみ</option>
                                <option value="edit">編集可能</option>
                            </select>
                            <select
                                v-else
                                class="text-xs border rounded p-1"
                                disabled
                            >
                                <option>権限なし</option>
                            </select>
                            <button
                                @click="unshareTask(user)"
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

            <!-- Share limit notice -->
            <div
                v-if="sharedUsers.length >= 5"
                class="mb-4 p-2 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800"
            >
                最大共有数 (5人)
                に達しています。新しく共有するには、既存の共有を解除してください。
            </div>

            <!-- Share form -->
            <div v-if="sharedUsers.length < 5" class="mb-4">
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
                    <button
                        @click="shareTask"
                        class="w-full bg-blue-500 text-white rounded py-2 text-sm hover:bg-blue-600 transition"
                        :disabled="!isValidEmail || isSubmitting"
                    >
                        {{ isSubmitting ? "処理中..." : "共有する" }}
                    </button>
                </div>
                <p v-if="errorMessage" class="mt-2 text-sm text-red-500">
                    {{ errorMessage }}
                </p>
            </div>

            <!-- Action buttons -->
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
import { ref, onMounted, computed } from "vue";
import TaskShareApi from "../api/taskShare";

export default {
    name: "TaskShareModal",

    props: {
        task: {
            type: Object,
            required: true,
        },
    },

    emits: ["close"],

    setup(props, { emit }) {
        // State
        const sharedUsers = ref([]);
        const shareEmail = ref("");
        const sharePermission = ref("view");
        const isSubmitting = ref(false);
        const errorMessage = ref("");

        // Computed
        const isValidEmail = computed(() => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(shareEmail.value);
        });

        // グローバル共有モードかどうかを判定
        const isGlobalShareMode = computed(() => {
            return props.task.id === "global-share";
        });

        // Methods
        const loadSharedUsers = async () => {
            // グローバル共有モードの場合はAPIリクエストをスキップ
            if (isGlobalShareMode.value) {
                // ユーザーの共有設定をリストしておく
                try {
                    // 代わりに全ユーザーの共有設定を取得
                    // 注: APIがない場合は空の配列を返す
                    const response = await TaskShareApi.getSharedWithMe();

                    // 共有ユーザーを重複なく抽出
                    const uniqueUsers = new Map();
                    if (response.data && Array.isArray(response.data)) {
                        response.data.forEach((task) => {
                            if (task.user) {
                                uniqueUsers.set(task.user.id, {
                                    id: task.user.id,
                                    name: task.user.name,
                                    email: task.user.email,
                                    pivot: { permission: "view" }, // デフォルトは表示のみ
                                });
                            }
                        });
                    }

                    sharedUsers.value = Array.from(uniqueUsers.values());
                } catch (error) {
                    console.log(
                        "グローバル共有ユーザー取得に失敗しました。新規に共有を設定してください。",
                    );
                    sharedUsers.value = [];
                }
                return;
            }

            // 通常のタスク共有モード
            try {
                const response = await TaskShareApi.getSharedUsers(
                    props.task.id,
                );
                // Ensure each user has a pivot property to avoid undefined errors
                sharedUsers.value = response.data.map((user) => {
                    if (!user.pivot) {
                        user.pivot = { permission: "view" }; // Default permission
                    }
                    return user;
                });
            } catch (error) {
                console.error("Error loading shared users:", error);
                errorMessage.value = "ユーザー情報の読み込みに失敗しました。";
            }
        };

        const shareTask = async () => {
            if (!isValidEmail.value) {
                errorMessage.value = "有効なメールアドレスを入力してください。";
                return;
            }

            errorMessage.value = "";
            isSubmitting.value = true;

            try {
                // グローバル共有モードの場合の処理
                if (isGlobalShareMode.value) {
                    // ここではバックエンドにグローバル共有APIがないため、
                    // ユーザーが最初のタスクを共有したことにする（デモ用）
                    // 実際の実装では、バックエンドに適切なAPIを用意することをお勧めします

                    // 既存のユーザー一覧にあるか確認
                    const existingUser = sharedUsers.value.find(
                        (u) => u.email === shareEmail.value,
                    );
                    if (existingUser) {
                        errorMessage.value =
                            "指定されたユーザーは既に共有設定されています。";
                        isSubmitting.value = false;
                        return;
                    }

                    // 新しいユーザーを追加（フロントエンドのみの操作）
                    const newUser = {
                        id: Date.now(), // 一時的なID
                        name: shareEmail.value.split("@")[0], // メールアドレスから名前を生成
                        email: shareEmail.value,
                        pivot: { permission: sharePermission.value },
                    };

                    sharedUsers.value.push(newUser);

                    // フォームをクリア
                    shareEmail.value = "";
                    sharePermission.value = "view";
                    isSubmitting.value = false;
                    return;
                }

                // 通常のタスク共有モード
                const response = await TaskShareApi.shareTask(
                    props.task.id,
                    shareEmail.value,
                    sharePermission.value,
                );

                // Add the new user to the list with pivot data
                const newUser = response.data.user;
                if (!newUser.pivot) {
                    newUser.pivot = { permission: sharePermission.value };
                }
                sharedUsers.value.push(newUser);

                // Clear the form
                shareEmail.value = "";
                sharePermission.value = "view";
            } catch (error) {
                console.error("Error sharing task:", error);
                errorMessage.value =
                    error.response?.data?.error || "共有に失敗しました。";
            } finally {
                isSubmitting.value = false;
            }
        };

        const updatePermission = async (user) => {
            // Skip if pivot doesn't exist
            if (!user.pivot) {
                console.error("Cannot update permission: pivot data missing");
                return;
            }

            // グローバル共有モードの場合はAPIリクエストをスキップ
            if (isGlobalShareMode.value) {
                // 権限の更新はフロントエンドのみで行う（デモ用）
                // 実際の実装では、バックエンドAPIを適切に呼び出す
                return;
            }

            try {
                await TaskShareApi.updatePermission(
                    props.task.id,
                    user.id,
                    user.pivot.permission,
                );
            } catch (error) {
                console.error("Error updating permission:", error);
                errorMessage.value = "権限の更新に失敗しました。";
                // Revert the value in the UI
                loadSharedUsers();
            }
        };

        const unshareTask = async (user) => {
            if (!confirm(`${user.name} との共有を解除してもよろしいですか？`)) {
                return;
            }

            // グローバル共有モードの場合
            if (isGlobalShareMode.value) {
                // フロントエンドでユーザーを削除（デモ用）
                sharedUsers.value = sharedUsers.value.filter(
                    (u) => u.id !== user.id,
                );
                return;
            }

            try {
                await TaskShareApi.unshareTask(props.task.id, user.id);
                // Remove the user from the list
                sharedUsers.value = sharedUsers.value.filter(
                    (u) => u.id !== user.id,
                );
            } catch (error) {
                console.error("Error unsharing task:", error);
                errorMessage.value = "共有解除に失敗しました。";
            }
        };

        const close = () => {
            emit("close");
        };

        // Lifecycle
        onMounted(() => {
            loadSharedUsers();
        });

        return {
            sharedUsers,
            shareEmail,
            sharePermission,
            isSubmitting,
            errorMessage,
            isValidEmail,
            isGlobalShareMode, // 追加
            shareTask,
            updatePermission,
            unshareTask,
            close,
        };
    },
};
</script>
