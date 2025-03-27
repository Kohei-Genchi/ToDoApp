<template>
    <div class="kanban-container">
        <!-- Header with filters and controls -->
        <div class="bg-white shadow-sm p-4 mb-4 rounded-lg">
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
            >
                <h1 class="text-xl font-semibold text-gray-800">
                    Team Kanban Board
                </h1>

                <div class="flex flex-wrap gap-2">
                    <!-- Category filter -->
                    <select
                        v-model="selectedCategoryId"
                        class="bg-white border border-gray-300 rounded-md px-3 py-1 text-sm"
                        @change="applyFilters"
                    >
                        <option value="">All Categories</option>
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
                        <option value="">All Team Members</option>
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
                            placeholder="Search tasks..."
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
                        Add Task
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

        <!-- Kanban board -->
        <div v-else class="kanban-board flex space-x-4 overflow-x-auto pb-4">
            <kanban-column
                v-for="column in columns"
                :key="column.id"
                :column="column"
                :tasks="getTasksByStatus(column.id)"
                @add-task="openAddTaskModal(column.id)"
                @edit-task="openEditTaskModal"
                @drop="handleDrop"
            />
        </div>

        <!-- Task Modal -->
        <task-modal
            v-if="showTaskModal"
            :mode="taskModalMode"
            :todo-id="selectedTaskId"
            :todo-data="selectedTaskData"
            :categories="categories"
            :statuses="columns.map((c) => ({ id: c.id, name: c.name }))"
            @close="closeTaskModal"
            @submit="submitTask"
            @delete="handleTaskDelete"
        />
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from "vue";
import KanbanColumn from "./kanban/KanbanColumn.vue";
import TaskModal from "./TaskModal.vue";
import TodoApi from "../api/todo";
import CategoryApi from "../api/category";

