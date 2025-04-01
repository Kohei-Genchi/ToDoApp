<!-- resources/js/components/ShareByLocationModal.vue -->
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
            <h3 class="text-lg font-medium mb-4">タスクを共有</h3>

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

            <!-- Task info -->
            <div class="mb-4 p-3 bg-gray-50 rounded-md">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700"
                        >共有するタスク</span
                    >
                    <span
                        class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full"
                    >
                        {{ taskCount }} 件
                    </span>
                </div>
                <div v-if="isLoading" class="text-center py-2">
                    <span
                        class="inline-block animate-spin h-4 w-4 border-2 border-blue-500 border-t-transparent rounded-full mr-2"
                    ></span>
                    読み込み中...
                </div>
                <div
                    v-else-if="taskCount === 0"
                    class="text-center text-sm text-gray-500 py-2"
                >
                    共有可能なタスクがありません
                </div>
                <div v-else class="text-sm text-gray-600">
                    選択されたタスクが共有されます
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
import { ref, computed, onMounted } from "vue";
import axios from "axios";

export default {
    name: "ShareByLocationModal",

    emits: ["close", "shared"],

    setup(props, { emit }) {
        // State
        const shareEmail = ref("");
        const permission = ref("view");
        const taskCount = ref(0);
        const isLoading = ref(true);
        const isSharing = ref(false);
        const errorMessage = ref("");
        const successMessage = ref("");
        const slackAuthRequired = ref(true);

        // Computed properties
        const canShare = computed(() => {
            return (
                shareEmail.value &&
                shareEmail.value.includes("@") &&
                taskCount.value > 0
            );
        });

        // Load task count
        const loadTaskCount = async () => {
            isLoading.value = true;
            try {
                const response = await axios.get("/api/todos", {
                    params: { count_only: true, status: "pending" },
                });
                taskCount.value = response.data.count || 0;
            } catch (error) {
                console.error("Failed to load task count:", error);
                errorMessage.value = "タスク情報の取得に失敗しました";
                taskCount.value = 0;
            } finally {
                isLoading.value = false;
            }
        };

        // Share selected tasks
        const shareSelectedTasks = async () => {
            if (!canShare.value) return;

            isSharing.value = true;
            errorMessage.value = "";
            successMessage.value = "";

            try {
                // Create a new category for the tasks
                const now = new Date();
                const dateStr = `${now.getFullYear()}/${(now.getMonth() + 1).toString().padStart(2, "0")}/${now.getDate().toString().padStart(2, "0")}`;
                const categoryName = `共有タスク (${dateStr})`;

                // Create a category
                const categoryResponse = await axios.post("/api/categories", {
                    name: categoryName,
                    color: "#3182CE", // Blue
                });

                const categoryId = categoryResponse.data.id;

                // Get all pending tasks
                const tasksResponse = await axios.get("/api/todos", {
                    params: { status: "pending" },
                });

                // Update tasks to use the new category
                const updatePromises = tasksResponse.data.map((task) =>
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

                successMessage.value = `${taskCount.value}件のタスクを共有しました！`;

                // Wait a moment to show success message before closing
                setTimeout(() => {
                    emit("shared", {
                        taskCount: taskCount.value,
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

        // Lifecycle hooks
        onMounted(() => {
            loadTaskCount();
        });

        return {
            shareEmail,
            permission,
            taskCount,
            isLoading,
            isSharing,
            errorMessage,
            successMessage,
            slackAuthRequired,
            canShare,
            shareSelectedTasks,
        };
    },
};
</script>
