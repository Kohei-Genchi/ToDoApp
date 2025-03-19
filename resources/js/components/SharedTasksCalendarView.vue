<template>
    <div
        class="shared-tasks-calendar bg-white rounded-lg shadow-sm overflow-hidden"
    >
        <!-- Header with date navigation -->
        <div
            class="p-4 flex justify-between items-center border-b border-gray-200"
        >
            <div class="flex items-center space-x-2">
                <h2 class="text-lg font-medium text-gray-900">共有タスク</h2>
                <span class="text-sm text-gray-500">{{
                    formattedCurrentDate
                }}</span>
            </div>
            <div class="flex space-x-2">
                <button
                    @click="previousDay"
                    class="p-1 rounded hover:bg-gray-100"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-gray-600"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
                <button
                    @click="goToToday"
                    class="px-2 py-1 text-sm text-blue-600 hover:bg-blue-50 rounded"
                >
                    今日
                </button>
                <button @click="nextDay" class="p-1 rounded hover:bg-gray-100">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-gray-600"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Back to task list button -->
        <div class="flex justify-end px-4 py-2">
            <button
                @click="goBackToTaskList"
                class="text-sm text-blue-600 hover:text-blue-800"
            >
                ← タスク一覧に戻る
            </button>
        </div>

        <!-- Calendar Table Layout -->
        <div class="w-full overflow-auto">
            <table class="w-full border-collapse">
                <!-- Header Row -->
                <thead>
                    <tr>
                        <th
                            class="w-16 px-2 py-3 bg-gray-50 border border-gray-200 text-sm font-medium text-gray-500"
                        >
                            時間
                        </th>
                        <th
                            v-for="user in sharedUsers"
                            :key="`header-${user.id}`"
                            class="px-2 py-3 bg-gray-50 border border-gray-200 text-center min-w-40"
                        >
                            <div class="text-sm font-medium truncate">
                                {{ user.name }}
                            </div>
                            <div class="text-xs text-gray-500 truncate">
                                {{ user.email }}
                            </div>
                        </th>
                    </tr>
                </thead>

                <!-- Time & Task Rows -->
                <tbody>
                    <tr v-for="hour in fullHours" :key="`row-${hour}`">
                        <!-- Time Cell -->
                        <td
                            class="w-16 px-2 py-2 border border-gray-200 bg-gray-50 text-left"
                        >
                            <div class="text-xs text-gray-500">
                                {{ formatHour(hour) }}
                            </div>
                        </td>

                        <!-- User Cells for this hour -->
                        <td
                            v-for="user in sharedUsers"
                            :key="`cell-${hour}-${user.id}`"
                            class="border border-gray-200 relative group min-h-[60px] p-0"
                            @click="addTaskAtTime(hour, user.id)"
                        >
                            <!-- Tasks for this hour and user -->
                            <div
                                v-for="task in getTasksForHourAndUser(
                                    hour,
                                    user.id,
                                )"
                                :key="`task-${task.id}`"
                                class="absolute m-1 p-1 text-xs rounded overflow-hidden cursor-pointer transition-all duration-200 hover:shadow-md text-left"
                                :class="getTaskClasses(task)"
                                :style="getTaskPositionStyle(task, hour)"
                                @click.stop="editTask(task)"
                            >
                                <div class="font-medium truncate">
                                    {{ task.title }}
                                </div>
                                <div
                                    v-if="task.due_time"
                                    class="text-xs opacity-75"
                                >
                                    {{ formatTaskTime(task.due_time) }}
                                </div>
                            </div>

                            <!-- Add task button (only shown on hover) -->
                            <div
                                class="absolute inset-0 bg-blue-50 bg-opacity-0 group-hover:bg-opacity-20 flex items-center justify-center opacity-0 group-hover:opacity-100"
                            >
                                <button
                                    class="p-1 rounded-full bg-blue-100 text-blue-600"
                                    @click.stop="addTaskAtTime(hour, user.id)"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
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
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Task modal for adding/editing tasks -->
        <task-modal
            v-if="showTaskModal"
            :mode="taskModalMode"
            :todo-id="selectedTaskId"
            :todo-data="selectedTaskData"
            :categories="categories"
            :current-date="currentDate"
            @close="closeTaskModal"
            @submit="submitTask"
            @delete="handleTaskDelete"
        />
    </div>
</template>

<script>
import { ref, computed, onMounted, watch } from "vue";
import TaskModal from "./TaskModal.vue";
import TodoApi from "../api/todo";
import TaskShareApi from "../api/taskShare";