export default {
    name: "KanbanBoard",

    components: {
        KanbanColumn,
        TaskModal,
    },

    setup() {
        // State
        const isLoading = ref(true);
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

        // Kanban columns
        const columns = reactive([
            { id: "pending", name: "To Do", color: "bg-gray-100" },
            { id: "in_progress", name: "In Progress", color: "bg-blue-100" },
            { id: "review", name: "Review", color: "bg-yellow-100" },
            { id: "completed", name: "Completed", color: "bg-green-100" },
        ]);

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

        // Load all data
        const loadData = async () => {
            isLoading.value = true;

            try {
                // Get current user ID
                if (window.Laravel?.user) {
                    currentUserId.value = window.Laravel.user.id;
                }

                // Load categories
                const categoriesResponse = await CategoryApi.getCategories();
                categories.value = categoriesResponse.data;

                // Load shared tasks
                await loadSharedTasks();

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

                // Apply initial filters
                applyFilters();
            } catch (error) {
                console.error("Error loading kanban data:", error);
            } finally {
                isLoading.value = false;
            }
        };

        // Load shared tasks from all available sources
        const loadSharedTasks = async () => {
            try {
                // Load tasks from shared categories
                const response = await fetch("/api/shared-categories/tasks", {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                const sharedCategoryTasks = await response.json();

                // Load user's own tasks
                const ownTasksResponse = await TodoApi.getTasks("all");
                const ownTasks = ownTasksResponse.data;

                // Combine and deduplicate tasks
                const taskMap = new Map();

                // Process shared category tasks
                sharedCategoryTasks.forEach((task) => {
                    // Convert legacy status values for kanban
                    task.status = convertStatusForKanban(task.status);
                    taskMap.set(task.id, task);
                });

                // Process own tasks
                ownTasks.forEach((task) => {
                    // Convert legacy status values for kanban
                    task.status = convertStatusForKanban(task.status);
                    taskMap.set(task.id, task);
                });

                // Convert to array
                allTasks.value = Array.from(taskMap.values());
            } catch (error) {
                console.error("Error loading shared tasks:", error);
            }
        };

        // Convert legacy status values to kanban statuses
        const convertStatusForKanban = (status) => {
            switch (status) {
                case "pending":
                    return "pending";
                case "completed":
                    return "completed";
                case "ongoing":
                    return "in_progress";
                case "paused":
                    return "review";
                default:
                    return "pending";
            }
        };

        // Handle dropping a task into a new column
        const handleDrop = async (taskId, newStatus) => {
            try {
                const taskIndex = allTasks.value.findIndex(
                    (t) => t.id === taskId,
                );
                if (taskIndex === -1) return;

                const task = allTasks.value[taskIndex];
                const originalStatus = task.status;

                // Optimistic update
                allTasks.value[taskIndex] = { ...task, status: newStatus };
                applyFilters();

                // Update task status via API
                try {
                    await TodoApi.updateTask(taskId, { status: newStatus });
                } catch (error) {
                    console.error("Failed to update task status:", error);

                    // Revert on error
                    allTasks.value[taskIndex] = {
                        ...task,
                        status: originalStatus,
                    };
                    applyFilters();

                    // Show error notification
                    if (error.response?.status === 403) {
                        alert(
                            "Permission denied: You cannot update this task.",
                        );
                    } else {
                        alert(
                            "Failed to update task status: " +
                                (error.response?.data?.error || error.message),
                        );
                    }
                }
            } catch (error) {
                console.error("Error handling task drop:", error);
            }
        };

        // Modal functions
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

        const openEditTaskModal = (task) => {
            taskModalMode.value = "edit";
            selectedTaskId.value = task.id;
            selectedTaskData.value = { ...task };
            showTaskModal.value = true;
        };

        const closeTaskModal = () => {
            showTaskModal.value = false;
            selectedTaskData.value = null;
        };

        const submitTask = async (taskData) => {
            try {
                // Ensure status is set for new tasks
                if (taskModalMode.value === "add" && initialColumnId.value) {
                    taskData.status = initialColumnId.value;
                }

                let response;
                if (taskModalMode.value === "add") {
                    response = await TodoApi.createTask(taskData);
                } else {
                    response = await TodoApi.updateTask(
                        selectedTaskId.value,
                        taskData,
                    );
                }

                closeTaskModal();
                await loadSharedTasks();
                applyFilters();
            } catch (error) {
                console.error("Error saving task:", error);
                alert(
                    "Failed to save task: " +
                        (error.response?.data?.error || error.message),
                );
            }
        };

        const handleTaskDelete = async (taskId) => {
            try {
                await TodoApi.deleteTask(taskId);

                // Remove from lists
                allTasks.value = allTasks.value.filter((t) => t.id !== taskId);
                applyFilters();

                closeTaskModal();
            } catch (error) {
                console.error("Error deleting task:", error);
                alert(
                    "Failed to delete task: " +
                        (error.response?.data?.error || error.message),
                );
            }
        };

        // Initialize
        onMounted(() => {
            loadData();
        });

        return {
            isLoading,
            allTasks,
            filteredTasks,
            categories,
            teamUsers,
            columns,
            selectedCategoryId,
            selectedUserId,
            searchQuery,
            showTaskModal,
            taskModalMode,
            selectedTaskId,
            selectedTaskData,
            currentUserId,

            getTasksByStatus,
            applyFilters,
            handleDrop,
            openAddTaskModal,
            openEditTaskModal,
            closeTaskModal,
            submitTask,
            handleTaskDelete,
        };
    },
};
</script>

<style scoped>
.kanban-container {
    height: calc(100vh - 150px);
    display: flex;
    flex-direction: column;
}

.kanban-board {
    flex: 1;
    min-height: 0;
}

/* Custom scrollbar */
.kanban-board::-webkit-scrollbar {
    height: 8px;
}

.kanban-board::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.kanban-board::-webkit-scrollbar-thumb {
    background: #c5c5c5;
    border-radius: 4px;
}

.kanban-board::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
