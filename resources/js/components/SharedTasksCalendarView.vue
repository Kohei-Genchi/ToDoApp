<template>
    <div
        class="shared-tasks-calendar bg-white rounded-lg shadow-sm overflow-hidden w-full"
    >
        <!-- Loading indicator that doesn't hide the entire component -->
        <div
            v-if="initialLoading"
            class="fixed top-4 right-4 bg-white rounded-lg shadow-md p-3 z-50"
        >
            <div class="flex items-center">
                <svg
                    class="animate-spin h-5 w-5 text-blue-500 mr-2"
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
                <span class="text-sm">カレンダーを読み込み中...</span>
            </div>
        </div>

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

        <!-- Flexbox-based layout for perfect alignment -->
        <div class="flex flex-col w-full">
            <!-- Header row with user names -->
            <div
                class="flex border-b border-gray-200 bg-white sticky top-0 z-20"
            >
                <!-- Time column header (fixed width) -->
                <div
                    class="w-16 px-1 py-2 text-xs font-medium text-gray-500 flex-shrink-0"
                >
                    <!-- Empty cell for time column -->
                </div>

                <!-- User name columns (equal flex width) -->
                <div class="flex flex-1">
                    <div
                        v-for="user in sharedUsers"
                        :key="`user-${user.id}`"
                        class="flex-1 min-w-[250px] px-2 py-2 text-center border-l border-gray-200"
                    >
                        <div class="text-sm font-medium text-gray-800 truncate">
                            {{ user.name }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email row header -->
            <div
                class="flex border-b border-gray-200 bg-gray-50 sticky top-8 z-10"
            >
                <!-- Time header (fixed width) -->
                <div
                    class="w-16 px-1 py-1 text-xs font-medium text-gray-500 text-center flex-shrink-0"
                >
                    時間
                </div>

                <!-- Email columns (equal flex width) -->
                <div class="flex flex-1">
                    <div
                        v-for="user in sharedUsers"
                        :key="`header-${user.id}`"
                        class="flex-1 min-w-[250px] px-1 py-1 text-center border-l border-gray-200"
                    >
                        <div class="text-xs text-gray-500 truncate">
                            {{ user.email }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar container with flexbox layout -->
            <div
                class="w-full overflow-y-auto max-h-[70vh]"
                ref="calendarContainer"
            >
                <!-- Time rows using flex -->
                <div
                    v-for="hour in fullHours"
                    :key="`row-${hour}`"
                    :data-hour="hour"
                    :class="[
                        'flex border-b border-gray-200 min-h-[60px]',
                        isCurrentHour(hour) ? 'bg-blue-50 relative' : '',
                    ]"
                >
                    <!-- Time Cell (fixed width) -->
                    <div
                        class="w-16 px-1 py-1 border-r border-gray-200 text-left flex-shrink-0"
                        :class="[
                            isCurrentHour(hour)
                                ? 'bg-blue-100 font-bold text-blue-700'
                                : 'bg-gray-50 text-gray-500',
                        ]"
                    >
                        <div
                            class="text-xs"
                            :class="{ 'text-blue-700': isCurrentHour(hour) }"
                        >
                            {{ formatHour(hour) }}
                        </div>
                    </div>

                    <div
                        v-if="isCurrentHour(hour)"
                        class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"
                    ></div>

                    <!-- User cell columns (equal flex width) -->
                    <div class="flex flex-1">
                        <div
                            v-for="user in sharedUsers"
                            :key="`cell-${hour}-${user.id}`"
                            class="flex-1 min-w-[250px] border-r border-gray-200 relative min-h-12 p-0.5 overflow-hidden border-l border-gray-200"
                        >
                            <!-- Tasks for this hour and user -->
                            <div
                                v-for="task in getTasksForHourAndUser(
                                    hour,
                                    user.id,
                                )"
                                :key="`task-${task.id}`"
                                class="mb-1 py-1.5 px-2 text-xs rounded overflow-hidden transition-colors duration-200 hover:shadow-md text-left flex items-center"
                                :class="getTaskStatusClasses(task)"
                            >
                                <!-- Status Dropdown -->
                                <select
                                    v-model="task.status"
                                    @change="
                                        updateTaskStatus(task.id, task.status)
                                    "
                                    class="mr-2 text-xs rounded py-1 px-2 font-medium border shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="getSelectClasses(task.status)"
                                >
                                    <option value="pending">待機</option>
                                    <option value="ongoing">進行</option>
                                    <option value="paused">中断</option>
                                    <option value="completed">完了</option>
                                </select>

                                <!-- Task Title and Time -->
                                <div
                                    class="flex flex-col flex-1 overflow-hidden"
                                >
                                    <div class="font-medium text-sm truncate">
                                        {{ task.title }}
                                    </div>
                                    <div class="flex items-center mt-0.5">
                                        <div
                                            v-if="task.due_time"
                                            class="text-xs opacity-75 mr-2"
                                        >
                                            <span
                                                class="bg-gray-100 px-1 py-0.5 rounded inline-flex items-center"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-3 w-3 mr-0.5"
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
                                                {{
                                                    formatTaskTime(
                                                        task.due_time,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            v-if="task.category"
                                            class="text-xs"
                                        >
                                            <span
                                                class="px-1.5 py-0.5 rounded"
                                                :style="{
                                                    backgroundColor: `${task.category.color}25`,
                                                    color: task.category.color,
                                                }"
                                            >
                                                {{ task.category.name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit button -->
                                <button
                                    v-if="isCurrentUserOwner(task)"
                                    @click.stop="editTask(task)"
                                    class="ml-1 text-gray-500 hover:text-blue-600 flex-shrink-0"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Task modal for editing tasks (no longer for adding) -->
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
    watch,
    nextTick,
    onBeforeUnmount,
} from "vue";
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
        // Fix: Use local timezone date string instead of ISO string to correctly handle the timezone
        const today = new Date();
        const currentDate = ref(
            `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`,
        );
        const sharedUsers = ref([]);
        const sharedTasks = ref([]);
        const categories = ref([]);
        const currentUserId = ref(null);
        const isLoading = ref(true);
        const initialLoading = ref(true);
        const globalShares = ref([]);
        const calendarContainer = ref(null);

        // Task modal state
        const showTaskModal = ref(false);
        const taskModalMode = ref("edit"); // Changed default to "edit" since we're removing "add"
        const selectedTaskId = ref(null);
        const selectedTaskData = ref(null);

        const showGlobalShareModal = ref(false);
        const selectedGlobalTask = ref(null);

        // Status colors mapping - MODIFIED FOR NEW STATUSES
        const statusColors = {
            pending: {
                bg: "bg-gray-100",
                text: "text-gray-700",
                select: "bg-gray-100 text-gray-700",
            },
            ongoing: {
                bg: "bg-blue-100",
                text: "text-blue-700",
                select: "bg-blue-100 text-blue-700",
            },
            paused: {
                bg: "bg-yellow-100",
                text: "text-yellow-700",
                select: "bg-yellow-100 text-yellow-700",
            },
            completed: {
                bg: "bg-green-100",
                text: "text-green-700",
                select: "bg-green-100 text-green-700",
            },
        };

        // Hours array for the calendar
        const fullHours = Array.from({ length: 24 }, (_, i) => i); // 0 to 23

        function openGlobalShareModal() {
            // ダミータスクオブジェクトを作成
            selectedGlobalTask.value = {
                id: "global-share", // 特別なID
                title: "全てのタスク", // 全てのタスクを共有することを示すタイトル
                isGlobalShare: true, // グローバル共有フラグ
            };
            showGlobalShareModal.value = true;
        }

        // Methods

        // Get CSS classes based on task status
        function getTaskStatusClasses(task) {
            const statusStyle =
                statusColors[task.status] || statusColors["pending"];

            // Base classes - improved visibility and contrast
            const baseClasses = `${statusStyle.bg} ${statusStyle.text} w-full`;

            // Special classes for shared or global tasks
            const specialClasses = task.isGloballyShared
                ? "border-2 border-green-300 "
                : task.ownerInfo
                  ? "border-dashed border "
                  : "";

            // Combine with task-specific classes
            return `${baseClasses} ${specialClasses}`;
        }

        // Get CSS classes for the status select dropdown
        function getSelectClasses(status) {
            return (statusColors[status] || statusColors["pending"]).select;
        }

        // Update task status
        async function updateTaskStatus(taskId, newStatus) {
            try {
                // Find the task in the sharedTasks array
                const taskIndex = sharedTasks.value.findIndex(
                    (t) => t.id === taskId,
                );
                if (taskIndex === -1) {
                    console.error("Task not found:", taskId);
                    return;
                }

                const task = sharedTasks.value[taskIndex];

                // Store original status in case we need to revert
                const originalStatus = task.status;

                // IMPORTANT: Create a local copy of the task to avoid direct binding issues
                const updatedTask = { ...task, status: newStatus };

                // Apply optimistic update - update the local state immediately
                // This prevents UI flickering and gives immediate feedback
                sharedTasks.value[taskIndex] = updatedTask;

                // Only send the status in the update to avoid any date issues
                const updateData = {
                    status: newStatus,
                };

                console.log("Updating task status:", taskId, newStatus);

                // Make API request
                try {
                    await TodoApi.updateTask(taskId, updateData);
                    console.log("Status update successful");

                    // No need to immediately reload tasks since we've already updated the UI
                    // We'll reload in the background to ensure consistency
                    loadSharedTasks().catch((e) =>
                        console.error("Background task reload failed:", e),
                    );
                } catch (error) {
                    // If the API call fails, revert the optimistic update
                    console.error("API error updating task status:", error);

                    // Revert the task status in the local state
                    sharedTasks.value[taskIndex] = {
                        ...updatedTask,
                        status: originalStatus,
                    };

                    // Show appropriate error message
                    if (error.response && error.response.status === 403) {
                        alert(
                            "権限がありません：このタスクを更新する権限がありません。",
                        );
                    } else {
                        alert(
                            "タスクのステータス更新に失敗しました: " +
                                (error.response?.data?.error || error.message),
                        );
                    }
                }
            } catch (error) {
                console.error("Error in updateTaskStatus:", error);
                alert("タスクのステータス更新中にエラーが発生しました。");
            }
        }

        // Check if the given hour is the current hour
        function isCurrentHour(hour) {
            const now = new Date();
            return now.getHours() === hour;
        }

        function scrollToCurrentTime() {
            if (!calendarContainer.value) return;

            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();

            try {
                // Find the row for the current hour using data-hour attribute
                const hourRows =
                    calendarContainer.value.querySelectorAll("[data-hour]");
                let currentRow = null;
                let currentRowOffsetTop = 0;

                // Find the current hour row
                for (const row of hourRows) {
                    const hourAttr = row.getAttribute("data-hour");
                    if (hourAttr && parseInt(hourAttr, 10) === currentHour) {
                        currentRow = row;
                        currentRowOffsetTop = row.offsetTop;
                        break;
                    }
                }

                if (currentRow) {
                    // Calculate position to center the current time
                    const containerHeight =
                        calendarContainer.value.clientHeight;
                    const rowHeight = currentRow.clientHeight;

                    // Calculate minute offset to position more precisely within the hour
                    const minuteOffset = (currentMinute / 60) * rowHeight;

                    // Position calculation: position the current time line in the middle of the viewport
                    const scrollPosition =
                        currentRowOffsetTop +
                        minuteOffset -
                        containerHeight / 2 +
                        rowHeight / 2;

                    // Scroll with smooth animation
                    calendarContainer.value.scrollTo({
                        top: Math.max(0, scrollPosition),
                        behavior: "smooth",
                    });

                    // Add the current time indicator
                    addCurrentTimeIndicator();

                    console.log(
                        `Scrolled to hour: ${currentHour}:${currentMinute}`,
                    );
                }
            } catch (error) {
                console.error("Error during scrollToCurrentTime:", error);
            }
        }

        /**
         * Load shared tasks
         */
        async function loadSharedTasks() {
            isLoading.value = true;

            try {
                // 現在のユーザー情報を取得
                const currentUser = window.Laravel?.user;
                currentUserId.value = currentUser?.id;

                // まずグローバル共有情報を読み込む
                try {
                    // APIからグローバル共有情報を取得
                    const globalSharesResponse =
                        await GlobalShareApi.getGlobalShares();

                    // レスポンスを検証
                    if (globalSharesResponse && globalSharesResponse.data) {
                        // グローバル共有情報を保存
                        globalShares.value = Array.isArray(
                            globalSharesResponse.data,
                        )
                            ? globalSharesResponse.data
                            : [];
                    } else {
                        console.warn(
                            "Global shares API returned unexpected response:",
                            globalSharesResponse,
                        );
                        globalShares.value = [];
                    }
                } catch (error) {
                    globalShares.value = [];
                }

                // 共有ユーザーマップを初期化
                const allSharedUsers = new Map();

                // 現在のユーザーを追加
                if (currentUser) {
                    allSharedUsers.set(currentUser.id, {
                        id: currentUser.id,
                        name: currentUser.name,
                        email: currentUser.email,
                    });
                }

                // 1. 個別共有ユーザーを読み込む
                try {
                    const sharedResponse = await TaskShareApi.getSharedWithMe();

                    // 個別のタスク共有からユーザーを追加
                    if (sharedResponse && Array.isArray(sharedResponse.data)) {
                        sharedResponse.data.forEach((task) => {
                            if (
                                task.user &&
                                !allSharedUsers.has(task.user.id)
                            ) {
                                allSharedUsers.set(task.user.id, {
                                    id: task.user.id,
                                    name: task.user.name,
                                    email: task.user.email,
                                    individuallyShared: true,
                                });
                            }
                        });
                    }
                } catch (error) {
                    console.error(
                        "Error loading individually shared tasks:",
                        error,
                    );
                }

                // 2. グローバル共有ユーザーを追加
                if (globalShares.value && globalShares.value.length > 0) {
                    globalShares.value.forEach((share) => {
                        // user_id を数値として処理
                        const userId = Number(share.user_id);

                        if (!allSharedUsers.has(userId)) {
                            allSharedUsers.set(userId, {
                                id: userId,
                                name: share.name || "User " + userId,
                                email: share.email || "",
                                isGlobalShare: true,
                                globalSharePermission:
                                    share.permission || "view",
                            });
                        } else {
                            // 既存のユーザーにグローバル共有フラグを追加
                            const user = allSharedUsers.get(userId);
                            user.isGlobalShare = true;
                            user.globalSharePermission =
                                share.permission || "view";
                            allSharedUsers.set(userId, user);
                        }
                    });
                }

                // カレンダー表示用のユーザー一覧を更新
                sharedUsers.value = Array.from(allSharedUsers.values());

                // すべてのタスクをロード
                let allTasks = [];

                // 1. 個別共有タスクをロード
                try {
                    const sharedResponse = await TaskShareApi.getSharedWithMe();
                    if (sharedResponse && Array.isArray(sharedResponse.data)) {
                        allTasks = [...sharedResponse.data];
                    }
                } catch (error) {
                    console.error(
                        "Error loading individually shared tasks:",
                        error,
                    );
                }

                // 2. グローバル共有タスクをロード
                try {
                    const globallySharedResponse =
                        await GlobalShareApi.getGloballySharedWithMe();
                    if (
                        globallySharedResponse &&
                        Array.isArray(globallySharedResponse.data)
                    ) {
                        // グローバル共有としてフラグ付け
                        const globalTasks = globallySharedResponse.data.map(
                            (task) => ({
                                ...task,
                                isGloballyShared: true,
                            }),
                        );

                        allTasks = [...allTasks, ...globalTasks];
                    }
                } catch (error) {
                    console.error(
                        "Error loading globally shared tasks:",
                        error,
                    );
                }

                // 3. グローバル共有されているユーザーのタスクを個別に取得
                if (globalShares.value.length > 0) {
                    console.log("Loading tasks from global share users");

                    // グローバル共有ユーザーのIDをリストアップ
                    const globalShareUserIds = globalShares.value.map(
                        (share) =>
                            typeof share.user_id === "string"
                                ? parseInt(share.user_id, 10)
                                : share.user_id,
                    );

                    // 各ユーザーのタスクを取得
                    for (const userId of globalShareUserIds) {
                        try {
                            // user_id パラメータを明示的に含めてAPI呼び出し
                            const userTasksResponse = await TodoApi.getTasks({
                                view: "date",
                                date: currentDate.value,
                                user_id: userId,
                            });

                            if (
                                userTasksResponse &&
                                Array.isArray(userTasksResponse.data)
                            ) {
                                const userSharedTasks =
                                    userTasksResponse.data.map((task) => ({
                                        ...task,
                                        isGloballyShared: true,
                                        ownerInfo: `Shared by ${
                                            globalShares.value.find(
                                                (share) =>
                                                    parseInt(
                                                        share.user_id,
                                                        10,
                                                    ) === userId,
                                            )?.name || "User " + userId
                                        }`,
                                    }));

                                allTasks = [...allTasks, ...userSharedTasks];
                            }
                        } catch (userTaskError) {
                            console.error(
                                `Error loading tasks for user ${userId}:`,
                                userTaskError,
                            );
                        }
                    }
                }

                // 4. 現在のユーザーの自身のタスクをロード
                if (currentUser) {
                    try {
                        const ownTasksResponse = await TodoApi.getTasks({
                            view: "date",
                            date: currentDate.value,
                        });

                        if (
                            ownTasksResponse &&
                            Array.isArray(ownTasksResponse.data)
                        ) {
                            console.log(
                                "Current user's tasks:",
                                ownTasksResponse.data,
                            );
                            allTasks = [...allTasks, ...ownTasksResponse.data];
                        }
                    } catch (error) {
                        console.error("Error loading own tasks:", error);
                    }
                }

                // タスクを処理し、重複を除去
                const taskMap = new Map();

                allTasks.forEach((task) => {
                    // すでに処理済みならスキップ（重複防止）
                    if (taskMap.has(task.id)) return;

                    // 必要なプロパティがない場合は初期化
                    if (!task.shared_with) {
                        task.shared_with = [];
                    }

                    // マップに処理済みタスクを追加
                    taskMap.set(task.id, task);
                });

                // マップを配列に戻す
                sharedTasks.value = Array.from(taskMap.values());
            } catch (error) {
                console.error("Error in loadSharedTasks:", error);
            } finally {
                isLoading.value = false;
                initialLoading.value = false;

                // After loading tasks, scroll to current time
                nextTick(() => {
                    scrollToCurrentTime();
                });
            }
        }

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
                globalShares.value = [];
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

        const getTasksForHourAndUser = (hour, userId) => {
            if (
                !userId ||
                !sharedTasks.value ||
                sharedTasks.value.length === 0
            ) {
                return [];
            }

            // Force consistent number type for userId to ensure proper comparison
            const columnUserId = parseInt(userId, 10);
            if (isNaN(columnUserId)) {
                console.warn("Invalid userId provided:", userId);
                return [];
            }

            const matchingTasks = [];

            // Loop through each task to determine if it belongs in this column
            for (const task of sharedTasks.value) {
                try {
                    // Ensure IDs are always treated as numbers for comparison
                    const taskId = task.id;
                    const taskOwnerId = task.user_id
                        ? parseInt(task.user_id, 10)
                        : null;

                    // IMPORTANT: Check if this task belongs directly to this user's column
                    // This is the key fix - we only want tasks that belong to THIS user in THEIR column
                    if (taskOwnerId !== columnUserId) {
                        continue; // Skip if the task doesn't belong to this user
                    }

                    // Check if the task's date matches the current date
                    const taskDate = formatDateForComparison(task.due_date);
                    if (taskDate !== currentDate.value) {
                        continue;
                    }

                    // Check if the task has a due time and if it matches the current hour
                    if (!task.due_time) {
                        continue;
                    }

                    // Extract hour from task time and check if it matches the current hour
                    const taskHour = extractHour(task.due_time);
                    if (taskHour !== hour) {
                        continue;
                    }

                    // This task belongs to this user and time slot - add it
                    matchingTasks.push({ ...task });
                } catch (error) {
                    console.error("Error processing task:", error, task);
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

        const previousDay = () => {
            const date = new Date(currentDate.value);
            date.setDate(date.getDate() - 1);
            currentDate.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
        };

        const nextDay = () => {
            const date = new Date(currentDate.value);
            date.setDate(date.getDate() + 1);
            currentDate.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
        };

        const goToToday = () => {
            // Fix: Set to current local date, not using ISO string which could cause timezone issues
            const today = new Date();
            currentDate.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
        };

        const goBackToTaskList = () => {
            emit("back");
        };

        const editTask = (task) => {
            taskModalMode.value = "edit";
            selectedTaskId.value = task.id;

            // 共有タスクビューから編集するときのフラグを追加
            const isSharedViewEdit = true;

            // タスクデータに共有ビューからの編集フラグを追加
            selectedTaskData.value = {
                ...task,
                _isSharedViewEdit: isSharedViewEdit,
            };

            showTaskModal.value = true;
        };

        const closeTaskModal = () => {
            showTaskModal.value = false;
        };

        // タスクの所有者チェック関数
        const isCurrentUserOwner = (task) => {
            if (!task || !currentUserId.value) return false;

            // Convert IDs to number for comparison
            const taskUserId = parseInt(task.user_id, 10);
            const currentId = parseInt(currentUserId.value, 10);

            return taskUserId === currentId;
        };

        const submitTask = async (taskData) => {
            try {
                // Clone data to avoid modifying original
                const preparedData = { ...taskData };

                // 現在の日付をデフォルトに設定
                if (!preparedData.due_date) {
                    const today = new Date();
                    const year = today.getFullYear();
                    const month = String(today.getMonth() + 1).padStart(2, "0");
                    const day = String(today.getDate()).padStart(2, "0");
                    preparedData.due_date = `${year}-${month}-${day}`;
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
                const existingUserIds = new Set(
                    sharedUsers.value.map((user) => user.id),
                );

                // 新しいユーザーのみを追加
                data.sharedUsers.forEach((user) => {
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

        // Set up an interval to update the current time highlight
        let timeUpdateInterval = null;

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
            }

            // Fix: Set to current local date on mount
            goToToday();

            // Start by loading minimal data to get the layout ready
            sharedUsers.value = [
                {
                    id: currentUserId.value || 0,
                    name: window.Laravel?.user?.name || "Current User",
                    email: window.Laravel?.user?.email || "",
                },
            ];

            // Start loading critical data immediately, don't wait
            loadGlobalShares().catch((e) =>
                console.error("Error loading global shares:", e),
            );
            loadCategories().catch((e) =>
                console.error("Error loading categories:", e),
            );

            // Load task data with minimal delay
            setTimeout(() => {
                loadSharedTasks().finally(() => {
                    initialLoading.value = false;

                    // Implement multiple scroll attempts to ensure it works
                    // First attempt - immediate
                    scrollToCurrentTime();

                    // Second attempt - after a short delay for DOM to settle
                    setTimeout(() => {
                        scrollToCurrentTime();
                    }, 300);

                    // Final attempt - after all rendering should be complete
                    setTimeout(() => {
                        scrollToCurrentTime();
                    }, 1000);
                });
            }, 100);

            // Set up interval to update current time highlight every minute
            timeUpdateInterval = setInterval(() => {
                // This will update the highlighting for the current hour
                // by triggering a re-render of the component
                nextTick(() => {
                    // Force component update to refresh current time highlight
                    sharedTasks.value = [...sharedTasks.value];

                    // Re-scroll to the current time every hour
                    const now = new Date();
                    if (now.getMinutes() === 0) {
                        scrollToCurrentTime();
                    }
                });
            }, 60000); // Update every minute
        });

        // Add current time indicator
        const addCurrentTimeIndicator = () => {
            if (!calendarContainer.value) return;

            // Remove any existing indicator
            const existingIndicator = document.getElementById(
                "current-time-indicator",
            );
            if (existingIndicator) {
                existingIndicator.remove();
            }

            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();

            // Find the current hour row
            const hourRows =
                calendarContainer.value.querySelectorAll("[data-hour]");
            let currentRow = null;

            for (const row of hourRows) {
                const hourAttr = row.getAttribute("data-hour");
                if (hourAttr && parseInt(hourAttr, 10) === currentHour) {
                    currentRow = row;
                    break;
                }
            }

            if (currentRow) {
                // Create the indicator
                const indicator = document.createElement("div");
                indicator.id = "current-time-indicator";

                // Add Tailwind classes
                indicator.className =
                    "absolute w-full h-0.5 bg-red-500 z-30 shadow-md transform -translate-y-1/2";

                // Calculate position based on minutes
                const rowHeight = currentRow.clientHeight;
                const minutePercentage = currentMinute / 60;
                const topOffset =
                    currentRow.offsetTop + rowHeight * minutePercentage;

                indicator.style.top = `${topOffset}px`;
                indicator.style.left = "0";
                indicator.style.right = "0";

                // Add a time label to the indicator
                const timeLabel = document.createElement("div");
                timeLabel.className =
                    "absolute left-0 -translate-y-1/2 bg-red-500 text-white text-xs px-1 py-0.5 rounded-r shadow";
                timeLabel.textContent = `${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}`;
                indicator.appendChild(timeLabel);

                // Add the indicator to the calendar container
                calendarContainer.value.appendChild(indicator);
            }
        };

        // 既存のonMountedフック内に追加
        onMounted(() => {
            // ... 既存のコード ...

            // Add current time indicator
            addCurrentTimeIndicator();

            // Update indicator every minute
            const indicatorInterval = setInterval(() => {
                addCurrentTimeIndicator();
            }, 60000);

            // Clean up interval when component is unmounted
            onBeforeUnmount(() => {
                clearInterval(indicatorInterval);
            });
        });

        // Clean up interval when component is unmounted
        onBeforeUnmount(() => {
            if (timeUpdateInterval) {
                clearInterval(timeUpdateInterval);
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
            globalShares,
            calendarContainer,
            initialLoading,

            // Methods
            formatHour,
            formatTaskTime,
            getTasksForHourAndUser,
            getTaskStatusClasses,
            getSelectClasses,
            previousDay,
            nextDay,
            goToToday,
            goBackToTaskList,
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
            updateTaskStatus,
            isCurrentHour,
            scrollToCurrentTime,
            isCurrentUserOwner,
        };
    },
};
</script>

<style scoped>
/* Current time indicator styles */
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

#current-time-indicator {
    animation: pulse 2s infinite;
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
