<!-- resources/js/components/ShareSelectedTasksModal.vue -->
<template>
    <div class="fixed inset-0 flex items-center justify-center z-50">
        <!-- Background overlay -->
        <div
            class="absolute inset-0 bg-black bg-opacity-30"
            @click="$emit('close')"
        ></div>

        <!-- Modal content -->
        <div
            class="bg-white rounded-lg shadow-md w-full max-w-md relative z-10 p-6"
        >
            <h3 class="text-lg font-medium mb-4">選択したタスクを共有</h3>

            <!-- Email input -->
            <div class="mb-4">
                <label
                    for="share-email"
                    class="block text-sm font-medium text-gray-700 mb-2"
                    >共有先のメールアドレス</label
                >
                <input
                    type="email"
                    id="share-email"
                    v-model="shareEmail"
                    placeholder="例: colleague@example.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                />
            </div>

            <!-- Permission selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                    >権限</label
                >
                <div class="flex space-x-2">
                    <button
                        @click="permission = 'view'"
                        :class="[
                            'flex-1 px-3 py-2 rounded-md text-center text-sm font-medium',
                            permission === 'view'
                                ? 'bg-blue-100 text-blue-800 border-2 border-blue-400'
                                : 'bg-gray-100 text-gray-800 hover:bg-gray-200',
                        ]"
                    >
                        閲覧のみ
                    </button>
                    <button
                        @click="permission = 'edit'"
                        :class="[
                            'flex-1 px-3 py-2 rounded-md text-center text-sm font-medium',
                            permission === 'edit'
                                ? 'bg-blue-100 text-blue-800 border-2 border-blue-400'
                                : 'bg-gray-100 text-gray-800 hover:bg-gray-200',
                        ]"
                    >
                        編集可能
                    </button>
                </div>
            </div>

            <!-- Task preview -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    共有するタスク数: {{ tasks.length }}
                </label>
                <div class="max-h-40 overflow-y-auto bg-gray-50 p-2 rounded-md">
                    <div
                        v-if="tasks.length === 0"
                        class="text-center py-4 text-gray-500"
                    >
                        タスクが選択されていません
                    </div>
                    <div v-else class="divide-y divide-gray-200">
                        <div
                            v-for="task in tasks.slice(0, 5)"
                            :key="task.id"
                            class="py-2 text-sm"
                        >
                            {{ task.title }}
                        </div>
                        <div
                            v-if="tasks.length > 5"
                            class="py-2 text-sm text-gray-500 text-center"
                        >
                            他 {{ tasks.length - 5 }} 件のタスク
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center mb-4">
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

            <!-- Category naming -->
            <div class="mb-4">
                <label
                    for="category-name"
                    class="block text-sm font-medium text-gray-700 mb-2"
                >
                    共有カテゴリー名
                </label>
                <input
                    type="text"
                    id="category-name"
                    v-model="categoryName"
                    placeholder="例: プロジェクトA タスク"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                />
                <p class="mt-1 text-xs text-gray-500">
                    空白の場合は自動的に「共有タスク
                    (2024/03/31)」のような名前が付けられます
                </p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-2">
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200"
                >
                    キャンセル
                </button>
                <button
                    @click="shareSelectedTasks"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                    :disabled="!canShare || isSharing"
                >
                    {{ isSharing ? "共有中..." : "共有する" }}
                </button>
            </div>

            <!-- Error/Success message -->
            <div v-if="errorMessage" class="mt-3 text-sm text-red-600">
                {{ errorMessage }}
            </div>
            <div v-if="successMessage" class="mt-3 text-sm text-green-600">
                {{ successMessage }}
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed } from "vue";
import axios from "axios";

export default {
    name: "ShareSelectedTasksModal",

    props: {
        tasks: {
            type: Array,
            required: true,
        },
    },

    emits: ["close", "shared"],

    setup(props, { emit }) {
        // State
        const shareEmail = ref("");
        const permission = ref("view");
        const categoryName = ref("");
        const isSharing = ref(false);
        const errorMessage = ref("");
        const successMessage = ref("");
        const slackAuthRequired = ref(true);

        // Computed
        const canShare = computed(() => {
            return (
                shareEmail.value &&
                shareEmail.value.includes("@") &&
                props.tasks.length > 0
            );
        });

        // Methods
        const shareSelectedTasks = async () => {
            if (!canShare.value) return;

            isSharing.value = true;
            errorMessage.value = "";
            successMessage.value = "";

            try {
                // Generate category name if not provided
                const now = new Date();
                const dateStr = `${now.getFullYear()}/${(now.getMonth() + 1).toString().padStart(2, "0")}/${now.getDate().toString().padStart(2, "0")}`;
                const generatedCategoryName =
                    categoryName.value.trim() || `共有タスク (${dateStr})`;

                // Create a new category
                const categoryResponse = await axios.post("/api/categories", {
                    name: generatedCategoryName,
                    color: "#3182CE", // Blue
                });

                const categoryId = categoryResponse.data.id;

                // Add tasks to this category
                const updatePromises = props.tasks.map((task) =>
                    axios.put(`/api/todos/${task.id}`, {
                        category_id: categoryId,
                    }),
                );

                await Promise.all(updatePromises);

                // Share the category
                await axios.post(`/api/categories/${categoryId}/shares`, {
                    email: shareEmail.value,
                    permission: permission.value,
                    slack_auth_required: slackAuthRequired.value,
                });

                successMessage.value = `${props.tasks.length}件のタスクを共有しました！`;

                // Wait a moment to show success message before closing
                setTimeout(() => {
                    emit("shared", {
                        taskCount: props.tasks.length,
                        email: shareEmail.value,
                    });

                    emit("close");
                }, 1500);
            } catch (error) {
                console.error("Failed to share tasks:", error);
                errorMessage.value =
                    error.response?.data?.message ||
                    error.message ||
                    "タスクの共有に失敗しました";
                isSharing.value = false;
            }
        };

        return {
            shareEmail,
            permission,
            categoryName,
            slackAuthRequired,
            isSharing,
            errorMessage,
            successMessage,
            canShare,
            shareSelectedTasks,
        };
    },
};
</script>
