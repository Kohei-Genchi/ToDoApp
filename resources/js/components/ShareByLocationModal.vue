<!-- ShareByLocationModal.vue -->
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
            <h3 class="text-lg font-medium mb-4">場所でタスクを共有</h3>

            <!-- Location selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                    >共有するタスクの場所</label
                >
                <div class="grid grid-cols-3 gap-2">
                    <button
                        @click="selectedLocation = 'INBOX'"
                        :class="[
                            'px-3 py-2 rounded-md text-center text-sm font-medium',
                            selectedLocation === 'INBOX'
                                ? 'bg-blue-100 text-blue-800 border-2 border-blue-400'
                                : 'bg-gray-100 text-gray-800 hover:bg-gray-200',
                        ]"
                    >
                        未分類
                    </button>
                    <button
                        @click="selectedLocation = 'TODAY'"
                        :class="[
                            'px-3 py-2 rounded-md text-center text-sm font-medium',
                            selectedLocation === 'TODAY'
                                ? 'bg-blue-100 text-blue-800 border-2 border-blue-400'
                                : 'bg-gray-100 text-gray-800 hover:bg-gray-200',
                        ]"
                    >
                        今日
                    </button>
                    <button
                        @click="selectedLocation = 'SCHEDULED'"
                        :class="[
                            'px-3 py-2 rounded-md text-center text-sm font-medium',
                            selectedLocation === 'SCHEDULED'
                                ? 'bg-blue-100 text-blue-800 border-2 border-blue-400'
                                : 'bg-gray-100 text-gray-800 hover:bg-gray-200',
                        ]"
                    >
                        予定
                    </button>
                </div>
            </div>

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
                    共有するタスク数: {{ taskCount }}
                </label>
                <div class="max-h-40 overflow-y-auto bg-gray-50 p-2 rounded-md">
                    <div v-if="isLoading" class="text-center py-4">
                        <span
                            class="inline-block animate-spin h-5 w-5 border-2 border-blue-500 border-t-transparent rounded-full mr-2"
                        ></span>
                        タスクを読み込み中...
                    </div>
                    <div
                        v-else-if="tasks.length === 0"
                        class="text-center py-4 text-gray-500"
                    >
                        該当するタスクはありません
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

            <!-- Actions -->
            <div class="flex justify-end space-x-2">
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200"
                >
                    キャンセル
                </button>
                <button
                    @click="shareTasksByLocation"
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
import { ref, computed, onMounted, watch } from "vue";
import axios from "axios";
import TaskSharingService from "./TaskSharingService";

export default {
    name: "ShareByLocationModal",

    emits: ["close", "shared"],

    setup(props, { emit }) {
        // State
        const selectedLocation = ref("TODAY");
        const shareEmail = ref("");
        const permission = ref("view");
        const tasks = ref([]);
        const isLoading = ref(false);
        const isSharing = ref(false);
        const errorMessage = ref("");
        const successMessage = ref("");
        const slackAuthRequired = ref(true);

        // Computed properties
        const taskCount = computed(() => tasks.value.length);

        const canShare = computed(() => {
            return (
                selectedLocation.value &&
                shareEmail.value &&
                shareEmail.value.includes("@") &&
                tasks.value.length > 0
            );
        });

        const loadTasksByLocation = async () => {
            if (!selectedLocation.value) return;

            isLoading.value = true;
            errorMessage.value = "";

            try {
                // Make sure we're explicitly sending the selected location as a query parameter
                const response = await axios.get("/api/todos", {
                    params: {
                        location: selectedLocation.value, // This is the key fix - ensure we're using the selected location
                        status: "pending",
                    },
                });

                console.log(
                    "Loaded tasks for location:",
                    selectedLocation.value,
                    response.data,
                );

                tasks.value = response.data || [];
            } catch (error) {
                console.error("Failed to load tasks:", error);
                errorMessage.value = "タスクの読み込みに失敗しました";
                tasks.value = [];
            } finally {
                isLoading.value = false;
            }
        };

        // Method to get a location display name
        const getLocationDisplayName = (location) => {
            switch (location) {
                case "INBOX":
                    return "未分類";
                case "TODAY":
                    return "今日";
                case "SCHEDULED":
                    return "予定";
                default:
                    return location;
            }
        };

        const shareTasksByLocation = async () => {
            if (!canShare.value) return;

            isSharing.value = true;
            errorMessage.value = "";
            successMessage.value = "";

            try {
                // Use the TaskSharingService to handle the sharing process
                const result = await TaskSharingService.shareTasksByLocation(
                    selectedLocation.value,
                    shareEmail.value,
                    permission.value,
                    slackAuthRequired.value,
                );

                successMessage.value = `${result.taskCount}件のタスクを共有しました！`;

                // Wait a moment to show success message before closing
                setTimeout(() => {
                    emit("shared", {
                        location: selectedLocation.value,
                        email: shareEmail.value,
                        taskCount: result.taskCount,
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

        const getColorForLocation = (location) => {
            switch (location) {
                case "INBOX":
                    return "#4A5568"; // Gray
                case "TODAY":
                    return "#3182CE"; // Blue
                case "SCHEDULED":
                    return "#805AD5"; // Purple
                default:
                    return "#3182CE"; // Default blue
            }
        };

        watch(selectedLocation, (newLocation) => {
            console.log("Location changed to:", newLocation);
            loadTasksByLocation();
        });

        // Lifecycle hooks
        onMounted(() => {
            loadTasksByLocation();
        });

        return {
            selectedLocation,
            shareEmail,
            permission,
            tasks,
            isLoading,
            isSharing,
            errorMessage,
            successMessage,
            taskCount,
            canShare,
            shareTasksByLocation,
            slackAuthRequired,
        };
    },
};
</script>
