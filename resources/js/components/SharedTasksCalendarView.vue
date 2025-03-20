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

        const fullHours = Array.from({ length: 24 }, (_, i) => i); // 0 to 23

        // Methods
        /**
         * Load shared tasks
         */
        // SharedTasksCalendarView.vue の loadSharedTasks 関数の修正部分

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

            // デバッグログ用のフラグ
            const isDebugHour = hour === 9;
            if (isDebugHour) {
                console.log(
                    `Looking for tasks at ${hour}:00 for user ${userId}`,
                );
            }

            const matchingTasks = [];
            // 確実に数値として処理するため、parseIntを使用
            const columnUserId = parseInt(userId, 10);

            // ユーザーIDが無効な場合は空の配列を返す
            if (isNaN(columnUserId)) {
                console.warn("Invalid userId provided:", userId);
                return [];
            }

            // 各タスクをチェック
            for (const task of sharedTasks.value) {
                try {
                    // タスクのベーシックデータを取得
                    const taskId = task.id;
                    // 文字列の場合もあるので、parseIntで確実に数値に変換
                    const taskUserId = task.user_id
                        ? parseInt(task.user_id, 10)
                        : null;

                    // 現在のユーザーのタスクかどうか
                    const isCurrentUserTask =
                        taskUserId === currentUserId.value;

                    // このタスクをこのユーザーの列に表示すべきかどうか
                    let shouldDisplayInColumn = false;

                    // ケース1: このユーザーに属するタスク
                    if (taskUserId === columnUserId) {
                        shouldDisplayInColumn = true;
                        if (isDebugHour)
                            console.log(
                                `Task ${taskId} belongs to column user: ${columnUserId}`,
                            );
                    }
                    // ケース2: 個別共有タスク
                    else if (
                        task.shared_with &&
                        Array.isArray(task.shared_with)
                    ) {
                        const isIndividuallyShared = task.shared_with.some(
                            (share) => {
                                // shared_with_user_id または user_id がこの列のユーザーIDと一致するか
                                const shareUserId = parseInt(
                                    share.shared_with_user_id || share.user_id,
                                    10,
                                );
                                return shareUserId === columnUserId;
                            },
                        );

                        if (isIndividuallyShared) {
                            shouldDisplayInColumn = true;
                            if (isDebugHour)
                                console.log(
                                    `Task ${taskId} is individually shared with column user: ${columnUserId}`,
                                );
                        }
                    }
                    // ケース3: グローバル共有タスク
                    else if (task.isGloballyShared) {
                        // グローバル共有の場合、タスク所有者とこの列のユーザーの関係を確認する
                        if (
                            globalShares.value &&
                            globalShares.value.length > 0
                        ) {
                            // このタスクの所有者が、この列のユーザーとグローバル共有しているか
                            const shareRelationship = globalShares.value.some(
                                (share) => {
                                    const shareOwnerId = parseInt(
                                        share.user_id,
                                        10,
                                    );
                                    const shareWithUserId = parseInt(
                                        share.shared_with_user_id,
                                        10,
                                    );

                                    // A) タスク所有者がこの列のユーザーとグローバル共有している
                                    const ownerSharesWithColumnUser =
                                        shareOwnerId === taskUserId &&
                                        shareWithUserId === columnUserId;

                                    // B) この列のユーザーがタスク所有者とグローバル共有している
                                    const columnUserSharesWithOwner =
                                        shareOwnerId === columnUserId &&
                                        shareWithUserId === taskUserId;

                                    return (
                                        ownerSharesWithColumnUser ||
                                        columnUserSharesWithOwner
                                    );
                                },
                            );

                            if (shareRelationship) {
                                shouldDisplayInColumn = true;
                                if (isDebugHour)
                                    console.log(
                                        `Task ${taskId} is globally shared between ${taskUserId} and ${columnUserId}`,
                                    );
                            }
                        }
                    }
                    // ケース4: フォールバック - 常に所有者の列にはタスクを表示
                    else if (taskUserId === columnUserId) {
                        shouldDisplayInColumn = true;
                        if (isDebugHour)
                            console.log(
                                `Task ${taskId} falls back to owner's column: ${columnUserId}`,
                            );
                    }

                    // このユーザーの列に表示すべきでない場合はスキップ
                    if (!shouldDisplayInColumn) continue;

                    // 日付と時間が一致するかチェック
                    const taskDate = formatDateForComparison(task.due_date);
                    const dateMatches = taskDate === currentDate.value;

                    if (!dateMatches) {
                        if (isDebugHour)
                            console.log(
                                `Task ${taskId} date doesn't match: ${taskDate} vs ${currentDate.value}`,
                            );
                        continue;
                    }

                    if (!task.due_time) {
                        if (isDebugHour)
                            console.log(`Task ${taskId} has no due_time`);
                        continue;
                    }

                    // 時間が一致するかチェック
                    const taskHour = extractHour(task.due_time);
                    if (taskHour !== hour) {
                        if (isDebugHour)
                            console.log(
                                `Task ${taskId} hour doesn't match: ${taskHour} vs ${hour}`,
                            );
                        continue;
                    }

                    // すべての条件を満たしたタスクをコピー
                    const taskCopy = { ...task };

                    // 所有者情報を追加（他人のタスクの場合）
                    if (taskUserId !== columnUserId) {
                        taskCopy.ownerInfo = task.user
                            ? task.user.name
                            : task.ownerInfo || "Shared Task";
                    }

                    taskCopy.isCurrentUserTask = isCurrentUserTask;

                    // 結果に追加
                    matchingTasks.push(taskCopy);
                    if (isDebugHour)
                        console.log(`✓ Added task: ${taskId} - ${task.title}`);
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
                globalShares.value,
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
