<template>
    <div
        class="shared-tasks-calendar bg-white rounded-lg shadow-sm overflow-hidden w-full max-w-full"
    >
        <!-- Loading indicator -->
        <loading-indicator
            v-if="initialLoading"
            message="カレンダーを読み込み中..."
        />

        <!-- Header navigation -->
        <header-navigation
            :formatted-date="formattedCurrentDate"
            @previous-day="previousDay"
            @next-day="nextDay"
            @go-to-today="goToToday"
            @open-global-share="openGlobalShareModal"
        />

        <!-- Global share modal -->
        <task-share-modal
            v-if="showGlobalShareModal"
            :task="selectedGlobalTask"
            @close="handleGlobalShareModalClose"
        />

        <!-- Calendar as a table -->
        <div class="w-full overflow-y-auto max-h-[85vh]">
            <table class="w-full border-collapse table-fixed text-xs">
                <thead class="sticky top-0 z-20">
                    <!-- User names row -->
                    <tr class="bg-white">
                        <!-- Time column header -->
                        <th
                            class="w-6 border-b border-r border-gray-200 px-0.5 py-1 text-left"
                        ></th>
                        <!-- User name headers -->
                        <th
                            v-for="user in sharedUsers"
                            :key="'header-' + user.id"
                            class="border-b border-r border-gray-200 px-1 py-1 text-center font-medium text-gray-800"
                            :style="userColumnStyle"
                        >
                            {{ user.name }}
                        </th>
                    </tr>
                    <!-- Email row -->
                    <tr class="bg-gray-50 sticky top-8 z-10">
                        <!-- Time column header -->
                        <th
                            class="w-6 border-b border-r border-gray-200 px-0.5 py-1 text-xs font-medium text-gray-500 text-center"
                        >
                            時間
                        </th>
                        <!-- User email headers -->
                        <th
                            v-for="user in sharedUsers"
                            :key="'email-' + user.id"
                            class="border-b border-r border-gray-200 px-1 py-1 text-center text-xs text-gray-500"
                            :style="userColumnStyle"
                        >
                            <div class="truncate text-xs">{{ user.email }}</div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Time rows -->
                    <tr
                        v-for="hour in fullHours"
                        :key="'row-' + hour"
                        :data-hour="hour"
                        :class="[isCurrentHour(hour) ? 'bg-blue-50' : '']"
                        class="h-8"
                    >
                        <!-- Time cell -->
                        <td
                            class="border-b border-r border-gray-200 px-0.5 py-0 text-left align-top w-6"
                            :class="[
                                isCurrentHour(hour)
                                    ? 'bg-blue-100 font-bold text-blue-700'
                                    : 'bg-gray-50 text-gray-500',
                            ]"
                        >
                            <div class="text-xs">{{ hour }}</div>

                            <!-- Current time indicator -->
                            <div
                                v-if="isCurrentHour(hour)"
                                class="absolute left-0 w-1 bg-blue-500"
                                :style="getTimeIndicatorStyle(hour)"
                            ></div>
                        </td>

                        <!-- User cells for this hour -->
                        <td
                            v-for="user in sharedUsers"
                            :key="'cell-' + hour + '-' + user.id"
                            class="border-b border-r border-gray-200 p-0.5 align-top"
                            :style="userColumnStyle"
                        >
                            <!-- Tasks for this user at this hour -->
                            <task-cell
                                v-for="task in getTasksForHourAndUser(
                                    hour,
                                    user.id,
                                )"
                                :key="'task-' + task.id"
                                :task="task"
                                :is-owner="isCurrentUserOwner(task)"
                                @update-status="onUpdateTaskStatus"
                                @edit="onEditTask"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Task edit modal -->
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
import {
    ref,
    computed,
    onMounted,
    onBeforeUnmount,
    nextTick,
    watch,
} from "vue";
import TaskModal from "./TaskModal.vue";
import TaskShareModal from "./TaskShareModal.vue";
import TaskCell from "./calendar/TaskCell.vue";
import HeaderNavigation from "./calendar/HeaderNavigation.vue";
import LoadingIndicator from "./common/LoadingIndicator.vue";
import TodoApi from "../api/todo";
import TaskShareApi from "../api/taskShare";
import GlobalShareApi from "../api/globalShare";

// Constants
const HOURS_IN_DAY = 24;