export default {
    name: "SharedTasksCalendarView",

    components: {
        TaskModal,
    },

    emits: ["task-updated", "task-created", "task-deleted", "back"],

    setup(props, { emit }) {
        // State
        const currentDate = ref(new Date().toISOString().split("T")[0]);
        const sharedUsers = ref([]);
        const sharedTasks = ref([]);
        const categories = ref([]);
        const currentUserId = ref(null);
        const isLoading = ref(true);

        // Task modal state
        const showTaskModal = ref(false);
        const taskModalMode = ref("add");
        const selectedTaskId = ref(null);
        const selectedTaskData = ref(null);
        const tempSelectedUser = ref(null);
        const tempSelectedHour = ref(null);

        // Generate all hours from 8:00 to 20:00 (including every hour)
        const fullHours = Array.from({ length: 13 }, (_, i) => i + 8); // 8 to 20

        // Methods
        const loadSharedTasks = async () => {
            isLoading.value = true;
            try {
                const response = await TaskShareApi.getSharedWithMe();
                sharedTasks.value = response.data;
                console.log("Loaded shared tasks:", sharedTasks.value);

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
                console.log("Shared users:", sharedUsers.value);
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

        // Computed properties
        const formattedCurrentDate = computed(() => {
            const date = new Date(currentDate.value);
            return date.toLocaleDateString("ja-JP", {
                year: "numeric",
                month: "long",
                day: "numeric",
                weekday: "long",
            });
        });

        // Methods
        const formatHour = (hour) => {
            return `${hour}:00`;
        };

        const formatTaskTime = (timeString) => {
            try {
                // Handle different time formats
                if (timeString instanceof Date) {
                    return timeString.toLocaleTimeString("ja-JP", {
                        hour: "2-digit",
                        minute: "2-digit",
                    });
                }

                if (typeof timeString === "string") {
                    if (timeString.includes("T")) {
                        // ISO format
                        const date = new Date(timeString);
                        return date.toLocaleTimeString("ja-JP", {
                            hour: "2-digit",
                            minute: "2-digit",
                        });
                    } else if (timeString.includes(":")) {
                        // HH:MM format
                        return timeString.substr(0, 5);
                    }
                }

                return timeString;
            } catch (e) {
                return timeString;
            }
        };

        const getTasksForHourAndUser = (hour, userId) => {
            // For debugging
            if (sharedTasks.value.length > 0 && hour === 9 && userId) {
                console.log(
                    `Looking for tasks at ${hour}:00 for user ${userId}`,
                );
            }

            const matchingTasks = sharedTasks.value.filter((task) => {
                // Check if task belongs to this user
                const isTaskOwner = task.user_id === userId;

                // Check if task is on the current date
                const taskDate = formatDateForComparison(task.due_date);
                const dateMatches = taskDate === currentDate.value;

                // Check if task is in the current hour
                let hourMatches = false;
                if (task.due_time) {
                    const taskHour = extractHour(task.due_time);
                    hourMatches = taskHour === hour;
                }

                // Debug for specific hour
                if (hour === 9 && isTaskOwner && dateMatches) {
                    console.log(
                        `Task ${task.id} (${task.title}): hour=${extractHour(task.due_time)}, matches=${hourMatches}`,
                    );
                }

                return isTaskOwner && dateMatches && hourMatches;
            });

            // For debugging
            if (matchingTasks.length > 0) {
                console.log(
                    `Found ${matchingTasks.length} tasks for hour ${hour}, user ${userId}`,
                );
            }

            return matchingTasks;
        };

        const extractHour = (timeString) => {
            try {
                if (timeString instanceof Date) {
                    return timeString.getHours();
                }

                if (typeof timeString === "string") {
                    if (timeString.includes("T")) {
                        // ISO format
                        const date = new Date(timeString);
                        return date.getHours();
                    } else if (timeString.includes(":")) {
                        // HH:MM format
                        return parseInt(timeString.split(":")[0], 10);
                    }
                }

                return null;
            } catch (e) {
                console.error("Error extracting hour:", e);
                return null;
            }
        };

        const formatDateForComparison = (dateString) => {
            if (!dateString) return "";

            try {
                // Handle different date formats
                if (dateString instanceof Date) {
                    return dateString.toISOString().split("T")[0];
                }

                if (typeof dateString === "string") {
                    // If already in YYYY-MM-DD format, return as is
                    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
                        return dateString;
                    }

                    // Otherwise parse it
                    const date = new Date(dateString);
                    return date.toISOString().split("T")[0];
                }

                return "";
            } catch (e) {
                console.error("Error formatting date:", e);
                return "";
            }
        };

        const getTaskClasses = (task) => {
            const baseClasses = "w-[95%]";

            // Add status-specific classes
            if (task.status === "completed") {
                return `${baseClasses} bg-gray-100 text-gray-600 line-through`;
            }

            // Add category color if available
            if (task.category) {
                return `${baseClasses} border-l-4 bg-white border-l-[${task.category.color}]`;
            }

            return `${baseClasses} bg-white border-l-4 border-l-blue-500`;
        };

        const getTaskPositionStyle = (task, hour) => {
            // Task is positioned based on its minutes
            // But always aligns to the left
            let minuteOffset = 0;

            if (task.due_time) {
                try {
                    const timeString =
                        typeof task.due_time === "string"
                            ? task.due_time
                            : task.due_time.toISOString();

                    let minutes = 0;

                    if (timeString.includes("T")) {
                        // ISO format
                        const date = new Date(timeString);
                        minutes = date.getMinutes();
                    } else if (timeString.includes(":")) {
                        // HH:MM format
                        minutes = parseInt(timeString.split(":")[1], 10);
                    }

                    minuteOffset = (minutes / 60) * 100;
                } catch (e) {
                    minuteOffset = 0;
                }
            }

            return {
                top: `${minuteOffset}%`,
                left: "0",
                width: "95%",
            };
        };

        const previousDay = () => {
            const date = new Date(currentDate.value);
            date.setDate(date.getDate() - 1);
            currentDate.value = date.toISOString().split("T")[0];
        };

        const nextDay = () => {
            const date = new Date(currentDate.value);
            date.setDate(date.getDate() + 1);
            currentDate.value = date.toISOString().split("T")[0];
        };

        const goToToday = () => {
            currentDate.value = new Date().toISOString().split("T")[0];
        };

        const goBackToTaskList = () => {
            emit("back");
        };

        const addTaskAtTime = (hour, userId) => {
            tempSelectedUser.value = userId;
            tempSelectedHour.value = hour;

            // Set up for new task
            taskModalMode.value = "add";
            selectedTaskId.value = null;

            // Create a date object for the task's due date and time
            const taskDate = new Date(currentDate.value);
            taskDate.setHours(hour, 0, 0, 0);

            selectedTaskData.value = {
                title: "",
                due_date: currentDate.value,
                due_time: `${hour.toString().padStart(2, "0")}:00`,
                start_time: `${hour.toString().padStart(2, "0")}:00`,
                end_time: `${(hour + 1).toString().padStart(2, "0")}:00`,
                category_id: "",
                user_id: userId,
            };

            showTaskModal.value = true;
        };

        const editTask = (task) => {
            taskModalMode.value = "edit";
            selectedTaskId.value = task.id;
            selectedTaskData.value = { ...task };
            showTaskModal.value = true;
        };

        const closeTaskModal = () => {
            showTaskModal.value = false;
            tempSelectedUser.value = null;
            tempSelectedHour.value = null;
        };

        const submitTask = async (taskData) => {
            try {
                // Clone data to avoid modifying original
                const preparedData = { ...taskData };

                // Set user_id from temporary storage if in add mode
                if (taskModalMode.value === "add" && tempSelectedUser.value) {
                    preparedData.user_id = tempSelectedUser.value;
                }

                let response;

                if (taskModalMode.value === "add") {
                    // Create new task
                    response = await TodoApi.createTask(preparedData);

                    // Add new task to the shared tasks list
                    if (response.data && response.data.todo) {
                        sharedTasks.value.push(response.data.todo);
                    }

                    emit("task-created", response.data?.todo);
                } else {
                    // Update existing task
                    response = await TodoApi.updateTask(
                        selectedTaskId.value,
                        preparedData,
                    );

                    // Update task in the shared tasks list
                    if (response.data && response.data.todo) {
                        const index = sharedTasks.value.findIndex(
                            (t) => t.id === response.data.todo.id,
                        );
                        if (index !== -1) {
                            sharedTasks.value[index] = response.data.todo;
                        }
                    }

                    emit("task-updated", response.data?.todo);
                }

                closeTaskModal();

                // Reload tasks to ensure everything is up to date
                loadSharedTasks();
            } catch (error) {
                console.error("Error submitting task:", error);
                alert("タスクの保存に失敗しました");
            }
        };

        const handleTaskDelete = async (taskId) => {
            try {
                await TodoApi.deleteTask(taskId);

                // Remove task from the shared tasks list
                sharedTasks.value = sharedTasks.value.filter(
                    (t) => t.id !== taskId,
                );

                emit("task-deleted", taskId);
                closeTaskModal();
            } catch (error) {
                console.error("Error deleting task:", error);
                alert("タスクの削除に失敗しました");
            }
        };

        // Watch for date changes to reload tasks
        watch(currentDate, () => {
            loadSharedTasks();
        });

        // Initialize the component
        onMounted(() => {
            loadSharedTasks();
            loadCategories();

            // Get current user ID
            if (window.Laravel && window.Laravel.user) {
                currentUserId.value = window.Laravel.user.id;
            }
        });

        return {
            currentDate,
            formattedCurrentDate,
            sharedUsers,
            sharedTasks,
            fullHours,
            showTaskModal,
            taskModalMode,
            selectedTaskId,
            selectedTaskData,

            // Methods
            formatHour,
            formatTaskTime,
            getTasksForHourAndUser,
            getTaskClasses,
            getTaskPositionStyle,
            previousDay,
            nextDay,
            goToToday,
            goBackToTaskList,
            addTaskAtTime,
            editTask,
            closeTaskModal,
            submitTask,
            handleTaskDelete,
            loadSharedTasks,
        };
    },
};
</script>

<style scoped>
td {
    height: 60px;
}

/* Set a min-width for user columns */
table th,
table td {
    min-width: 120px;
}

/* Keep time column at a fixed width */
table th:first-child,
table td:first-child {
    min-width: 60px;
    width: 60px;
}

/* Make position of task container relative to its cell */
td {
    position: relative;
}
</style>
