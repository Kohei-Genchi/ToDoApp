<template>
    <div
        class="shared-tasks-calendar bg-white rounded-lg shadow-sm overflow-hidden w-full"
    >
        <!-- ローディングインジケーター -->
        <loading-indicator
            v-if="initialLoading"
            message="カレンダーを読み込み中..."
        />

        <!-- ヘッダー部分（日付ナビゲーション） -->
        <header-navigation
            :formatted-date="formattedCurrentDate"
            @previous-day="previousDay"
            @next-day="nextDay"
            @go-to-today="goToToday"
            @open-global-share="openGlobalShareModal"
        />

        <!-- ユーザー共有モーダル -->
        <task-share-modal
            v-if="showGlobalShareModal"
            :task="selectedGlobalTask"
            @close="handleGlobalShareModalClose"
        />

        <!-- カレンダーレイアウト -->
        <div class="flex flex-col w-full">
            <!-- ヘッダー行（ユーザー名） -->
            <calendar-header-row :shared-users="sharedUsers" />

            <!-- ヘッダー行（メールアドレス） -->
            <calendar-email-row :shared-users="sharedUsers" />

            <!-- カレンダー本体 -->
            <calendar-body
                :full-hours="fullHours"
                :shared-users="sharedUsers"
                :current-date="currentDate"
                :shared-tasks="sharedTasks"
                :current-user-id="currentUserId"
                @edit-task="editTask"
                @update-task-status="updateTaskStatus"
                @scroll-to-current-time="scrollToCurrentTime"
                ref="calendarContainer"
            />
        </div>

        <!-- タスク編集モーダル -->
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
import TaskShareModal from "./TaskShareModal.vue";
import TodoApi from "../api/todo";
import TaskShareApi from "../api/taskShare";
import GlobalShareApi from "../api/globalShare";

// インポート各種コンポーネント - 外部ファイルに分離
import HeaderNavigation from "./calendar/HeaderNavigation.vue";
import CalendarHeaderRow from "./calendar/CalendarHeaderRow.vue";
import CalendarEmailRow from "./calendar/CalendarEmailRow.vue";
import CalendarBody from "./calendar/CalendarBody.vue";
import LoadingIndicator from "./common/LoadingIndicator.vue";

// 定数定義
const HOURS_IN_DAY = 24;

