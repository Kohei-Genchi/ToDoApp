<template>
    <div class="bg-gray-100 min-h-screen main-content">
        <!-- ヘッダー（「+ 新しいタスク」ボタンを含む） -->
        <app-header
            :current-view="currentView"
            @set-view="setView"
            @show-calendar="showCalendarView"
            @add-task="openAddTaskModal"
        />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
            <!-- 週間日付ナビゲーション - 新しいコンポーネント -->
            <weekly-date-navigation
                v-if="currentView !== 'calendar'"
                :current-date="currentDate"
                @date-selected="selectDate"
            />

            <!-- カレンダー月ナビゲーション -->
            <month-navigation
                v-if="currentView === 'calendar'"
                :formatted-month="formattedMonth"
                @previous-month="previousMonth"
                @next-month="nextMonth"
            />

            <!-- カレンダー表示 -->
            <todo-calendar
                v-if="currentView === 'calendar'"
                :current-date="currentDate"
                :todos="todos"
                @date-selected="selectDate"
                @edit-task="openEditTaskModal"
            />

            <!-- タスク一覧（通常ビュー） -->
            <todo-list
                v-if="currentView !== 'calendar'"
                :todos="filteredTodos"
                :categories="categories"
                @toggle-task="toggleTaskStatus"
                @edit-task="openEditTaskModal"
                @delete-task="confirmDeleteTask"
            />
        </main>

        <!-- タスク追加/編集モーダル -->
        <task-modal
            v-if="showTaskModal"
            :mode="taskModalMode"
            :todo-id="selectedTaskId"
            :todo-data="selectedTaskData"
            :categories="categories"
            :current-date="currentDate"
            :current-view="currentView"
            @close="closeTaskModal"
            @submit="submitTask"
            @delete="handleTaskDelete"
            @category-created="loadCategories"
        />

        <!-- 削除確認モーダル -->
        <delete-confirm-modal
            v-if="showDeleteConfirmModal"
            :todo-title="selectedTaskData?.title || ''"
            :is-recurring="isRecurringTask(selectedTaskData)"
            @confirm="confirmDelete"
            @cancel="showDeleteConfirmModal = false"
        />

        <!-- 通知コンポーネント -->
        <notification-component ref="notification" />
    </div>
</template>

<script>
import { ref, computed, onMounted, defineAsyncComponent } from "vue";
import TodoApi from "../api/todo";
import CategoryApi from "../api/category";

// コンポーネントのインポート
const TodoList = defineAsyncComponent(() => import("./TodoList.vue"));
const TodoCalendar = defineAsyncComponent(() => import("./TodoCalendar.vue"));
const TaskModal = defineAsyncComponent(() => import("./TaskModal.vue"));
const DeleteConfirmModal = defineAsyncComponent(
    () => import("./DeleteConfirmModal.vue"),
);
const NotificationComponent = defineAsyncComponent(
    () => import("./UI/NotificationComponent.vue"),
);

// コンポーネントのインポート
import AppHeader from "./AppHeader.vue";
import MonthNavigation from "./MonthNavigation.vue";
import WeeklyDateNavigation from "./WeeklyDateNavigation.vue";

