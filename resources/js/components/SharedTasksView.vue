<template>
    <div>
        <div class="mb-4 flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900">共有タスク</h2>
            <div class="flex space-x-2">
                <button
                    @click="viewMode = 'list'"
                    class="px-3 py-1 rounded-md text-sm"
                    :class="
                        viewMode === 'list'
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                    "
                >
                    リスト表示
                </button>
                <button
                    @click="viewMode = 'calendar'"
                    class="px-3 py-1 rounded-md text-sm"
                    :class="
                        viewMode === 'calendar'
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                    "
                >
                    カレンダー表示
                </button>
                <button
                    @click="$emit('back')"
                    class="text-sm text-blue-600 hover:text-blue-800"
                >
                    ← タスク一覧に戻る
                </button>
            </div>
        </div>

        <!-- List View -->
        <div v-if="viewMode === 'list'">
            <!-- Loading indicator -->
            <div v-if="isLoading" class="text-center py-10">
                <svg
                    class="animate-spin h-8 w-8 text-blue-500 mx-auto"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                <p class="mt-2 text-gray-600">読み込み中...</p>
            </div>

            <!-- Empty state -->
            <div
                v-else-if="sharedTasks.length === 0"
                class="bg-white rounded-lg shadow-sm p-8 text-center"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-12 w-12 mx-auto text-gray-400 mb-3"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"
                    />
                </svg>
                <h3 class="text-lg font-medium text-gray-700 mb-2">
                    共有されたタスクはありません
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    他のユーザーからタスクが共有されるとここに表示されます
                </p>
            </div>

            <!-- Task list -->
            <div v-else class="bg-white rounded-lg shadow-sm">
                <ul class="divide-y divide-gray-100">
                    <li
                        v-for="task in sharedTasks"
                        :key="task.id"
                        class="p-4 hover:bg-gray-50"
                    >
                        <div class="flex justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <!-- Checkbox -->
                                    <input
                                        type="checkbox"
                                        :checked="task.status === 'completed'"
                                        @change="toggleTask(task)"
                                        :disabled="
                                            task.pivot.permission === 'view'
                                        "
                                        class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500"
                                    />

                                    <!-- Task title -->
                                    <p
                                        class="ml-3 font-medium"
                                        :class="
                                            task.status === 'completed'
                                                ? 'line-through text-gray-500'
                                                : 'text-gray-900'
                                        "
                                    >
                                        {{ task.title }}
                                    </p>

                                    <!-- Task category -->
                                    <span
                                        v-if="task.category"
                                        class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium"
                                        :style="{
                                            backgroundColor: getCategoryColor(
                                                task.category.color,
                                            ),
                                            color: task.category.color,
                                        }"
                                    >
                                        {{ task.category.name }}
                                    </span>
                                </div>

                                <!-- Task metadata -->
                                <div class="mt-1 text-sm text-gray-500">
                                    <div class="flex items-center space-x-4">
                                        <!-- Owner -->
                                        <div class="flex items-center">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 mr-1"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                                />
                                            </svg>
                                            <span>{{ task.user.name }}</span>
                                        </div>

                                        <!-- Due date -->
                                        <div
                                            v-if="task.due_date"
                                            class="flex items-center"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 mr-1"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                />
                                            </svg>
                                            <span>{{
                                                formatDate(task.due_date)
                                            }}</span>
                                        </div>

                                        <!-- Due time -->
                                        <div
                                            v-if="task.due_time"
                                            class="flex items-center"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 mr-1"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                            <span>{{
                                                formatTime(task.due_time)
                                            }}</span>
                                        </div>

                                        <!-- Permission -->
                                        <div class="flex items-center">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 mr-1"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                                />
                                            </svg>
                                            <span>{{
                                                task.pivot.permission === "edit"
                                                    ? "編集可能"
                                                    : "閲覧のみ"
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action buttons -->
                            <div class="flex items-start space-x-2">
                                <button
                                    v-if="task.pivot.permission === 'edit'"
                                    @click="editTask(task)"
                                    class="text-blue-600 hover:text-blue-800"
                                    title="編集"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Calendar View -->
        <shared-tasks-calendar-view
            v-if="viewMode === 'calendar'"
            :initial-shared-users="sharedUsers"
            :initial-shared-tasks="sharedTasks"
            :categories="categories"
            :current-user-id="currentUserId"
            @task-updated="loadSharedTasks"
            @task-created="loadSharedTasks"
            @task-deleted="loadSharedTasks"
        />
    </div>
</template>

<script>
import { ref, onMounted, computed } from "vue";
import TaskShareApi from "../api/taskShare";
import TodoApi from "../api/todo";
import SharedTasksCalendarView from "./SharedTasksCalendarView.vue";

export default {
    name: "SharedTasksView",

    components: {
        SharedTasksCalendarView,
    },

    emits: ["back", "edit-task"],

    setup(props, { emit }) {
        // State
        const sharedTasks = ref([]);
        const sharedUsers = ref([]);
        const categories = ref([]);
        const isLoading = ref(true);
        const currentUserId = ref(null);
        const viewMode = ref("list"); // 'list' or 'calendar'

        // Methods
        const loadSharedTasks = async () => {
            isLoading.value = true;
            try {
                const response = await TaskShareApi.getSharedWithMe();
                sharedTasks.value = response.data;

                // Extract unique users from shared tasks for the calendar view
                const uniqueUsers = new Map();

                // Add the current user first
                if (window.Laravel && window.Laravel.user) {
                    const currentUser = window.Laravel.user;
                    currentUserId.value = currentUser.id;
                    uniqueUsers.set(currentUser.id, {
                        id: currentUser.id,
                        name: currentUser.name,
                        email: currentUser.email,
                    });
                }

                // Add users who shared tasks with the current user
                sharedTasks.value.forEach((task) => {
                    if (task.user && !uniqueUsers.has(task.user.id)) {
                        uniqueUsers.set(task.user.id, {
                            id: task.user.id,
                            name: task.user.name,
                            email: task.user.email,
                        });
                    }
                });

                sharedUsers.value = Array.from(uniqueUsers.values());
            } catch (error) {
                console.error("Error loading shared tasks:", error);
            } finally {
                isLoading.value = false;
            }
        };

        const loadCategories = async () => {
            try {
                const response = await axios.get("/api/web-categories");
                categories.value = response.data || [];
            } catch (error) {
                console.error("Error loading categories:", error);
            }
        };

        const toggleTask = async (task) => {
            // Only allow toggling if user has edit permission
            if (task.pivot.permission !== "edit") return;

            try {
                const response = await TodoApi.toggleTask(task.id);

                // Update the task status in the UI
                const index = sharedTasks.value.findIndex(
                    (t) => t.id === task.id,
                );
                if (index !== -1) {
                    sharedTasks.value[index].status = response.data.todo.status;
                }
            } catch (error) {
                console.error("Error toggling task status:", error);
            }
        };

        const editTask = (task) => {
            emit("edit-task", task);
        };

        const formatDate = (dateString) => {
            if (!dateString) return "";

            const date = new Date(dateString);
            return date.toLocaleDateString("ja-JP", {
                year: "numeric",
                month: "long",
                day: "numeric",
            });
        };

        const formatTime = (timeString) => {
            if (!timeString) return "";

            const date = new Date(timeString);
            return date.toLocaleTimeString("ja-JP", {
                hour: "2-digit",
                minute: "2-digit",
                hour12: false,
            });
        };

        const getCategoryColor = (hexColor) => {
            if (!hexColor) return "rgba(156, 163, 175, 0.15)";

            // Convert hex to rgba with low opacity
            const hex = hexColor.replace("#", "");
            const r = parseInt(hex.slice(0, 2), 16);
            const g = parseInt(hex.slice(2, 4), 16);
            const b = parseInt(hex.slice(4, 6), 16);

            return `rgba(${r}, ${g}, ${b}, 0.15)`;
        };

        // Lifecycle
        onMounted(() => {
            loadSharedTasks();
            loadCategories();

            // Get current user ID
            if (window.Laravel && window.Laravel.user) {
                currentUserId.value = window.Laravel.user.id;
            }
        });

        return {
            sharedTasks,
            sharedUsers,
            categories,
            isLoading,
            viewMode,
            currentUserId,
            toggleTask,
            editTask,
            formatDate,
            formatTime,
            getCategoryColor,
            loadSharedTasks,
        };
    },
};
</script>
