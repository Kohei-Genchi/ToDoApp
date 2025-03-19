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
import GlobalShareApi from "../api/globalShare";

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
        const debug = ref(false); // デバッグモード

        // Computed
        const isValidEmail = computed(() => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(shareEmail.value);
        });

        // グローバル共有モードかどうかを判定
        const isGlobalShareMode = computed(() => {
            return props.task && props.task.id === "global-share";
        });

        // Methods
        const loadSharedUsers = async () => {
            console.log(
                "loadSharedUsers called, isGlobalShareMode:",
                isGlobalShareMode.value,
            );

            // グローバル共有モードの場合はグローバル共有ユーザーを取得
            if (isGlobalShareMode.value) {
                console.log("グローバル共有モード: ユーザー一覧を取得");

                try {
                    const response = await GlobalShareApi.getGlobalShares();
                    // Map the response data to the expected format
                    sharedUsers.value = response.data.map((share) => ({
                        id: share.user_id,
                        name: share.name,
                        email: share.email,
                        pivot: { permission: share.permission },
                        globalShareId: share.id, // Add this to identify the global share
                    }));
                } catch (error) {
                    console.error("Error loading global shares:", error);
                    errorMessage.value =
                        "グローバル共有ユーザーの読み込みに失敗しました。";

                    // APIエラーの場合はlocalStorageからユーザー情報を取得
                    try {
                        // ユーザー固有のキーを使用
                        const currentUserId = localStorage.getItem('currentUserId') || 'guest';
                        const savedUsersKey = `sharedUsers_${currentUserId}`;

                        const storedUsers = localStorage.getItem(savedUsersKey);
                        if (storedUsers) {
                            const parsedUsers = JSON.parse(storedUsers);
                            console.log("Loaded shared users from localStorage:", parsedUsers);
                            sharedUsers.value = parsedUsers;
                        } else {
                            sharedUsers.value = [];
                        }
                    } catch (storageError) {
                        console.error("Error loading from localStorage:", storageError);
                        sharedUsers.value = [];
                    }
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
            console.log("shareTask called, email:", shareEmail.value);

            if (!isValidEmail.value) {
                errorMessage.value = "有効なメールアドレスを入力してください。";
                return;
            }

            errorMessage.value = "";
            isSubmitting.value = true;

            try {
                // グローバル共有モードの場合の処理
                if (isGlobalShareMode.value) {
                    console.log(
                        "グローバル共有モード: 新しいユーザー追加:",
                        shareEmail.value,
                    );

                    // 既存のユーザー一覧にあるか確認 (大文字小文字を区別せずに比較)
                    const existingUser = sharedUsers.value.find(
                        (u) =>
                            u.email.toLowerCase() ===
                            shareEmail.value.toLowerCase(),
                    );
                    if (existingUser) {
                        errorMessage.value =
                            "指定されたユーザーは既に共有設定されています。";
                        isSubmitting.value = false;
                        return;
                    }

                    // グローバル共有APIを使用して新しいユーザーを追加
                    const response = await GlobalShareApi.addGlobalShare(
                        shareEmail.value,
                        sharePermission.value,
                    );

                    // APIレスポンスからユーザー情報を取得
                    const newUser = {
                        id: response.data.user.id,
                        name: response.data.user.name,
                        email: response.data.user.email,
                        pivot: { permission: response.data.user.permission },
                        globalShareId: response.data.user.id, // Global share ID
                    };

                    // ユーザーを追加
                    sharedUsers.value.push(newUser);
                    console.log("ユーザーを追加しました:", newUser);

                    // localStorageにも保存
                    try {
                        const currentUserId = localStorage.getItem('currentUserId') || 'guest';
                        const savedUsersKey = `sharedUsers_${currentUserId}`;
                        localStorage.setItem(savedUsersKey, JSON.stringify(sharedUsers.value));
                        console.log("Saved users to localStorage");
                    } catch (storageError) {
                        console.error("Error saving to localStorage:", storageError);
                    }

                    // フォームをクリア
                    shareEmail.value = "";
                    sharePermission.value = "view";
                    isSubmitting.value = false;

                    // 成功メッセージを表示
                    errorMessage.value = "ユーザーを共有リストに追加しました。";
                    setTimeout(() => {
                        errorMessage.value = "";
                    }, 3000);

                    return;
                }

                // 通常のタスク共有モード

                // 既存のユーザー一覧にあるか確認 (大文字小文字を区別せずに比較)
                const existingUser = sharedUsers.value.find(
                    (u) =>
                        u.email.toLowerCase() ===
                        shareEmail.value.toLowerCase(),
                );
                if (existingUser) {
                    errorMessage.value =
                        "指定されたユーザーは既に共有設定されています。";
                    isSubmitting.value = false;
                    return;
                }

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

                // 成功メッセージを表示
                errorMessage.value = "ユーザーを共有リストに追加しました。";
                setTimeout(() => {
                    errorMessage.value = "";
                }, 3000);
            } catch (error) {
                console.error("Error sharing task:", error);

                // APIエラーの場合でもlocalStorageを使用してユーザーを追加
                if (isGlobalShareMode.value) {
                    try {
                        // 新しいユーザーを作成
                        const newUser = {
                            id: Date.now(), // 一時的なID
                            name: shareEmail.value.split('@')[0], // メールアドレスからユーザー名を生成
                            email: shareEmail.value,
                            pivot: { permission: sharePermission.value },
                            globalShareId: Date.now(), // 一時的なID
                        };

                        // ユーザーを追加
                        sharedUsers.value.push(newUser);

                        // localStorageに保存
                        const currentUserId = localStorage.getItem('currentUserId') || 'guest';
                        const savedUsersKey = `sharedUsers_${currentUserId}`;
                        localStorage.setItem(savedUsersKey, JSON.stringify(sharedUsers.value));

                        // フォームをクリア
                        shareEmail.value = "";
                        sharePermission.value = "view";

                        // 成功メッセージを表示
                        errorMessage.value = "ユーザーをローカルに追加しました（APIエラー）";
                        setTimeout(() => {
                            errorMessage.value = "";
                        }, 3000);
                    } catch (storageError) {
                        console.error("Error saving to localStorage:", storageError);
                        errorMessage.value = error.response?.data?.error || "共有に失敗しました。";
                    }
                } else {
                    errorMessage.value = error.response?.data?.error || "共有に失敗しました。";
                }
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

            // グローバル共有モードの場合はグローバル共有APIを使用
            if (isGlobalShareMode.value) {
                console.log(
                    "グローバル共有モード: 権限を更新:",
                    user.email,
                    user.pivot.permission,
                );

                try {
                    // Use the global share ID to update permission
                    if (user.globalShareId) {
                        await GlobalShareApi.updateGlobalSharePermission(
                            user.globalShareId,
                            user.pivot.permission,
                        );
                    } else {
                        console.error(
                            "Global share ID missing for user:",
                            user,
                        );
                        errorMessage.value =
                            "グローバル共有IDが見つかりません。";
                    }
                } catch (error) {
                    console.error("Error updating global permission:", error);
                    errorMessage.value =
                        "グローバル共有権限の更新に失敗しました。";
                    // Revert UI
                    loadSharedUsers();
                }
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
            if (
                !confirm(
                    `${user.name || user.email} との共有を解除してもよろしいですか？`,
                )
            ) {
                return;
            }

            // グローバル共有モードの場合はグローバル共有APIを使用
            if (isGlobalShareMode.value) {
                console.log(
                    "グローバル共有モード: ユーザーを削除:",
                    user.email,
                );

                try {
                    // Use the global share ID to remove the share
                    if (user.globalShareId) {
                        await GlobalShareApi.removeGlobalShare(
                            user.globalShareId,
                        );
                        // Remove user from the list
                        sharedUsers.value = sharedUsers.value.filter(
                            (u) => u.id !== user.id,
                        );

                        // localStorageも更新
                        try {
                            const currentUserId = localStorage.getItem('currentUserId') || 'guest';
                            const savedUsersKey = `sharedUsers_${currentUserId}`;
                            localStorage.setItem(savedUsersKey, JSON.stringify(sharedUsers.value));
                            console.log("Updated localStorage after removing user");
                        } catch (storageError) {
                            console.error("Error updating localStorage:", storageError);
                        }
                    } else {
                        console.error(
                            "Global share ID missing for user:",
                            user,
                        );
                        errorMessage.value =
                            "グローバル共有IDが見つかりません。";
                    }
                } catch (error) {
                    console.error("Error removing global share:", error);
                    errorMessage.value = "グローバル共有解除に失敗しました。";
                }
                return;
            }

            try {
                await TaskShareApi.unshareTask(props.task.id, user.id);
                // Remove the user from the list
                sharedUsers.value = sharedUsers.value.filter(
                    (u) => u.id !== user.id,
                );

                // 通常のタスク共有モードでもlocalStorageを更新
                if (isGlobalShareMode.value) {
                    try {
                        const currentUserId = localStorage.getItem('currentUserId') || 'guest';
                        const savedUsersKey = `sharedUsers_${currentUserId}`;
                        localStorage.setItem(savedUsersKey, JSON.stringify(sharedUsers.value));
                        console.log("Updated localStorage after removing user (task share mode)");
                    } catch (storageError) {
                        console.error("Error updating localStorage:", storageError);
                    }
                }
            } catch (error) {
                console.error("Error unsharing task:", error);
                errorMessage.value = "共有解除に失敗しました。";
            }
        };

        const close = () => {
            emit("close", { sharedUsers: sharedUsers.value });
        };

        // Lifecycle
        onMounted(() => {
            console.log("TaskShareModal mounted, task:", props.task);
            loadSharedUsers();
        });

        return {
            sharedUsers,
            shareEmail,
            sharePermission,
            isSubmitting,
            errorMessage,
            isValidEmail,
            isGlobalShareMode,
            debug,
            shareTask,
            updatePermission,
            unshareTask,
            close,
        };
    },
};
</script>
