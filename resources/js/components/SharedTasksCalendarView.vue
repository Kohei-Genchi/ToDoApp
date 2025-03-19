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
                <!-- 新しいユーザー共有ボタンを追加 -->
                <button
                    @click="openGlobalShareModal"
                    class="ml-2 px-2 py-1 text-sm bg-green-600 text-white hover:bg-green-700 rounded flex items-center"
                    title="ユーザーを追加して全てのタスクを共有"
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
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"
                        />
                    </svg>
                    ユーザー共有
                </button>
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

        <task-share-modal
            v-if="showGlobalShareModal"
            :task="selectedGlobalTask"
            @close="handleGlobalShareModalClose"
        />

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
import GlobalShareApi from "../api/globalShare";
import TaskShareModal from "./TaskShareModal.vue";

export default {
    name: "SharedTasksCalendarView",

    components: {
        TaskModal,
        TaskShareModal,
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
        const globalShares = ref([]);

        // Task modal state
        const showTaskModal = ref(false);
        const taskModalMode = ref("add");
        const selectedTaskId = ref(null);
        const selectedTaskData = ref(null);
        const tempSelectedUser = ref(null);
        const tempSelectedHour = ref(null);

        const showGlobalShareModal = ref(false);
        const selectedGlobalTask = ref(null);

        function openGlobalShareModal() {
            // ダミータスクオブジェクトを作成
            selectedGlobalTask.value = {
                id: "global-share", // 特別なID
                title: "全てのタスク", // 全てのタスクを共有することを示すタイトル
                isGlobalShare: true, // グローバル共有フラグ
            };
            showGlobalShareModal.value = true;
        }

        // Generate all hours from 8:00 to 20:00 (including every hour)
        const fullHours = Array.from({ length: 13 }, (_, i) => i + 8); // 8 to 20

        // Methods
        /**
         * Load shared tasks
         */
        const loadSharedTasks = async () => {
            isLoading.value = true;
            console.log("Loading shared tasks...");

            try {
                // Get current user's info
                const currentUser = window.Laravel?.user;
                currentUserId.value = currentUser?.id;

                // First, load users to display in the calendar
                try {
                    // Get individually shared users - these are users who have individual tasks shared with the current user
                    const sharedResponse = await TaskShareApi.getSharedWithMe();
                    const sharedWithMeUsers = new Map();

                    // Extract unique users who have shared tasks
                    sharedResponse.data.forEach((task) => {
                        if (task.user && !sharedWithMeUsers.has(task.user.id)) {
                            sharedWithMeUsers.set(task.user.id, {
                                id: task.user.id,
                                name: task.user.name,
                                email: task.user.email,
                            });
                        }
                    });

                    // Get globally shared users - these are users who have shared all their tasks globally
                    const globallySharedResponse =
                        await GlobalShareApi.getGlobalShares();

                    console.log(
                        "Globally shared users:",
                        globallySharedResponse.data,
                    );

                    // Store global shares for later use
                    globalShares.value = globallySharedResponse.data;

                    // Add global shares to users
                    globallySharedResponse.data.forEach((share) => {
                        if (!sharedWithMeUsers.has(share.user_id)) {
                            sharedWithMeUsers.set(share.user_id, {
                                id: share.user_id,
                                name: share.name,
                                email: share.email,
                                isGlobalShare: true,
                                globalSharePermission: share.permission,
                            });
                        }
                    });

                    // Start with current user
                    const uniqueUsers = new Map();
                    if (currentUser) {
                        uniqueUsers.set(currentUser.id, {
                            id: currentUser.id,
                            name: currentUser.name,
                            email: currentUser.email,
                        });
                    }

                    // Add users who have shared with current user
                    sharedWithMeUsers.forEach((user, id) => {
                        uniqueUsers.set(id, user);
                    });

                    // Update shared users (for the calendar columns)
                    sharedUsers.value = Array.from(uniqueUsers.values());
                    console.log("Shared users:", sharedUsers.value);
                } catch (error) {
                    console.error("Error loading shared users:", error);
                }

                // Now load all tasks
                let allTasks = [];

                // 1. Load individually shared tasks
                try {
                    const sharedResponse = await TaskShareApi.getSharedWithMe();
                    console.log(
                        "Individually shared tasks:",
                        sharedResponse.data,
                    );
                    allTasks = [...sharedResponse.data];
                } catch (error) {
                    console.error(
                        "Error loading individually shared tasks:",
                        error,
                    );
                }

                // 2. Load globally shared tasks
                try {
                    const globallySharedResponse =
                        await GlobalShareApi.getGloballySharedWithMe();
                    console.log(
                        "Globally shared tasks:",
                        globallySharedResponse.data,
                    );

                    // Add flag to identify these as globally shared
                    const globalTasks = globallySharedResponse.data.map(
                        (task) => ({
                            ...task,
                            isGloballyShared: true,
                        }),
                    );

                    allTasks = [...allTasks, ...globalTasks];
                } catch (error) {
                    console.error(
                        "Error loading globally shared tasks:",
                        error,
                    );
                }

                // APIの成功のみを考慮して、グローバル共有ユーザーのタスクを取得
                if (globalShares.value.length > 0) {
                    console.log("Loading tasks from global share users");

                    // グローバル共有ユーザーのIDを取得
                    const globalShareUserIds = globalShares.value.map(share => Number(share.user_id));

                    // 各ユーザーのタスクを取得
                    for (const userId of globalShareUserIds) {
                        try {
                            // 通常のタスク取得APIを使用
                            const userTasksResponse = await TodoApi.getTasks(
                                "date",
                                currentDate.value,
                                { user_id: userId }
                            );

                            if (Array.isArray(userTasksResponse.data)) {
                                // タスクにグローバル共有フラグを追加
                                const userGlobalTasks = userTasksResponse.data.map(task => ({
                                    ...task,
                                    isGloballyShared: true,
                                    ownerInfo: `Shared by ${globalShares.value.find(share => Number(share.user_id) === userId)?.name || 'User'}`
                                }));

                                allTasks = [...allTasks, ...userGlobalTasks];
                                console.log(`Added ${userGlobalTasks.length} tasks from user ${userId}`);
                            }
                        } catch (userTaskError) {
                            console.error(`Error loading tasks for user ${userId}:`, userTaskError);
                        }
                    }
                }

                // 3. Load current user's own tasks
                if (currentUser) {
                    try {
                        const ownTasksResponse = await TodoApi.getTasks(
                            "date",
                            currentDate.value,
                        );

                        console.log(
                            "Current user's tasks:",
                            ownTasksResponse.data,
                        );

                        if (Array.isArray(ownTasksResponse.data)) {
                            allTasks = [...allTasks, ...ownTasksResponse.data];
                        }
                    } catch (error) {
                        console.error("Error loading own tasks:", error);
                    }
                }

                console.log("All tasks before processing:", allTasks);

                // Process tasks and remove duplicates
                const taskMap = new Map();

                allTasks.forEach((task) => {
                    // Skip if already processed (prevent duplicates)
                    if (taskMap.has(task.id)) return;

                    // Ensure proper shared_with array
                    if (!task.shared_with) {
                        task.shared_with = [];
                    }

                    // Add processed task to map
                    taskMap.set(task.id, task);
                });

                // Convert map back to array
                sharedTasks.value = Array.from(taskMap.values());

                console.log("Processed tasks:", sharedTasks.value);
            } catch (error) {
                console.error("Error in loadSharedTasks:", error);
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

        // Load global shares - APIの成功のみを考慮
        const loadGlobalShares = async () => {
            try {
                // APIからグローバル共有情報を取得
                const response = await GlobalShareApi.getGlobalShares();
                if (response.data && Array.isArray(response.data)) {
                    // コンポーネントの状態に保存
                    globalShares.value = response.data;
                    console.log(
                        "Loaded global shares from API:",
                        globalShares.value,
                    );

                    // 共有ユーザー情報を更新
                    response.data.forEach((share) => {
                        // 既存のユーザーリストに存在しない場合のみ追加
                        const existingUserIndex = sharedUsers.value.findIndex(
                            (user) => user.id === share.user_id,
                        );

                        if (existingUserIndex === -1) {
                            sharedUsers.value.push({
                                id: share.user_id,
                                name: share.name,
                                email: share.email,
                                pivot: { permission: share.permission },
                                globalShareId: share.id,
                            });
                        }
                    });
                }
            } catch (error) {
                console.error("Error loading global shares from API:", error);
                // エラーハンドリングのみ行い、localStorageへの依存を削除
                globalShares.value = [];
            }
        };
        // This is a debug function to add to the component setup
        // Add this after loadGlobalShares function
        const debugGlobalSharing = () => {
            console.log("DEBUG - Current global shares:", globalShares.value);
            console.log("DEBUG - Current users:", sharedUsers.value);

            // Check if globalShares is being correctly populated
            if (globalShares.value.length === 0) {
                console.warn(
                    "No global shares found - this might cause tasks not to display correctly",
                );
            }

            // Check task sharing structure
            if (sharedTasks.value.length > 0) {
                console.log(
                    "DEBUG - Sample task structure:",
                    JSON.stringify(sharedTasks.value[0], null, 2),
                );
            }
        };

        // Call this at the end of loadSharedTasks function
        debugGlobalSharing();

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

        const getTasksForHourAndUser = (hour, userId) => {
            if (!userId || sharedTasks.value.length === 0) {
                return [];
            }

            // Debug logging for specific hour
            const isDebugHour = hour === 9;
            if (isDebugHour) {
                console.log(
                    `Looking for tasks at ${hour}:00 for user ${userId}`,
                );
            }

            const matchingTasks = [];
            userId = Number(userId);

            // Find tasks for this user and hour
            for (const task of sharedTasks.value) {
                try {
                    // Basic task data
                    const taskId = task.id;
                    const taskUserId = Number(task.user_id);
                    const isCurrentUserTask =
                        taskUserId === currentUserId.value;

                    // Determine if task should be shown in this user's column
                    let shouldDisplayInColumn = false;

                    // Case 1: If the task belongs to this user
                    if (taskUserId === userId) {
                        shouldDisplayInColumn = true;
                    }

                    // Case 2: If the task is individually shared with this user
                    else if (
                        task.shared_with &&
                        task.shared_with.some(
                            (share) =>
                                Number(share.user_id) === userId ||
                                Number(share.shared_with_user_id) === userId,
                        )
                    ) {
                        shouldDisplayInColumn = true;
                    }

                    // Case 3: If the task is globally shared
                    else if (task.isGloballyShared) {
                        // For global sharing, check if the owner has shared with this column's user
                        const isSharedGlobally = globalShares.value.some(
                            (share) =>
                                Number(share.user_id) === taskUserId &&
                                Number(share.shared_with_user_id) === userId,
                        );

                        shouldDisplayInColumn = isSharedGlobally;
                    }

                    // Case 4: Check if this task should be displayed based on global shares
                    else if (globalShares.value.length > 0) {
                        // APIの成功だけを考慮して、グローバル共有の条件を修正

                        // 1. タスク所有者のカラムの場合は表示（自分のタスクは自分のカラムに表示）
                        if (userId === taskUserId) {
                            shouldDisplayInColumn = true;
                        }
                        // 2. グローバル共有されたタスクの場合
                        else {
                            // グローバル共有情報を確認
                            const isTaskOwnerSharing = globalShares.value.some(
                                (share) => Number(share.user_id) === taskUserId
                            );

                            // タスク所有者がグローバル共有している場合、そのタスクを表示
                            if (isTaskOwnerSharing) {
                                shouldDisplayInColumn = true;
                            }
                        }

                        if (isDebugHour) {
                            const isTaskOwnerSharing = globalShares.value.some(
                                (share) => Number(share.user_id) === taskUserId
                            );
                            console.log(`Global sharing check: isTaskOwnerSharing=${isTaskOwnerSharing}, shouldDisplay=${shouldDisplayInColumn}`);
                        }
                    }

                    // 最終手段の行を削除し、正しい条件に基づいてタスクを表示するようにします

                    if (isDebugHour) {
                        console.log(
                            `Task ${task.id} - ${task.title} - Owner: ${taskUserId}, ThisColumn: ${userId}, ShouldDisplay: ${shouldDisplayInColumn}`,
                        );
                    }

                    if (!shouldDisplayInColumn) continue;

                    // Check if task is on the right date/time
                    const taskDate = formatDateForComparison(task.due_date);
                    const dateMatches = taskDate === currentDate.value;

                    if (!dateMatches) continue;
                    if (!task.due_time) continue;

                    // Check the hour
                    const taskHour = extractHour(task.due_time);
                    if (taskHour !== hour) continue;

                    // Task passes all checks, add it to results
                    const taskCopy = { ...task };

                    // Add owner info for shared tasks
                    if (taskUserId !== userId) {
                        taskCopy.ownerInfo = task.user
                            ? task.user.name
                            : "Shared Task";
                    }

                    taskCopy.isCurrentUserTask = isCurrentUserTask;

                    matchingTasks.push(taskCopy);

                    if (isDebugHour) {
                        console.log(`✓ Added task: ${task.id} - ${task.title}`);
                    }
                } catch (error) {
                    console.error("Error processing task:", error, task);
                }
            }

            return matchingTasks;
        };

        // Improved hour extraction
        // Improved hour extraction
        const extractHour = (timeString) => {
            try {
                if (!timeString) return null;

                // Debug for time parsing
                console.log(
                    `Parsing time: ${timeString}, type: ${typeof timeString}`,
                );

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

                // Last resort - try to parse as number
                const num = parseInt(timeString, 10);
                if (!isNaN(num) && num >= 0 && num < 24) {
                    return num;
                }

                console.warn(`Could not parse time: ${timeString}`);
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

            // Check if this is globally shared
            const isGloballyShared = task.isGloballyShared;

            // Add visual indicators
            let specialClasses = "";

            // For globally shared tasks, add special indicator
            if (isGloballyShared) {
                specialClasses += "border-2 border-green-300 ";
            }
            // For individually shared tasks, add dashed border
            else if (isSharedTask) {
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

        // Handle the global share modal close event
        const handleGlobalShareModalClose = (data) => {
            showGlobalShareModal.value = false;

            // If we received shared users data from the modal
            if (data && data.sharedUsers && Array.isArray(data.sharedUsers)) {
                console.log(
                    "Received shared users from modal:",
                    data.sharedUsers,
                );

                // 重要: モーダルから受け取ったユーザー情報をsharedUsers.valueに直接設定
                // 既存のユーザーと新しいユーザーをマージ
                const existingUserIds = new Set(sharedUsers.value.map(user => user.id));

                // 新しいユーザーのみを追加
                data.sharedUsers.forEach(user => {
                    if (!existingUserIds.has(user.id)) {
                        sharedUsers.value.push(user);
                    }
                });

                console.log("Updated shared users:", sharedUsers.value);

                // For backwards compatibility, we'll also store in localStorage
                if (currentUserId.value) {
                    try {
                        const savedUsersKey = `sharedUsers_${currentUserId.value}`;
                        localStorage.setItem(
                            savedUsersKey,
                            JSON.stringify(data.sharedUsers),
                        );
                    } catch (e) {
                        console.error(
                            "Could not save shared users to localStorage:",
                            e,
                        );
                    }
                }
            }

            // Reload shared tasks to refresh the view with the latest API data
            loadSharedTasks();

            // Also reload global shares
            loadGlobalShares();
        };

        // Watch for date changes to reload tasks
        watch(
            () => currentDate.value,
            () => {
                loadSharedTasks();
            },
        );

        // Initialize the component
        onMounted(() => {
            // Get current user ID
            if (window.Laravel && window.Laravel.user) {
                currentUserId.value = window.Laravel.user.id;
                console.log("Current user ID:", currentUserId.value);
            }

            // 重要: 順序を変更 - まずグローバル共有情報を読み込む
            loadGlobalShares();

            // その後、タスクとカテゴリを読み込む
            loadSharedTasks();
            loadCategories();

            // デバッグ情報を表示
            console.log(
                "Component mounted, initial shared users:",
                sharedUsers.value,
            );

            // 追加のデバッグ情報
            console.log(
                "Global shares after initialization:",
                globalShares.value
            );
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
            globalShares,

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
            loadGlobalShares,
            showGlobalShareModal,
            selectedGlobalTask,
            openGlobalShareModal,
            handleGlobalShareModalClose,
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