export default {
    name: "SharedTasksCalendarView",

    components: {
        TaskModal,
        TaskShareModal,
        HeaderNavigation,
        LoadingIndicator,
        TaskCell,
    },

    emits: ["task-updated", "task-created", "task-deleted", "back"],

    setup(props, { emit }) {
        // State
        const currentDate = ref(formatTodayDate());
        const sharedUsers = ref([]);
        const currentUserId = ref(0);

        // Tasks and categories
        const sharedTasks = ref([]);
        const categories = ref([]);
        const globalShares = ref([]);

        // UI state
        const isLoading = ref(true);
        const initialLoading = ref(true);

        // Modal state
        const showTaskModal = ref(false);
        const taskModalMode = ref("edit");
        const selectedTaskId = ref(null);
        const selectedTaskData = ref(null);
        const showGlobalShareModal = ref(false);
        const selectedGlobalTask = ref(null);

        // Time tracking for indicator
        const currentMinute = ref(new Date().getMinutes());
        let timeUpdateInterval = null;

        // Hours for the calendar
        const fullHours = Array.from({ length: HOURS_IN_DAY }, (_, i) => i);

        // Calculate user column width
        const userColumnStyle = computed(() => {
            const userCount = sharedUsers.value.length || 5; // Default to 5 if no users
            return {
                width: `calc((100% - 1.5rem) / ${userCount})`,
            };
        });

        // =============== Computed Properties ===============
        const formattedCurrentDate = computed(() => {
            const date = new Date(currentDate.value);
            return date.toLocaleDateString("ja-JP", {
                year: "numeric",
                month: "long",
                day: "numeric",
                weekday: "long",
            });
        });

        // =============== Methods ===============
        // Format today's date in YYYY-MM-DD format
        function formatTodayDate() {
            const today = new Date();
            return formatDateToString(today);
        }

        // Format a date object to YYYY-MM-DD string
        function formatDateToString(date) {
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
        }

        // Check if an hour is the current hour
        function isCurrentHour(hour) {
            const now = new Date();
            return now.getHours() === hour;
        }

        // Get style for time indicator based on current minute
        function getTimeIndicatorStyle(hour) {
            if (!isCurrentHour(hour)) return {};

            const now = new Date();
            const minute = now.getMinutes();
            const percentage = (minute / 60) * 100;

            return {
                height: "100%",
                top: "0",
                opacity: "0.8",
                boxShadow: "0 0 4px rgba(59, 130, 246, 0.5)",
            };
        }

        // Date comparison formatter
        function formatDateForComparison(dateString) {
            if (!dateString) return "";

            try {
                if (
                    typeof dateString === "string" &&
                    /^\d{4}-\d{2}-\d{2}$/.test(dateString)
                ) {
                    return dateString;
                }

                const date = new Date(dateString);
                if (isNaN(date.getTime())) {
                    return "";
                }

                return formatDateToString(date);
            } catch (e) {
                console.error("Date format error:", e);
                return "";
            }
        }

        // Extract hour from time string
        function extractHour(timeString) {
            try {
                if (!timeString) return null;

                if (timeString instanceof Date) {
                    return timeString.getHours();
                }

                if (typeof timeString === "string") {
                    if (timeString.includes("T")) {
                        const date = new Date(timeString);
                        return date.getHours();
                    } else if (timeString.includes(":")) {
                        return parseInt(timeString.split(":")[0], 10);
                    }
                }

                const num = parseInt(timeString, 10);
                if (!isNaN(num) && num >= 0 && num < 24) {
                    return num;
                }

                return null;
            } catch (e) {
                console.error("Time extraction error:", e);
                return null;
            }
        }

        // Get tasks for a specific hour and user
        function getTasksForHourAndUser(hour, userId) {
            if (
                !userId ||
                !sharedTasks.value ||
                sharedTasks.value.length === 0
            ) {
                return [];
            }

            const columnUserId = parseInt(userId, 10);
            if (isNaN(columnUserId)) {
                return [];
            }

            return sharedTasks.value.filter((task) => {
                try {
                    const taskOwnerId = task.user_id
                        ? parseInt(task.user_id, 10)
                        : null;
                    if (taskOwnerId !== columnUserId) return false;

                    const taskDate = formatDateForComparison(task.due_date);
                    if (taskDate !== currentDate.value) return false;

                    if (!task.due_time) return false;
                    const taskHour = extractHour(task.due_time);
                    return taskHour === hour;
                } catch (error) {
                    console.error("Task processing error:", error);
                    return false;
                }
            });
        }

        // Check if current user is the owner of a task
        function isCurrentUserOwner(task) {
            if (!task || !currentUserId.value) return false;
            const taskUserId = parseInt(task.user_id, 10);
            const currentId = parseInt(currentUserId.value, 10);
            return taskUserId === currentId;
        }

        // Update the current minute
        function updateCurrentTime() {
            const now = new Date();
            currentMinute.value = now.getMinutes();
        }

        // Load all shared tasks
        async function loadSharedTasks() {
            isLoading.value = true;

            try {
                await initializeCurrentUser();
                await loadGlobalShares();
                await buildSharedUsersList();
                await loadAllTasksData();
            } catch (error) {
                console.error("Task loading error:", error);
                alert("Failed to load task data.");
            } finally {
                isLoading.value = false;
                initialLoading.value = false;

                nextTick(() => {
                    scrollToCurrentTime();
                });
            }
        }

        // Initialize the current user
        async function initializeCurrentUser() {
            const currentUser = window.Laravel?.user;
            if (currentUser && currentUser.id) {
                currentUserId.value = currentUser.id;
            }
        }

        // Load global shares
        async function loadGlobalShares() {
            try {
                const response = await GlobalShareApi.getGlobalShares();
                globalShares.value = Array.isArray(response.data)
                    ? response.data
                    : [];
            } catch (error) {
                console.error("Global share loading error:", error);
                globalShares.value = [];
            }
        }

        // Build the list of shared users
        async function buildSharedUsersList() {
            const allSharedUsers = new Map();

            // Add current user
            const currentUser = window.Laravel?.user;
            if (currentUser) {
                allSharedUsers.set(currentUser.id, {
                    id: currentUser.id,
                    name: currentUser.name,
                    email: currentUser.email,
                });
            }

            // Add individually shared users
            await addIndividuallySharedUsers(allSharedUsers);

            // Add globally shared users
            addGlobalSharedUsers(allSharedUsers);

            // Update the shared users state
            sharedUsers.value = Array.from(allSharedUsers.values());
        }

        // Add individually shared users
        async function addIndividuallySharedUsers(userMap) {
            try {
                const response = await TaskShareApi.getSharedWithMe();
                if (response && Array.isArray(response.data)) {
                    response.data.forEach((task) => {
                        if (task.user && !userMap.has(task.user.id)) {
                            userMap.set(task.user.id, {
                                id: task.user.id,
                                name: task.user.name,
                                email: task.user.email,
                                individuallyShared: true,
                            });
                        }
                    });
                }
            } catch (error) {
                console.error("Individual share loading error:", error);
            }
        }

        // Add globally shared users
        function addGlobalSharedUsers(userMap) {
            if (!globalShares.value || globalShares.value.length === 0) return;

            globalShares.value.forEach((share) => {
                const userId = Number(share.user_id);
                if (!userMap.has(userId)) {
                    userMap.set(userId, {
                        id: userId,
                        name: share.name || "User " + userId,
                        email: share.email || "",
                        isGlobalShare: true,
                        globalSharePermission: share.permission || "view",
                    });
                } else {
                    const user = userMap.get(userId);
                    user.isGlobalShare = true;
                    user.globalSharePermission = share.permission || "view";
                    userMap.set(userId, user);
                }
            });
        }

        // Load all task data
        async function loadAllTasksData() {
            const allTasks = await fetchAllTasks();

            // Remove duplicates
            const taskMap = new Map();
            allTasks.forEach((task) => {
                if (!taskMap.has(task.id)) {
                    if (!task.shared_with) {
                        task.shared_with = [];
                    }
                    taskMap.set(task.id, task);
                }
            });

            sharedTasks.value = Array.from(taskMap.values());
        }

        // Fetch all tasks
        async function fetchAllTasks() {
            let allTasks = [];

            // 1. Get individually shared tasks
            try {
                const response = await TaskShareApi.getSharedWithMe();
                if (response && Array.isArray(response.data)) {
                    allTasks = [...response.data];
                }
            } catch (error) {
                console.error("Individual shared task loading error:", error);
            }

            // 2. Get globally shared tasks
            try {
                const response = await GlobalShareApi.getGloballySharedWithMe();
                if (response && Array.isArray(response.data)) {
                    const globalTasks = response.data.map((task) => ({
                        ...task,
                        isGloballyShared: true,
                    }));
                    allTasks = [...allTasks, ...globalTasks];
                }
            } catch (error) {
                console.error("Global shared task loading error:", error);
            }

            // 3. Get globally shared user tasks
            const globalUserTasks = await fetchGlobalUserTasks();
            allTasks = [...allTasks, ...globalUserTasks];

            // 4. Get current user's tasks
            if (currentUserId.value) {
                try {
                    const response = await TodoApi.getTasks({
                        view: "date",
                        date: currentDate.value,
                    });

                    if (response && Array.isArray(response.data)) {
                        allTasks = [...allTasks, ...response.data];
                    }
                } catch (error) {
                    console.error("User task loading error:", error);
                }
            }

            return allTasks;
        }

        // Fetch tasks from globally shared users
        async function fetchGlobalUserTasks() {
            let tasks = [];

            if (!globalShares.value.length) return tasks;

            const userIds = globalShares.value.map((share) =>
                typeof share.user_id === "string"
                    ? parseInt(share.user_id, 10)
                    : share.user_id,
            );

            for (const userId of userIds) {
                try {
                    const response = await TodoApi.getTasks({
                        view: "date",
                        date: currentDate.value,
                        user_id: userId,
                    });

                    if (response && Array.isArray(response.data)) {
                        const userTasks = response.data.map((task) => ({
                            ...task,
                            isGloballyShared: true,
                            ownerInfo: `Shared by ${
                                globalShares.value.find(
                                    (share) =>
                                        parseInt(share.user_id, 10) === userId,
                                )?.name || "User " + userId
                            }`,
                        }));
                        tasks = [...tasks, ...userTasks];
                    }
                } catch (error) {
                    console.error(`User ${userId} task loading error:`, error);
                }
            }

            return tasks;
        }

        // Scroll to current time
        function scrollToCurrentTime() {
            const container = document.querySelector(".w-full.overflow-y-auto");
            if (!container) return;

            const now = new Date();
            const currentHour = now.getHours();

            try {
                // Find the current hour row
                const hourRows = container.querySelectorAll("[data-hour]");
                let currentRow = null;

                for (const row of hourRows) {
                    const hourAttr = row.getAttribute("data-hour");
                    if (hourAttr && parseInt(hourAttr, 10) === currentHour) {
                        currentRow = row;
                        break;
                    }
                }

                if (currentRow) {
                    // Scroll to position
                    const containerHeight = container.clientHeight;
                    const rowHeight = currentRow.clientHeight;
                    const rowTop = currentRow.offsetTop;

                    container.scrollTo({
                        top: Math.max(
                            0,
                            rowTop - containerHeight / 2 + rowHeight / 2,
                        ),
                        behavior: "smooth",
                    });
                }
            } catch (error) {
                console.error("Scroll error:", error);
            }
        }

        // Open global share modal
        function openGlobalShareModal() {
            selectedGlobalTask.value = {
                id: "global-share",
                title: "全てのタスク",
                isGlobalShare: true,
            };
            showGlobalShareModal.value = true;
        }

        // Handle global share modal close
        function handleGlobalShareModalClose(data) {
            showGlobalShareModal.value = false;

            if (data?.sharedUsers && Array.isArray(data.sharedUsers)) {
                updateSharedUsersList(data.sharedUsers);
            }

            loadSharedTasks();
            loadGlobalShares();
        }

        // Update shared users list
        function updateSharedUsersList(newUsers) {
            const existingUserIds = new Set(
                sharedUsers.value.map((user) => user.id),
            );

            newUsers.forEach((user) => {
                if (!existingUserIds.has(user.id)) {
                    sharedUsers.value.push(user);
                }
            });

            if (currentUserId.value) {
                try {
                    localStorage.setItem(
                        `sharedUsers_${currentUserId.value}`,
                        JSON.stringify(newUsers),
                    );
                } catch (e) {
                    console.error("Shared user save error:", e);
                }
            }
        }

        // Open task edit modal
        function onEditTask(task) {
            taskModalMode.value = "edit";
            selectedTaskId.value = task.id;

            selectedTaskData.value = {
                ...task,
                _isSharedViewEdit: true,
            };

            showTaskModal.value = true;
        }

        // Close task modal
        function closeTaskModal() {
            showTaskModal.value = false;
            selectedTaskData.value = null;
        }

        // Submit task (create or update)
        async function submitTask(taskData) {
            try {
                if (!taskData.due_date) {
                    taskData.due_date = formatTodayDate();
                }

                let response;
                if (taskModalMode.value === "add") {
                    response = await TodoApi.createTask(taskData);
                    emit("task-created", response.data?.todo);
                } else {
                    response = await TodoApi.updateTask(
                        selectedTaskId.value,
                        taskData,
                    );
                    emit("task-updated", response.data?.todo);
                }

                closeTaskModal();
                await loadSharedTasks();
            } catch (error) {
                console.error("Task save error:", error);
                alert("Failed to save task");
            }
        }

        // Handle task delete
        async function handleTaskDelete(taskId) {
            try {
                await TodoApi.deleteTask(taskId);
                sharedTasks.value = sharedTasks.value.filter(
                    (t) => t.id !== taskId,
                );
                emit("task-deleted", taskId);
                closeTaskModal();
            } catch (error) {
                console.error("Task delete error:", error);
                alert("Failed to delete task");
            }
        }

        // Update task status
        async function onUpdateTaskStatus(taskId, newStatus) {
            try {
                const taskIndex = sharedTasks.value.findIndex(
                    (t) => t.id === taskId,
                );
                if (taskIndex === -1) return;

                const task = sharedTasks.value[taskIndex];
                const originalStatus = task.status;

                // Optimistic update
                sharedTasks.value[taskIndex] = { ...task, status: newStatus };

                try {
                    await TodoApi.updateTask(taskId, { status: newStatus });
                    loadSharedTasks().catch((e) =>
                        console.error("Background update error:", e),
                    );
                } catch (error) {
                    console.error("Status update error:", error);

                    // Revert on error
                    sharedTasks.value[taskIndex] = {
                        ...task,
                        status: originalStatus,
                    };

                    if (error.response && error.response.status === 403) {
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
                console.error("Status update processing error:", error);
                alert("An error occurred while updating task status.");
            }
        }

        // Navigation methods
        function previousDay() {
            const date = new Date(currentDate.value);
            date.setDate(date.getDate() - 1);
            currentDate.value = formatDateToString(date);
        }

        function nextDay() {
            const date = new Date(currentDate.value);
            date.setDate(date.getDate() + 1);
            currentDate.value = formatDateToString(date);
        }

        function goToToday() {
            currentDate.value = formatTodayDate();
        }

        // Watch for date changes
        watch(
            () => currentDate.value,
            () => {
                loadSharedTasks();
            },
        );

        // Lifecycle hooks
        onMounted(() => {
            if (window.Laravel?.user) {
                currentUserId.value = window.Laravel.user.id;
            }

            goToToday();

            // Set initial users for better UX
            sharedUsers.value = [
                {
                    id: currentUserId.value || 0,
                    name: window.Laravel?.user?.name || "Current User",
                    email: window.Laravel?.user?.email || "",
                },
            ];

            // Start time updates
            timeUpdateInterval = setInterval(() => {
                updateCurrentTime();
            }, 60000); // Update every minute

            // Load data
            Promise.all([
                loadGlobalShares().catch((e) =>
                    console.error("Global share load error:", e),
                ),
                // Add other initialization if needed
            ]).then(() => {
                setTimeout(() => {
                    loadSharedTasks().finally(() => {
                        initialLoading.value = false;

                        // Multiple scroll attempts for better reliability
                        scrollToCurrentTime();
                        setTimeout(() => scrollToCurrentTime(), 300);
                        setTimeout(() => scrollToCurrentTime(), 1000);
                    });
                }, 100);
            });
        });

        onBeforeUnmount(() => {
            // Clear interval
            if (timeUpdateInterval) {
                clearInterval(timeUpdateInterval);
            }
        });

        return {
            // State
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
            globalShares,
            initialLoading,
            showGlobalShareModal,
            selectedGlobalTask,
            currentUserId,
            currentMinute,
            userColumnStyle,

            // Methods
            openGlobalShareModal,
            onEditTask,
            closeTaskModal,
            submitTask,
            handleTaskDelete,
            handleGlobalShareModalClose,
            onUpdateTaskStatus,
            scrollToCurrentTime,
            previousDay,
            nextDay,
            goToToday,
            isCurrentHour,
            getTasksForHourAndUser,
            isCurrentUserOwner,
            getTimeIndicatorStyle,
        };
    },
};
</script>

<style scoped>
/* Current time indicator animation */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
    }
    70% {
        box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
    }
}

/* Make sure table cells have consistent width */
table {
    table-layout: fixed;
}

/* Current hour highlight animation */
@keyframes currentTimeHighlight {
    0% {
        background-color: rgba(59, 130, 246, 0.1);
    }
    50% {
        background-color: rgba(59, 130, 246, 0.3);
    }
    100% {
        background-color: rgba(59, 130, 246, 0.1);
    }
}

.scroll-highlight {
    animation: currentTimeHighlight 2s ease-in-out;
}
</style>