export default {
    name: "SharedTasksCalendarView",

    components: {
        TaskModal,
        TaskShareModal,
        HeaderNavigation,
        CalendarHeaderRow,
        CalendarEmailRow,
        CalendarBody,
        LoadingIndicator,
    },

    emits: ["task-updated", "task-created", "task-deleted", "back"],

    setup(props, { emit }) {
        // =============== 状態管理 - グループ化 ===============
        // 日付・ユーザー関連
        const currentDate = ref(formatTodayDate());
        const sharedUsers = ref([]);
        const currentUserId = ref(0); // 未初期化の警告防止のため0として初期化

        // タスク関連
        const sharedTasks = ref([]);
        const categories = ref([]);
        const globalShares = ref([]);

        // UI状態管理
        const isLoading = ref(true);
        const initialLoading = ref(true);
        const calendarContainer = ref(null);

        // モーダル関連
        const showTaskModal = ref(false);
        const taskModalMode = ref("edit");
        const selectedTaskId = ref(null);
        const selectedTaskData = ref(null);
        const showGlobalShareModal = ref(false);
        const selectedGlobalTask = ref(null);

        // カレンダーの時間設定
        const fullHours = Array.from({ length: HOURS_IN_DAY }, (_, i) => i); // 0〜23時

        // =============== 計算プロパティ ===============
        /**
         * フォーマットされた現在日付
         */
        const formattedCurrentDate = computed(() => {
            const date = new Date(currentDate.value);
            return date.toLocaleDateString("ja-JP", {
                year: "numeric",
                month: "long",
                day: "numeric",
                weekday: "long",
            });
        });

        // =============== ユーティリティ関数 ===============
        /**
         * 今日の日付をYYYY-MM-DD形式で取得
         */
        function formatTodayDate() {
            const today = new Date();
            return formatDateToString(today);
        }

        /**
         * 日付をYYYY-MM-DD形式の文字列に変換
         */
        function formatDateToString(date) {
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
        }

        // =============== メソッド - データ読み込み ===============
        /**
         * 共有タスクデータを読み込む
         */
        async function loadSharedTasks() {
            isLoading.value = true;

            try {
                // 1. 現在のユーザー情報を取得
                await initializeCurrentUser();

                // 2. グローバル共有情報を読み込む
                await loadGlobalShares();

                // 3. 共有ユーザーリストを構築
                await buildSharedUsersList();

                // 4. 全てのタスクを読み込む（個別共有、グローバル共有、自分のタスク）
                await loadAllTasksData();
            } catch (error) {
                console.error("タスク読み込みエラー:", error);
                handleError("タスクデータの読み込みに失敗しました。");
            } finally {
                isLoading.value = false;
                initialLoading.value = false;

                // タスク読み込み後に現在時刻にスクロール
                nextTick(() => {
                    scrollToCurrentTime();
                });
            }
        }

        /**
         * 現在のユーザー情報を初期化
         */
        async function initializeCurrentUser() {
            const currentUser = window.Laravel?.user;
            if (currentUser && currentUser.id) {
                currentUserId.value = currentUser.id;
            }
        }

        /**
         * グローバル共有情報を読み込む
         */
        async function loadGlobalShares() {
            try {
                const response = await GlobalShareApi.getGlobalShares();
                globalShares.value = Array.isArray(response.data)
                    ? response.data
                    : [];
            } catch (error) {
                console.error("グローバル共有情報の読み込みエラー:", error);
                globalShares.value = [];
            }
        }

        /**
         * 共有ユーザーリストを構築する
         */
        async function buildSharedUsersList() {
            // ユーザーマップを初期化（重複排除のため）
            const allSharedUsers = new Map();

            // 1. 現在のユーザーを追加
            const currentUser = window.Laravel?.user;
            if (currentUser) {
                allSharedUsers.set(currentUser.id, {
                    id: currentUser.id,
                    name: currentUser.name,
                    email: currentUser.email,
                });
            }

            // 2. 個別共有ユーザーを追加
            await addIndividuallySharedUsers(allSharedUsers);

            // 3. グローバル共有ユーザーを追加
            addGlobalSharedUsers(allSharedUsers);

            // マップから配列に変換して状態を更新
            sharedUsers.value = Array.from(allSharedUsers.values());
        }

        /**
         * 個別共有ユーザーを読み込む
         */
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
                console.error("個別共有ユーザーの読み込みエラー:", error);
            }
        }

        /**
         * グローバル共有ユーザーをマップに追加
         */
        function addGlobalSharedUsers(userMap) {
            if (!globalShares.value || globalShares.value.length === 0) return;

            globalShares.value.forEach((share) => {
                const userId = Number(share.user_id);
                if (!userMap.has(userId)) {
                    // 新規ユーザーとして追加
                    userMap.set(userId, {
                        id: userId,
                        name: share.name || "User " + userId,
                        email: share.email || "",
                        isGlobalShare: true,
                        globalSharePermission: share.permission || "view",
                    });
                } else {
                    // 既存ユーザーの情報を更新
                    const user = userMap.get(userId);
                    user.isGlobalShare = true;
                    user.globalSharePermission = share.permission || "view";
                    userMap.set(userId, user);
                }
            });
        }

        /**
         * 全てのタスクデータを読み込む
         */
        async function loadAllTasksData() {
            const allTasks = await fetchAllTasks();

            // タスクの重複を排除
            const taskMap = new Map();
            allTasks.forEach((task) => {
                if (!taskMap.has(task.id)) {
                    if (!task.shared_with) {
                        task.shared_with = [];
                    }
                    taskMap.set(task.id, task);
                }
            });

            // 最終的なタスクリストを更新
            sharedTasks.value = Array.from(taskMap.values());
        }

        /**
         * すべてのタスクを取得（個別共有、グローバル共有、自分のタスク）
         */
        async function fetchAllTasks() {
            let allTasks = [];

            // 1. 個別共有タスクの読み込み
            try {
                const response = await TaskShareApi.getSharedWithMe();
                if (response && Array.isArray(response.data)) {
                    allTasks = [...response.data];
                }
            } catch (error) {
                console.error("個別共有タスクの読み込みエラー:", error);
            }

            // 2. グローバル共有タスクの読み込み
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
                console.error("グローバル共有タスクの読み込みエラー:", error);
            }

            // 3. グローバル共有ユーザーのタスク読み込み
            const globalUserTasks = await fetchGlobalUserTasks();
            allTasks = [...allTasks, ...globalUserTasks];

            // 4. 自分自身のタスクを読み込む
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
                    console.error("自分のタスク読み込みエラー:", error);
                }
            }

            return allTasks;
        }

        /**
         * グローバル共有ユーザーのタスクを取得
         */
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
                    console.error(
                        `ユーザー ${userId} のタスク読み込みエラー:`,
                        error,
                    );
                }
            }

            return tasks;
        }

        /**
         * カテゴリ一覧を読み込む
         */
        async function loadCategories() {
            try {
                const response = await axios.get("/api/web-categories");
                categories.value = response.data || [];
            } catch (error) {
                console.error("カテゴリー読み込みエラー:", error);
            }
        }

        // =============== メソッド - UI制御 ===============
        /**
         * 特定の時間帯へスクロール（デフォルトは現在時刻）
         */
        function scrollToCurrentTime() {
            const container = document.querySelector(".w-full.overflow-y-auto");
            if (!container) return;

            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();

            try {
                // 現在の時間帯の行を検索
                const hourRows = container.querySelectorAll("[data-hour]");
                let currentRow = null;
                let currentRowOffsetTop = 0;

                for (const row of hourRows) {
                    const hourAttr = row.getAttribute("data-hour");
                    if (hourAttr && parseInt(hourAttr, 10) === currentHour) {
                        currentRow = row;
                        currentRowOffsetTop = row.offsetTop;
                        break;
                    }
                }

                if (currentRow) {
                    // コンテナの高さと行の高さを取得
                    const containerHeight = container.clientHeight;
                    const rowHeight = currentRow.clientHeight;

                    // 分数に基づく位置の調整
                    const minuteOffset = (currentMinute / 60) * rowHeight;

                    // スクロール位置の計算（現在時刻を中央に配置）
                    const scrollPosition =
                        currentRowOffsetTop +
                        minuteOffset -
                        containerHeight / 2 +
                        rowHeight / 2;

                    // スムーズスクロール
                    container.scrollTo({
                        top: Math.max(0, scrollPosition),
                        behavior: "smooth",
                    });
                }
            } catch (error) {
                console.error("スクロール処理エラー:", error);
            }
        }

        /**
         * グローバル共有モーダルを開く
         */
        function openGlobalShareModal() {
            selectedGlobalTask.value = {
                id: "global-share",
                title: "全てのタスク",
                isGlobalShare: true,
            };
            showGlobalShareModal.value = true;
        }

        /**
         * グローバル共有モーダルのクローズ処理
         */
        function handleGlobalShareModalClose(data) {
            showGlobalShareModal.value = false;

            // モーダルから共有ユーザー情報を受け取った場合は更新
            if (data?.sharedUsers && Array.isArray(data.sharedUsers)) {
                updateSharedUsersList(data.sharedUsers);
            }

            // 共有タスクとグローバル共有の再読み込み
            loadSharedTasks();
            loadGlobalShares();
        }

        /**
         * 共有ユーザーリストを更新
         */
        function updateSharedUsersList(newUsers) {
            // 既存ユーザーIDのセット
            const existingUserIds = new Set(
                sharedUsers.value.map((user) => user.id),
            );

            // 新規ユーザーの追加
            newUsers.forEach((user) => {
                if (!existingUserIds.has(user.id)) {
                    sharedUsers.value.push(user);
                }
            });

            // ローカルストレージへの保存（後方互換性のため）
            if (currentUserId.value) {
                try {
                    localStorage.setItem(
                        `sharedUsers_${currentUserId.value}`,
                        JSON.stringify(newUsers),
                    );
                } catch (e) {
                    console.error("共有ユーザー情報の保存エラー:", e);
                }
            }
        }

        // =============== メソッド - タスク操作 ===============
        /**
         * タスクの編集モーダルを開く
         */
        function editTask(task) {
            taskModalMode.value = "edit";
            selectedTaskId.value = task.id;

            // 共有ビューからの編集時のフラグ
            selectedTaskData.value = {
                ...task,
                _isSharedViewEdit: true,
            };

            showTaskModal.value = true;
        }

        /**
         * タスクモーダルを閉じる
         */
        function closeTaskModal() {
            showTaskModal.value = false;
            selectedTaskData.value = null; // 参照の解放
        }

        /**
         * タスクの保存（作成または更新）
         */
        async function submitTask(taskData) {
            try {
                // 日付がない場合は現在の日付をデフォルトに
                if (!taskData.due_date) {
                    taskData.due_date = formatTodayDate();
                }

                let response;
                if (taskModalMode.value === "add") {
                    // 新規タスク作成
                    response = await TodoApi.createTask(taskData);
                    emit("task-created", response.data?.todo);
                } else {
                    // タスク更新
                    response = await TodoApi.updateTask(
                        selectedTaskId.value,
                        taskData,
                    );
                    emit("task-updated", response.data?.todo);
                }

                closeTaskModal();

                // タスク一覧を再読み込み
                await loadSharedTasks();
            } catch (error) {
                console.error("タスク保存エラー:", error);
                handleError("タスクの保存に失敗しました");
            }
        }

        /**
         * タスクの削除
         */
        async function handleTaskDelete(taskId) {
            try {
                await TodoApi.deleteTask(taskId);
                sharedTasks.value = sharedTasks.value.filter(
                    (t) => t.id !== taskId,
                );
                emit("task-deleted", taskId);
                closeTaskModal();
            } catch (error) {
                console.error("タスク削除エラー:", error);
                handleError("タスクの削除に失敗しました");
            }
        }

        /**
         * タスクのステータスを更新
         */
        async function updateTaskStatus(taskId, newStatus) {
            try {
                // タスク一覧から対象タスクを検索
                const taskIndex = sharedTasks.value.findIndex(
                    (t) => t.id === taskId,
                );
                if (taskIndex === -1) return;

                const task = sharedTasks.value[taskIndex];
                const originalStatus = task.status;

                // 楽観的更新 - UIを即時更新
                sharedTasks.value[taskIndex] = { ...task, status: newStatus };

                // APIリクエスト
                try {
                    await TodoApi.updateTask(taskId, { status: newStatus });
                    // バックグラウンドでタスク一覧を更新
                    loadSharedTasks().catch((e) =>
                        console.error("バックグラウンド更新エラー:", e),
                    );
                } catch (error) {
                    console.error("タスクステータス更新エラー:", error);

                    // APIエラー時にUIを元に戻す
                    sharedTasks.value[taskIndex] = {
                        ...task,
                        status: originalStatus,
                    };

                    // エラーメッセージを表示
                    handleStatusUpdateError(error);
                }
            } catch (error) {
                console.error("タスクステータス更新処理エラー:", error);
                handleError("タスクのステータス更新中にエラーが発生しました。");
            }
        }

        /**
         * タスクステータス更新エラーのハンドリング
         */
        function handleStatusUpdateError(error) {
            if (error.response && error.response.status === 403) {
                handleError(
                    "権限がありません：このタスクを更新する権限がありません。",
                );
            } else {
                handleError(
                    "タスクのステータス更新に失敗しました: " +
                        (error.response?.data?.error || error.message),
                );
            }
        }

        /**
         * エラーメッセージの表示
         */
        function handleError(message) {
            // 実装に応じてエラー表示方法を変更
            alert(message);
        }

        // =============== メソッド - 日付操作 ===============
        /**
         * 前日に移動
         */
        function previousDay() {
            const date = new Date(currentDate.value);
            date.setDate(date.getDate() - 1);
            currentDate.value = formatDateToString(date);
        }

        /**
         * 翌日に移動
         */
        function nextDay() {
            const date = new Date(currentDate.value);
            date.setDate(date.getDate() + 1);
            currentDate.value = formatDateToString(date);
        }

        /**
         * 今日に移動
         */
        function goToToday() {
            currentDate.value = formatTodayDate();
        }

        // =============== ライフサイクルフック ===============
        // 日付変更の監視
        watch(
            () => currentDate.value,
            () => {
                loadSharedTasks();
            },
        );

        // 初期化処理
        onMounted(() => {
            // 現在のユーザーIDを取得
            if (window.Laravel?.user) {
                currentUserId.value = window.Laravel.user.id;
            }

            // 今日の日付を設定
            goToToday();

            // 最低限のデータを先に表示（ユーザーエクスペリエンス向上）
            sharedUsers.value = [
                {
                    id: currentUserId.value || 0,
                    name: window.Laravel?.user?.name || "現在のユーザー",
                    email: window.Laravel?.user?.email || "",
                },
            ];

            // 非同期でデータ読み込み
            Promise.all([
                loadGlobalShares().catch((e) =>
                    console.error("グローバル共有読み込みエラー:", e),
                ),
                loadCategories().catch((e) =>
                    console.error("カテゴリー読み込みエラー:", e),
                ),
            ]).then(() => {
                // タスクデータを読み込み
                setTimeout(() => {
                    loadSharedTasks().finally(() => {
                        initialLoading.value = false;

                        // スクロール位置の調整（複数回試行してより確実に）
                        scrollToCurrentTime();
                        setTimeout(() => scrollToCurrentTime(), 300);
                        setTimeout(() => scrollToCurrentTime(), 1000);
                    });
                }, 100);
            });
        });

        return {
            // 状態
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
            showGlobalShareModal,
            selectedGlobalTask,
            currentUserId,

            // メソッド - 明示的に公開する必要のあるもののみ
            openGlobalShareModal,
            editTask,
            closeTaskModal,
            submitTask,
            handleTaskDelete,
            handleGlobalShareModalClose,
            updateTaskStatus,
            scrollToCurrentTime,
            previousDay,
            nextDay,
            goToToday,
        };
    },
};
</script>

<style scoped>
/* 現在時刻インジケーターのアニメーション */
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

/* 現在時間行のハイライトアニメーション */
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
