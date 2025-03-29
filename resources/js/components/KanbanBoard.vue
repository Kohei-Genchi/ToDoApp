<template>
    <div class="kanban-container">
        <!-- Header with filters and controls -->
        <div class="bg-white shadow-sm p-4 mb-4 rounded-lg">
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
            >
                <h1 class="text-xl font-semibold text-gray-800">
                    チームタスク管理ボード
                </h1>

                <div class="flex flex-wrap gap-2">
                    <!-- Category filter -->
                    <select
                        v-model="selectedCategoryId"
                        class="bg-white border border-gray-300 rounded-md px-3 py-1 text-sm"
                        @change="applyFilters"
                    >
                        <option value="">全てのカテゴリー</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>

                    <!-- User filter -->
                    <select
                        v-model="selectedUserId"
                        class="bg-white border border-gray-300 rounded-md px-3 py-1 text-sm"
                        @change="applyFilters"
                    >
                        <option value="">全てのメンバー</option>
                        <option
                            v-for="user in teamUsers"
                            :key="user.id"
                            :value="user.id"
                        >
                            {{ user.name }}
                        </option>
                    </select>

                    <!-- Task search -->
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="タスクを検索..."
                            class="bg-white border border-gray-300 rounded-md pl-8 pr-3 py-1 text-sm"
                            @input="applyFilters"
                        />
                        <svg
                            class="absolute left-2 top-1.5 h-4 w-4 text-gray-400"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>

                    <!-- Add task button -->
                    <button
                        @click="openAddTaskModal"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm font-medium flex items-center"
                    >
                        <svg
                            class="h-4 w-4 mr-1"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        新しいタスク
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading indicator -->
        <div v-if="isLoading" class="flex justify-center items-center h-64">
            <div
                class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"
            ></div>
        </div>

        <!-- Error message -->
        <div
            v-if="errorMessage"
            class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
        >
            <strong class="font-bold">エラー!</strong>
            <span class="block sm:inline"> {{ errorMessage }}</span>
            <button
                @click="errorMessage = ''"
                class="absolute top-0 bottom-0 right-0 px-4"
            >
                <svg
                    class="h-6 w-6 text-red-500"
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

        <!-- Kanban board -->
        <div
            v-else-if="!isLoading"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
        >
            <!-- To Do Column -->
            <div
                class="bg-gray-100 rounded-lg p-4"
                @dragover.prevent
                @drop="onDrop($event, 'pending')"
            >
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-medium">To Do</h3>
                    <div class="flex items-center">
                        <span
                            class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full mr-2"
                        >
                            {{ getTasksByStatus("pending").length }}
                        </span>
                        <button
                            @click="openAddTaskModal('pending')"
                            class="bg-white p-1 rounded-full shadow hover:bg-gray-100"
                        >
                            <svg
                                class="w-4 h-4 text-gray-600"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="task in getTasksByStatus('pending')"
                        :key="task.id"
                        class="bg-white rounded shadow p-3 cursor-pointer"
                        draggable="true"
                        @dragstart="onDragStart($event, task.id)"
                        @click="openEditTaskModal(task)"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-sm">
                                {{ task.title }}
                            </h4>
                            <span
                                v-if="task.category"
                                class="w-2 h-2 rounded-full"
                                :style="{
                                    backgroundColor: task.category.color,
                                }"
                            ></span>
                        </div>
                        <div
                            v-if="task.due_date"
                            class="mt-2 text-xs text-gray-500"
                        >
                            {{ formatDate(task.due_date) }}
                            <span v-if="task.due_time">
                                {{ formatTime(task.due_time) }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- In Progress Column -->
            <div
                class="bg-blue-100 rounded-lg p-4"
                @dragover.prevent
                @drop="onDrop($event, 'in_progress')"
            >
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-medium">In Progress</h3>
                    <div class="flex items-center">
                        <span
                            class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full mr-2"
                        >
                            {{ getTasksByStatus("in_progress").length }}
                        </span>
                        <button
                            @click="openAddTaskModal('in_progress')"
                            class="bg-white p-1 rounded-full shadow hover:bg-gray-100"
                        >
                            <svg
                                class="w-4 h-4 text-gray-600"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="task in getTasksByStatus('in_progress')"
                        :key="task.id"
                        class="bg-white rounded shadow p-3 cursor-pointer"
                        draggable="true"
                        @dragstart="onDragStart($event, task.id)"
                        @click="openEditTaskModal(task)"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-sm">
                                {{ task.title }}
                            </h4>
                            <span
                                v-if="task.category"
                                class="w-2 h-2 rounded-full"
                                :style="{
                                    backgroundColor: task.category.color,
                                }"
                            ></span>
                        </div>
                        <div
                            v-if="task.due_date"
                            class="mt-2 text-xs text-gray-500"
                        >
                            {{ formatDate(task.due_date) }}
                            <span v-if="task.due_time">
                                {{ formatTime(task.due_time) }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review Column -->
            <div
                class="bg-yellow-100 rounded-lg p-4"
                @dragover.prevent
                @drop="onDrop($event, 'review')"
            >
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-medium">Review</h3>
                    <div class="flex items-center">
                        <span
                            class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full mr-2"
                        >
                            {{ getTasksByStatus("review").length }}
                        </span>
                        <button
                            @click="openAddTaskModal('review')"
                            class="bg-white p-1 rounded-full shadow hover:bg-gray-100"
                        >
                            <svg
                                class="w-4 h-4 text-gray-600"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="task in getTasksByStatus('review')"
                        :key="task.id"
                        class="bg-white rounded shadow p-3 cursor-pointer"
                        draggable="true"
                        @dragstart="onDragStart($event, task.id)"
                        @click="openEditTaskModal(task)"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-sm">
                                {{ task.title }}
                            </h4>
                            <span
                                v-if="task.category"
                                class="w-2 h-2 rounded-full"
                                :style="{
                                    backgroundColor: task.category.color,
                                }"
                            ></span>
                        </div>
                        <div
                            v-if="task.due_date"
                            class="mt-2 text-xs text-gray-500"
                        >
                            {{ formatDate(task.due_date) }}
                            <span v-if="task.due_time">
                                {{ formatTime(task.due_time) }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed Column -->
            <div
                class="bg-green-100 rounded-lg p-4"
                @dragover.prevent
                @drop="onDrop($event, 'completed')"
            >
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-medium">Completed</h3>
                    <div class="flex items-center">
                        <span
                            class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full mr-2"
                        >
                            {{ getTasksByStatus("completed").length }}
                        </span>
                        <button
                            @click="openAddTaskModal('completed')"
                            class="bg-white p-1 rounded-full shadow hover:bg-gray-100"
                        >
                            <svg
                                class="w-4 h-4 text-gray-600"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="task in getTasksByStatus('completed')"
                        :key="task.id"
                        class="bg-white rounded shadow p-3 cursor-pointer"
                        draggable="true"
                        @dragstart="onDragStart($event, task.id)"
                        @click="openEditTaskModal(task)"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-sm">
                                {{ task.title }}
                            </h4>
                            <span
                                v-if="task.category"
                                class="w-2 h-2 rounded-full"
                                :style="{
                                    backgroundColor: task.category.color,
                                }"
                            ></span>
                        </div>
                        <div
                            v-if="task.due_date"
                            class="mt-2 text-xs text-gray-500"
                        >
                            {{ formatDate(task.due_date) }}
                            <span v-if="task.due_time">
                                {{ formatTime(task.due_time) }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Modal -->
        <task-modal
            v-if="showTaskModal"
            :mode="taskModalMode"
            :todo-id="selectedTaskId"
            :todo-data="selectedTaskData"
            :categories="categories"
            :statuses="[
                { id: 'pending', name: 'To Do' },
                { id: 'in_progress', name: 'In Progress' },
                { id: 'review', name: 'Review' },
                { id: 'completed', name: 'Completed' },
            ]"
            @close="closeTaskModal"
            @submit="submitTask"
            @delete="handleTaskDelete"
            @category-created="loadCategories"
        />
    </div>
