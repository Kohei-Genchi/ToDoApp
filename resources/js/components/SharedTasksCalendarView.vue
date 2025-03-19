<template>
    <div
        class="shared-tasks-calendar bg-white rounded-lg shadow-sm overflow-hidden w-full"
    >
        <!-- Header with date navigation -->
        <div
            class="p-2 flex justify-between items-center border-b border-gray-200"
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

        <!-- Calendar Table Layout - Increased width and removed overflow constraints -->
        <div class="w-full">
            <table class="w-full border-collapse table-fixed">
                <!-- Header Row -->
                <thead>
                    <tr>
                        <th
                            class="w-16 px-1 py-1 bg-gray-50 border border-gray-200 text-xs font-medium text-gray-500"
                        >
                            時間
                        </th>
                        <th
                            v-for="user in sharedUsers"
                            :key="`header-${user.id}`"
                            class="px-1 py-1 bg-gray-50 border border-gray-200 text-center"
                            style="min-width: 120px; width: auto"
                        >
                            <div class="text-xs font-medium truncate">
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
                            class="w-12 px-1 py-1 border border-gray-200 bg-gray-50 text-left"
                        >
                            <div class="text-xs text-gray-500">
                                {{ formatHour(hour) }}
                            </div>
                        </td>

                        <!-- User Cells for this hour -->
                        <td
                            v-for="user in sharedUsers"
                            :key="`cell-${hour}-${user.id}`"
                            class="border border-gray-200 relative group min-h-[50px] p-0"
                            style="height: 50px"
                            @click="addTaskAtTime(hour, user.id)"
                        >
                            <!-- Tasks for this hour and user - horizontal layout -->
                            <div
                                class="flex flex-col h-full w-full overflow-y-auto p-0.5"
                            >
                                <div
                                    v-for="(
                                        task, index
                                    ) in getTasksForHourAndUser(hour, user.id)"
                                    :key="`task-${task.id}`"
                                    class="mb-0.5 p-1 text-xs rounded overflow-hidden cursor-pointer transition-all duration-200 hover:shadow-md text-left flex-shrink-0"
                                    :class="getTaskClasses(task)"
                                    @click.stop="editTask(task)"
                                >
                                    <div class="flex items-center">
                                        <div class="font-medium truncate mr-1">
                                            {{ task.title }}
                                        </div>
                                        <div
                                            v-if="task.due_time"
                                            class="text-xs opacity-75 whitespace-nowrap"
                                        >
                                            {{ formatTaskTime(task.due_time) }}
                                        </div>
                                    </div>
                                    <!-- Show owner info for shared tasks -->
                                    <div
                                        v-if="task.ownerInfo"
                                        class="text-xs italic text-gray-600"
                                    >
                                        From: {{ task.ownerInfo }}
                                    </div>
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
                // Load shared tasks
                const sharedResponse = await TaskShareApi.getSharedWithMe();
                let allTasks = [...sharedResponse.data];
                console.log("Loaded shared tasks:", sharedResponse.data);

                // Also load the current user's own tasks
                if (window.Laravel && window.Laravel.user) {
                    try {
                        const ownTasksResponse = await TodoApi.getTasks({
                            date: currentDate.value,
                        });

                        if (
                            ownTasksResponse.data &&
                            Array.isArray(ownTasksResponse.data)
                        ) {
                            console.log(
                                "Loaded own tasks:",
                                ownTasksResponse.data,
                            );

                            // Add the current user's tasks to the shared tasks list
                            allTasks = [...allTasks, ...ownTasksResponse.data];
                        }
                    } catch (ownTasksError) {
                        console.error(
                            "Error loading own tasks:",
                            ownTasksError,
                        );
                    }
                }

                // Remove duplicates (in case a task is both shared and owned)
                const taskIds = new Set();
                sharedTasks.value = allTasks.filter((task) => {
                    if (taskIds.has(task.id)) {
                        return false;
                    }
                    taskIds.add(task.id);
                    return true;
                });

                console.log("Combined tasks:", sharedTasks.value);

                // Extract unique users from all tasks for the calendar view
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
                console.error("Error formatting time:", e);
                return timeString;
            }
        };

        // Improved function to get tasks for a specific hour and user
        const getTasksForHourAndUser = (hour, userId) => {
            if (!userId || sharedTasks.value.length === 0) {
                return [];
            }

            // Log for debugging
            if (hour === 9) {
                console.log(
                    `Looking for tasks at ${hour}:00 for user ${userId}`,
                );
            }

            const matchingTasks = [];

            // Loop through all tasks (more robust than filter)
            for (const task of sharedTasks.value) {
                // Check if the task should be displayed in this user's column
                const isTaskOwner = Number(task.user_id) === Number(userId);
                const isSharedWithUser =
                    task.shared_with &&
                    task.shared_with.some(
                        (share) => Number(share.user_id) === Number(userId),
                    );
                const isCurrentUserTask =
                    Number(task.user_id) === Number(currentUserId.value);

                // Display logic:
                // 1. If this is the task owner's column, show the task (each user's tasks in their own column)
                // 2. If this is the current user's column, show tasks shared with them
                const shouldDisplayInColumn =
                    isTaskOwner ||
                    (Number(userId) === Number(currentUserId.value) &&
                        isSharedWithUser);

                if (!shouldDisplayInColumn) continue;

                // Check if task is on the current date - use local date comparison to fix timezone issues
                const taskDate = formatDateForComparison(task.due_date);

                // Debug date comparison
                if (hour === 9) {
                    console.log(
                        `Task date: ${taskDate}, Current date: ${currentDate.value}`,
                    );
                }

                const dateMatches = taskDate === currentDate.value;
                if (!dateMatches) continue;

                // Check if task is in the current hour
                if (!task.due_time) continue;

                const taskHour = extractHour(task.due_time);
                if (taskHour !== hour) continue;

                // Task matches all criteria
                // Create a copy of the task to avoid modifying the original
                const taskCopy = { ...task };

                // Add owner information to the task for display purposes
                if (!isTaskOwner) {
                    taskCopy.ownerInfo = task.user
                        ? task.user.name
                        : "Shared Task";
                }

                // Add a flag to indicate if this is the current user's task
                taskCopy.isCurrentUserTask = isCurrentUserTask;

                matchingTasks.push(taskCopy);

                // Debug for hour 9
                if (hour === 9) {
                    console.log(
                        `Found matching task: ${task.id} - ${task.title} - Owner: ${isTaskOwner ? "Yes" : "No"} - Shared: ${isSharedWithUser ? "Yes" : "No"}`,
                    );
                }
            }

            return matchingTasks;
        };

        // Improved hour extraction
        const extractHour = (timeString) => {
            try {
                if (!timeString) return null;

                if (timeString instanceof Date) {
                    return timeString.getHours();
                }

                if (typeof timeString === "string") {
                    if (timeString.includes("T")) {
                        // ISO format: "2025-03-19T09:00:00.000000Z"
                        const date = new Date(timeString);
                        return date.getHours();
                    } else if (timeString.includes(":")) {
                        // HH:MM or HH:MM:SS format
                        return parseInt(timeString.split(":")[0], 10);
                    }
                }

                return null;
            } catch (e) {
                console.error(
                    "Error extracting hour:",
                    e,
                    "from timeString:",
                    timeString,
                );
                return null;
            }
        };

        // Improved date formatting for comparison
        const formatDateForComparison = (dateString) => {
            if (!dateString) return "";

            try {
                // Check if already in YYYY-MM-DD format
                if (
                    typeof dateString === "string" &&
                    /^\d{4}-\d{2}-\d{2}$/.test(dateString)
                ) {
                    return dateString;
                }

                // Convert date string to local date format
                // Use local timezone to avoid date shifting issues
                const date = new Date(dateString);
                if (isNaN(date.getTime())) {
                    console.error("Invalid date:", dateString);
                    return "";
                }

                // Format to YYYY-MM-DD in local timezone
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, "0");
                const day = String(date.getDate()).padStart(2, "0");

                return `${year}-${month}-${day}`;
            } catch (e) {
                console.error(
                    "Error formatting date:",
                    e,
                    "for dateString:",
                    dateString,
                );
                return "";
            }
        };

        const getTaskClasses = (task) => {
            const baseClasses = "w-full";

            // Check if this is a shared task (has ownerInfo)
            const isSharedTask = !!task.ownerInfo;

            // Check if this is the current user's task
            const isCurrentUserTask = task.isCurrentUserTask;

            // Add visual indicators
            let specialClasses = "";

            // For shared tasks, add dashed border
            if (isSharedTask) {
                specialClasses += "border-dashed border ";
            }

            // For current user's tasks in other columns, add highlight
            if (isCurrentUserTask && isSharedTask) {
                specialClasses += "bg-blue-50 ";
            }

            // Add status-specific classes
            if (task.status === "completed") {
                return `${baseClasses} ${specialClasses} bg-gray-100 text-gray-600 line-through`;
            }

            // Add category color if available
            if (task.category) {
                return `${baseClasses} ${specialClasses} border-l-4 bg-white border-l-[${task.category.color}]`;
            }

            // Default styling with special indicators if needed
            return `${baseClasses} ${specialClasses} bg-white border-l-4 border-l-blue-500`;
        };

        // Function removed as we're no longer using absolute positioning for tasks

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
            categories,

            // Methods
            formatHour,
            formatTaskTime,
            getTasksForHourAndUser,
            getTaskClasses,
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
/* Set a minimum width and explicit height for cells */
td {
    height: 50px;
    padding: 0 !important; /* Remove default padding */
}