export default {
    name: "TodoApp",

    components: {
        TodoList,
        TodoCalendar,
        TaskModal,
        DeleteConfirmModal,
        NotificationComponent,
        AppHeader,
        MonthNavigation,
        WeeklyDateNavigation, // 新しいナビゲーションコンポーネント
    },

    setup() {
        // ===============================
        // ステート
        // ===============================
        const todos = ref([]);
        const categories = ref([]);
        const currentView = ref("today");
        const currentDate = ref(new Date().toISOString().split("T")[0]);

        // モーダルステート
        const showTaskModal = ref(false);
        const taskModalMode = ref("add");
        const selectedTaskId = ref(null);
        const selectedTaskData = ref(null);
        const showDeleteConfirmModal = ref(false);
        const deleteAllRecurring = ref(false);

        // 通知参照
        const notification = ref(null);

        // ===============================
        // ユーティリティ関数
        // ===============================

        /**
         * 日付を比較用にフォーマット
         * @param {string|Date} dateString - 日付文字列またはDateオブジェクト
         * @returns {string} YYYY-MM-DD形式の日付文字列
         */
        function formatDateForComparison(dateString) {
            if (!dateString) return "";

            try {
                // 異なる日付形式を処理
                let date;
                if (typeof dateString === "string") {
                    // すでにYYYY-MM-DD形式の場合はそのまま返す
                    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
                        return dateString;
                    }
                    date = new Date(dateString);
                } else if (dateString instanceof Date) {
                    date = dateString;
                } else {
                    return "";
                }

                // 有効な日付かどうか確認
                if (isNaN(date.getTime())) {
                    return "";
                }

                // ローカルタイムゾーンでYYYY-MM-DD形式にフォーマット
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
            } catch (e) {
                return "";
            }
        }

        /**
         * API用に日付をフォーマット
         * @param {string} dateStr 日付文字列
         * @returns {string|null} YYYY-MM-DD形式の日付文字列またはnull
         */
        function formatDateForAPI(dateStr) {
            if (!dateStr) return null;

            try {
                const date = new Date(dateStr);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, "0");
                const day = String(date.getDate()).padStart(2, "0");

                return `${year}-${month}-${day}`;
            } catch (e) {
                return null;
            }
        }

        /**
         * タスクが繰り返しタスクかどうかを判定
         * @param {Object} task タスクオブジェクト
         * @returns {boolean} 繰り返しタスクかどうか
         */
        function isRecurringTask(task) {
            return task?.recurrence_type && task.recurrence_type !== "none";
        }

        /**
         * エラーを処理し、通知を表示
         * @param {Error} error エラーオブジェクト
         * @param {string} defaultMessage デフォルトのエラーメッセージ
         */
        function handleError(error, defaultMessage) {
            const errorMessage = error?.response?.data?.error || defaultMessage;
            notification.value?.show(errorMessage, "error");
        }

        // ===============================
        // 計算プロパティ
        // ===============================

        /**
         * 表示用に日付をフォーマット
         */
        const formattedDate = computed(() => {
            const date = new Date(currentDate.value);
            const today = new Date();

            if (date.toDateString() === today.toDateString()) {
                return "今日";
            }

            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            if (date.toDateString() === tomorrow.toDateString()) {
                return "明日";
            }

            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            if (date.toDateString() === yesterday.toDateString()) {
                return "昨日";
            }

            // 日本語形式
            const options = {
                year: "numeric",
                month: "long",
                day: "numeric",
                weekday: "long",
            };
            return date.toLocaleDateString("ja-JP", options);
        });

        /**
         * 表示用に月をフォーマット
         */
        const formattedMonth = computed(() => {
            const date = new Date(currentDate.value);
            const options = { year: "numeric", month: "long" };
            return date.toLocaleDateString("ja-JP", options);
        });

        /**
         * 現在の日付に基づいてタスクをフィルタリング
         */
        const filteredTodos = computed(() => {
            const formattedCurrentDate = formatDateForComparison(
                currentDate.value,
            );

            return todos.value.filter((todo) => {
                const formattedTodoDate = formatDateForComparison(
                    todo.due_date,
                );
                return (
                    formattedTodoDate === formattedCurrentDate &&
                    todo.status !== "trashed"
                );
            });
        });

        // ===============================
        // データ読み込み関数
        // ===============================

        /**
         * タスク一覧を読み込む
         */
        async function loadTasks() {
            try {
                const response = await TodoApi.getTasks(
                    currentView.value,
                    currentDate.value,
                );
                // console.log(response.data[0]);
                //オブジェクトやnull などが返ってきた場合に備えて Array.isArrayで確認。
                if (!Array.isArray(response.data)) {
                    todos.value = [];
                    return;
                }

                // レスポンスを処理して一貫性を確保
                todos.value = response.data.map((todo) => {
                    // 参照の問題を避けるためディープコピーを作成
                    const processedTodo = { ...todo };
                    // IDが数値であることを確認
                    if (processedTodo.id !== undefined) {
                        processedTodo.id = Number(processedTodo.id);
                    }

                    // category_idが数値であることを確認
                    if (
                        processedTodo.category_id !== null &&
                        processedTodo.category_id !== undefined
                    ) {
                        processedTodo.category_id = Number(
                            processedTodo.category_id,
                        );
                    }

                    // 比較しやすいようにフォーマットされた日付を追加
                    if (processedTodo.due_date) {
                        processedTodo.formatted_due_date =
                            formatDateForComparison(processedTodo.due_date);
                    }

                    return processedTodo;
                });
            } catch (error) {
                handleError(error, "タスクの読み込みに失敗しました");
            }
            console.log("更新後のタスクリスト:", todos.value);
        }

        async function loadCategories() {
            try {
                const response = await CategoryApi.getCategories();

                if (!response || !response.data) {
                    categories.value = [];
                    return;
                }

                // カテゴリが適切にフォーマットされていることを確認
                categories.value = Array.isArray(response.data)
                    ? response.data.map((cat) => ({
                          id: Number(cat.id),
                          name: cat.name || "Unnamed Category",
                          color: cat.color || "#cccccc",
                          user_id: cat.user_id,
                      }))
                    : [];
            } catch (error) {
                handleError(error, "カテゴリーの読み込みに失敗しました");
            }
        }

        // ===============================
        // ビュー操作関数
        // ===============================

        /**
         * ビューを設定
         * @param {string} view ビュータイプ
         */
        function setView(view) {
            currentView.value = view;
            if (view === "today") {
                currentDate.value = new Date().toISOString().split("T")[0];
            }
            loadTasks();
        }

        /**
         * カレンダービューを表示
         */
        function showCalendarView() {
            currentView.value = "calendar";
            loadTasks();
        }

        // ===============================
        // 日付操作関数
        // ===============================

        /**
         * 特定の日付を選択
         * @param {string} date 日付文字列
         */
        function selectDate(date) {
            currentDate.value = date;
            currentView.value = "date";
            loadTasks();
        }

        /**
         * 前月に移動
         */
        function previousMonth() {
            const date = new Date(currentDate.value);
            date.setMonth(date.getMonth() - 1);
            currentDate.value = date.toISOString().split("T")[0];
            loadTasks();
        }

        /**
         * 翌月に移動
         */
        function nextMonth() {
            const date = new Date(currentDate.value);
            date.setMonth(date.getMonth() + 1);
            currentDate.value = date.toISOString().split("T")[0];
            loadTasks();
        }

        // ===============================
        // タスクモーダル関連関数
        // ===============================

        /**
         * タスク追加モーダルを開く
         */
        function openAddTaskModal() {
            taskModalMode.value = "add";
            selectedTaskId.value = null;
            selectedTaskData.value = {
                title: "",
                description: "",
                due_date: currentDate.value,
                due_time: "",
                category_id: "",
                recurrence_type: "none",
                recurrence_end_date: "",
            };

            showTaskModal.value = true;
        }

        /**
         * タスク編集モーダルを開く
         * @param {Object|number|string} task タスクオブジェクトまたはID
         */
        async function openEditTaskModal(task) {
            try {
                // 直接ID入力の場合（数値または文字列として）
                if (
                    (typeof task === "number" || typeof task === "string") &&
                    !isNaN(Number(task))
                ) {
                    await fetchAndEditTask(Number(task));
                    return;
                }

                // タスクが空配列または未定義の場合
                if (!task || (Array.isArray(task) && task.length === 0)) {
                    notification.value?.show(
                        "編集するタスクが見つかりません",
                        "error",
                    );
                    return;
                }

                // タスクがIDプロパティを持つオブジェクトの場合
                if (typeof task === "object" && task !== null) {
                    // モーダルを開く前にカテゴリを強制的に再読み込み
                    await loadCategories();

                    taskModalMode.value = "edit";

                    // タスクIDが適切に設定されていることを確認
                    if (task.id === undefined || task.id === null) {
                        notification.value?.show(
                            "タスクIDが見つかりません",
                            "error",
                        );
                        return;
                    }

                    selectedTaskId.value = Number(task.id);

                    // 参照の問題を避けるためタスクのディープコピーを作成
                    selectedTaskData.value = JSON.parse(JSON.stringify(task));

                    showTaskModal.value = true;
                    console.log("編集モーダルを開きます:", task);
                }
            } catch (error) {
                handleError(error, "タスク編集の準備中にエラーが発生しました");
            }
        }

        /**
         * IDでタスクデータを取得し、編集モーダルを開く
         * @param {number} taskId タスクID
         */
        async function fetchAndEditTask(taskId) {
            try {
                // まず、すでに読み込まれているタスクを確認
                const task = todos.value.find((t) => t.id === taskId);

                if (task) {
                    // カテゴリが読み込まれていることを確認
                    await loadCategories();

                    taskModalMode.value = "edit";
                    selectedTaskId.value = Number(taskId);
                    selectedTaskData.value = JSON.parse(JSON.stringify(task));
                    showTaskModal.value = true;
                    return;
                }

                // APIからタスクデータを取得
                const response = await TodoApi.getTaskById(taskId);

                // カテゴリが読み込まれていることを確認
                await loadCategories();

                taskModalMode.value = "edit";
                selectedTaskId.value = Number(taskId);
                selectedTaskData.value = response.data;
                showTaskModal.value = true;
            } catch (error) {
                handleError(error, "タスクデータの取得に失敗しました");
            }
        }

        /**
         * タスクモーダルを閉じる
         */
        function closeTaskModal() {
            showTaskModal.value = false;
        }

        // ===============================
        // タスク操作関数
        // ===============================

        /**
         * タスクを送信（作成または更新）
         * @param {Object} taskData タスクデータ
         */
        async function submitTask(taskData) {
            try {
                // 元のデータを変更しないようにクローン
                const preparedData = { ...taskData };

                // 日付を正しくAPIにフォーマット
                if (preparedData.due_date) {
                    preparedData.due_date = formatDateForAPI(
                        preparedData.due_date,
                    );
                }

                if (preparedData.recurrence_end_date) {
                    preparedData.recurrence_end_date = formatDateForAPI(
                        preparedData.recurrence_end_date,
                    );
                }

                let response;

                if (taskModalMode.value === "add") {
                    // 新しいタスクの追加
                    response = await TodoApi.createTask(preparedData);
                } else {
                    // タスクIDが利用可能であることを確認
                    const taskId =
                        selectedTaskId.value ||
                        (preparedData.id ? Number(preparedData.id) : null);

                    if (!taskId && taskId !== 0) {
                        notification.value.show(
                            "タスクIDが見つかりません",
                            "error",
                        );
                        return;
                    }

                    // 以前に日付のないタスクに日付を追加しているかどうかを確認
                    const isAddingDueDateToMemo =
                        preparedData.due_date &&
                        (!selectedTaskData.value.due_date ||
                            selectedTaskData.value.due_date === null);

                    console.log("APIに送信するデータ:", preparedData);
                    // 既存のタスクを更新
                    response = await TodoApi.updateTask(taskId, preparedData);

                    // メモリストからタスクに日付を追加している場合、メモリストをすぐに更新
                    if (isAddingDueDateToMemo) {
                        await refreshMemoList();
                    }
                }
                console.log("APIレスポンス:", response.data);
                closeTaskModal();

                // モーダルを閉じた後、現在のビューに基づいて関連するタスクデータを再読み込み
                await loadTasks();
            } catch (error) {
                handleError(error, "タスクの保存に失敗しました");
            }
        }

        async function refreshMemoList() {
            try {
                const response = await axios.get("/api/memos-partial", {
                    headers: {
                        Accept: "text/html",
                        "Cache-Control": "no-cache, no-store, must-revalidate",
                    },
                    // キャッシュを無効化
                    params: { _t: new Date().getTime() },
                });

                // レスポンスデータは自動的に処理される
                const html = response.data;

                // メモリストコンテナを検索
                const memoContainers = document.querySelectorAll(
                    ".memo-list-container",
                );

                if (memoContainers.length > 0) {
                    // すべてのメモコンテナを新しいHTMLで更新
                    memoContainers.forEach((container) => {
                        container.innerHTML = html;
                    });

                    // イベントリスナーを再アタッチ
                    attachMemoListEvents();
                    return true;
                } else {
                    return false;
                }
            } catch (error) {
                console.error("メモリスト更新エラー:", error);
                return false;
            }
        }

        /**
         * メモリストのイベントを再アタッチ
         */
        function attachMemoListEvents() {
            // メモリスト内のすべてのゴミ箱ボタンを検索し、クリックハンドラをアタッチ
            const trashButtons = document.querySelectorAll(
                '.memo-list-container button[onclick*="trashMemo"]',
            );
            // 必要に応じて他のイベントリスナーを再アタッチ
        }

        /**
         * タスクの完了状態を切り替え
         * @param {Object} task タスクオブジェクト
         */
        async function toggleTaskStatus(task) {
            try {
                await TodoApi.toggleTask(task.id);

                // 楽観的更新
                const taskIndex = todos.value.findIndex(
                    (t) => t.id === task.id,
                );
                if (taskIndex !== -1) {
                    todos.value[taskIndex].status =
                        todos.value[taskIndex].status === "completed"
                            ? "pending"
                            : "completed";
                }

                loadTasks();
            } catch (error) {
                handleError(error, "タスクのステータス変更に失敗しました");
            }
        }

        /**
         * タスク削除ハンドラ
         * @param {number} id タスクID
         * @param {boolean} deleteAllRecurringFlag すべての繰り返しタスクを削除するかどうか
         */
        function handleTaskDelete(id, deleteAllRecurringFlag) {
            // まずタスクモーダルを閉じる
            closeTaskModal();

            // 削除確認モーダルを表示
            selectedTaskId.value = id;
            deleteAllRecurring.value = deleteAllRecurringFlag;

            // 確認に表示するタスクを検索
            const task = todos.value.find((t) => t.id === id);

            if (task) {
                selectedTaskData.value = task;
            }

            // 確認モーダルを表示 - ユーザーが確認するまで削除しない
            showDeleteConfirmModal.value = true;
        }

        /**
         * タスク削除確認
         * @param {Object} task タスクオブジェクト
         */
        function confirmDeleteTask(task) {
            selectedTaskId.value = task.id;
            selectedTaskData.value = task;
            showDeleteConfirmModal.value = true;
        }

        /**
         * 削除を確認
         * @param {boolean} confirmed 確認されたかどうか
         */
        async function confirmDelete(confirmed = true) {
            // ユーザーが確認した場合のみ続行
            if (!confirmed) {
                showDeleteConfirmModal.value = false;
                return;
            }

            try {
                await TodoApi.deleteTask(
                    selectedTaskId.value,
                    deleteAllRecurring.value,
                );
                notification.value?.show("タスクを削除しました");
                showDeleteConfirmModal.value = false;

                // タスクリストを更新
                await loadTasks();

                // フィルタリングされたタスクの強制更新
                todos.value = [...todos.value]; // リアクティビティをトリガー
            } catch (error) {
                handleError(error, "タスクの削除に失敗しました");
            }
        }

        // 初期化
        onMounted(() => {
            loadTasks();
            loadCategories();

            // レガシーコードからのedit-todoイベントをリッスン
            document
                .getElementById("todo-app")
                ?.addEventListener("edit-todo", async (event) => {
                    try {
                        const { id, data } = event.detail;

                        if (id !== undefined && id !== null) {
                            await fetchAndEditTask(Number(id));
                        } else if (data) {
                            openEditTaskModal(data);
                        } else {
                            notification.value?.show(
                                "タスク編集データが無効です",
                                "error",
                            );
                        }
                    } catch (error) {
                        handleError(
                            error,
                            "タスク編集の処理中にエラーが発生しました",
                        );
                    }
                });
        });

        return {
            // ステート
            todos,
            categories,
            currentView,
            currentDate,
            formattedDate,
            formattedMonth,
            filteredTodos,
            showTaskModal,
            taskModalMode,
            selectedTaskId,
            selectedTaskData,
            showDeleteConfirmModal,
            notification,

            // ビュー操作
            setView,
            showCalendarView,

            // 日付操作
            selectDate,
            previousMonth,
            nextMonth,

            // タスクモーダル
            openAddTaskModal,
            openEditTaskModal,
            fetchAndEditTask,
            closeTaskModal,

            // タスク操作
            submitTask,
            toggleTaskStatus,
            handleTaskDelete,
            confirmDeleteTask,
            confirmDelete,

            // その他
            loadCategories,
            isRecurringTask,
        };
    },
};
</script>