</template>

<script>
import {
    ref,
    reactive,
    computed,
    onMounted,
    watch,
    defineAsyncComponent,
} from "vue";

// Import TaskModal component asynchronously
const TaskModal = defineAsyncComponent(() => import("./TaskModal.vue"));

export default {
    name: "KanbanBoard",

    components: {
        TaskModal,
    },

    setup() {
        // State
        const isLoading = ref(true);
        const errorMessage = ref("");
        const allTasks = ref([]);
        const filteredTasks = ref([]);
        const categories = ref([]);
        const teamUsers = ref([]);
        const currentUserId = ref(null);

        // Filters
        const selectedCategoryId = ref("");
        const selectedUserId = ref("");
        const searchQuery = ref("");

        // Task modal state
        const showTaskModal = ref(false);
        const taskModalMode = ref("add");
        const selectedTaskId = ref(null);
        const selectedTaskData = ref(null);
        const initialColumnId = ref(null);

        // Get tasks for a specific status column
        const getTasksByStatus = (status) => {
            return filteredTasks.value.filter((task) => task.status === status);
        };

        // Filter tasks based on selected filters
        const applyFilters = () => {
            filteredTasks.value = allTasks.value.filter((task) => {
                // Category filter
                if (
                    selectedCategoryId.value &&
                    task.category_id != selectedCategoryId.value
                ) {
                    return false;
                }

                // User filter
                if (
                    selectedUserId.value &&
                    task.user_id != selectedUserId.value
                ) {
                    return false;
                }

                // Search query
                if (searchQuery.value) {
                    const query = searchQuery.value.toLowerCase();
                    return task.title.toLowerCase().includes(query);
                }

                return true;
            });
        };

        // Load tasks from API
        const loadTasks = async () => {
            try {
                const response = await fetch("/api/todos?view=all", {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                if (!response.ok) {
                    throw new Error("Failed to load tasks");
                }

                const data = await response.json();

                // Process tasks and convert legacy statuses
                allTasks.value = data.map((task) => {
                    // Convert legacy statuses
                    if (task.status === "ongoing") {
                        task.status = "in_progress";
                    } else if (task.status === "paused") {
                        task.status = "review";
                    }
                    return task;
                });

                console.log(`Loaded ${allTasks.value.length} tasks`);

                // Apply initial filters
                applyFilters();
            } catch (error) {
                console.error("Error loading tasks:", error);
                errorMessage.value =
                    "タスクの読み込みに失敗しました。ページを更新してください。";
            }
        };

        // Load categories from API
        const loadCategories = async () => {
            try {
                const response = await fetch("/api/categories", {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                if (!response.ok) {
                    throw new Error("Failed to load categories");
                }

                const data = await response.json();
                categories.value = data;

                console.log(`Loaded ${categories.value.length} categories`);
            } catch (error) {
                console.error("Error loading categories:", error);
                // Don't show error for categories, as they're not critical
            }
        };

        // Format date
        const formatDate = (dateStr) => {
            if (!dateStr) return "";
            const date = new Date(dateStr);
            return new Intl.DateTimeFormat("ja-JP", {
                year: "numeric",
                month: "2-digit",
                day: "2-digit",
            }).format(date);
        };

        // Format time
        const formatTime = (timeStr) => {
            if (!timeStr) return "";

            // Handle both ISO datetime and time strings
            let hours, minutes;

            if (timeStr.includes("T")) {
                // ISO datetime format
                const date = new Date(timeStr);
                hours = date.getHours();
                minutes = date.getMinutes();
            } else {
                // HH:MM or HH:MM:SS format
                const parts = timeStr.split(":");
                hours = parseInt(parts[0], 10);
                minutes = parseInt(parts[1], 10);
            }

            return `${hours.toString().padStart(2, "0")}:${minutes.toString().padStart(2, "0")}`;
        };

        // Handle task drag start
        const onDragStart = (event, taskId) => {
            event.dataTransfer.effectAllowed = "move";
            event.dataTransfer.setData("text/plain", taskId);
            event.target.classList.add("opacity-50");
        };

        // Handle dropping a task into a new column
        const onDrop = async (event, newStatus) => {
            // Remove dragging classes
            document.querySelectorAll(".opacity-50").forEach((el) => {
                el.classList.remove("opacity-50");
            });

            const taskId = Number(event.dataTransfer.getData("text/plain"));
            if (!taskId) return;

            try {
                const taskIndex = allTasks.value.findIndex(
                    (t) => t.id === taskId,
                );
                if (taskIndex === -1) return;

                const task = allTasks.value[taskIndex];
                const originalStatus = task.status;

                // Only update if status actually changed
                if (originalStatus === newStatus) {
                    console.log(
                        `Task ${taskId} status unchanged (${newStatus})`,
                    );
                    return;
                }

                // Optimistic update
                allTasks.value[taskIndex] = { ...task, status: newStatus };
                applyFilters();

                // Get CSRF token
                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content");

                // Update task status via API
                const response = await fetch(`/api/todos/${taskId}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: JSON.stringify({
                        _method: "PUT",
                        status: newStatus,
                    }),
                });

                if (!response.ok) {
                    throw new Error("Failed to update task status");
                }

                console.log(
                    `Successfully updated task ${taskId} status to ${newStatus}`,
                );
            } catch (error) {
                console.error("Error updating task status:", error);
                errorMessage.value = "タスクのステータス更新に失敗しました。";

                // Reload tasks to ensure correct state
                await loadTasks();
            }
        };

        // Open add task modal
        const openAddTaskModal = (columnId = "pending") => {
            taskModalMode.value = "add";
            selectedTaskId.value = null;
            initialColumnId.value = columnId;

            // Set default data for new task
            selectedTaskData.value = {
                title: "",
                description: "",
                due_date: new Date().toISOString().split("T")[0],
                status: columnId,
                category_id: "",
            };

            showTaskModal.value = true;
        };

        // Open edit task modal
        const openEditTaskModal = (task) => {
            taskModalMode.value = "edit";
            selectedTaskId.value = task.id;
            selectedTaskData.value = { ...task };
            showTaskModal.value = true;
        };

        // Close task modal
        const closeTaskModal = () => {
            showTaskModal.value = false;
            selectedTaskData.value = null;
        };

        // Submit task
        const submitTask = async (taskData) => {
            try {
                // Ensure status is set for new tasks
                if (taskModalMode.value === "add" && initialColumnId.value) {
                    taskData.status = initialColumnId.value;
                }

                // Get CSRF token
                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content");

                let response;
                if (taskModalMode.value === "add") {
                    // Create new task
                    response = await fetch("/api/todos", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": csrfToken,
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        body: JSON.stringify(taskData),
                    });
                } else {
                    // Update existing task
                    response = await fetch(
                        `/api/todos/${selectedTaskId.value}`,
                        {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": csrfToken,
                                "X-Requested-With": "XMLHttpRequest",
                            },
                            body: JSON.stringify({
                                ...taskData,
                                _method: "PUT",
                            }),
                        },
                    );
                }

                if (!response.ok) {
                    throw new Error("Failed to save task");
                }

                closeTaskModal();

                // Reload data to ensure we have the latest tasks
                await loadTasks();
            } catch (error) {
                console.error("Error saving task:", error);
                errorMessage.value = "タスクの保存に失敗しました。";
            }
        };

        // Handle task delete
        const handleTaskDelete = async (taskId) => {
            try {
                // Get CSRF token
                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content");

                const response = await fetch(`/api/todos/${taskId}`, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                if (!response.ok) {
                    throw new Error("Failed to delete task");
                }

                closeTaskModal();

                // Remove from lists
                allTasks.value = allTasks.value.filter((t) => t.id !== taskId);
                applyFilters();
            } catch (error) {
                console.error("Error deleting task:", error);
                errorMessage.value = "タスクの削除に失敗しました。";
            }
        };

        // Initialize
        onMounted(async () => {
            console.log("KanbanBoard component mounted");

            // Get current user ID from Laravel global variable
            if (window.Laravel && window.Laravel.user) {
                currentUserId.value = window.Laravel.user.id;
            }

            // Load all data
            try {
                await Promise.all([loadTasks(), loadCategories()]);

                // Extract unique users from tasks
                const userMap = new Map();
                allTasks.value.forEach((task) => {
                    if (task.user && !userMap.has(task.user.id)) {
                        userMap.set(task.user.id, {
                            id: task.user.id,
                            name: task.user.name,
                            email: task.user.email,
                        });
                    }
                });
                teamUsers.value = Array.from(userMap.values());
            } catch (error) {
                console.error("Error initializing kanban board:", error);
                errorMessage.value = "カンバンボードの初期化に失敗しました。";
            } finally {
                isLoading.value = false;
            }
        });

        // Watch for filter changes to reapply filters
        watch([selectedCategoryId, selectedUserId, searchQuery], () => {
            applyFilters();
        });

        return {
            // State
            isLoading,
            errorMessage,
            allTasks,
            filteredTasks,
            categories,
            teamUsers,
            currentUserId,
            showTaskModal,
            taskModalMode,
            selectedTaskId,
            selectedTaskData,
            selectedCategoryId,
            selectedUserId,
            searchQuery,

            // Methods
            getTasksByStatus,
            applyFilters,
            formatDate,
            formatTime,
            onDragStart,
            onDrop,
            openAddTaskModal,
            openEditTaskModal,
            closeTaskModal,
            submitTask,
            handleTaskDelete,
            loadCategories,
        };
    },
};
</script>

<style scoped>
.kanban-container {
    min-height: calc(100vh - 180px);
}

/* Drag and drop styles */
[draggable="true"] {
    cursor: grab;
}

.opacity-50 {
    opacity: 0.5;
}
</style>