/* Optimize width for user columns */
table th,
table td {
    min-width: 120px; /* Reduced from 150px */
}

/* Keep time column at a fixed width */
table th:first-child,
table td:first-child {
    min-width: 40px; /* Reduced from 60px */
    width: 40px;
}

/* Make position of task container relative to its cell */
td {
    position: relative;
    vertical-align: top;
}

/* Fix for table layout */
.table-fixed {
    table-layout: fixed;
    width: 100%;
    border-collapse: collapse;
}

/* Make sure tasks stand out better */
[class*="border-l-"] {
    border-left-width: 4px !important;
}

/* Reduce overall margins and padding */
.shared-tasks-calendar {
    margin: 0;
    padding: 0;
}

/* Compact header */
.shared-tasks-calendar .p-4 {
    padding: 0.5rem !important;
}

/* Make task containers more compact */
.mb-0\.5 {
    margin-bottom: 0.125rem !important;
}

.p-0\.5 {
    padding: 0.125rem !important;
}

/* Ensure task text is readable */
.text-xs {
    font-size: 0.7rem;
    line-height: 1rem;
}

/* Reduce header height */
th {
    padding-top: 0.5rem !important;
    padding-bottom: 0.5rem !important;
}

/* Optimize space in the back button area */
.px-2.py-1 {
    padding: 0.25rem 0.5rem !important;
}
</style>
