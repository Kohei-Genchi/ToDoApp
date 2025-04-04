<template>
    <div class="bg-gray-100 min-h-screen main-content">
        <!-- Header ("+ New Task" button included) -->
        <app-header
            :current-view="currentView"
            @set-view="setView"
            @add-task="openAddTaskModal"
            @open-share-modal="openShareByLocationModal"
        />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
            <!-- Weekly Date Navigation - 共有ビュー以外で表示 -->
            <weekly-date-navigation
                v-if="!isSharedView"
                :current-date="currentDate"
                @date-selected="selectDate"
                @previous-day="previousDay"
                @next-day="nextDay"
            />

            <!-- Task List (通常ビュー) -->
            <todo-list
                v-if="!isSharedView"
                :todos="filteredTodos"
                :categories="categories"
                :current-user-id="currentUserId"
                @toggle-task="toggleTaskStatus"
                @edit-task="openEditTaskModal"
                @delete-task="confirmDeleteTask"
            />

            <kanban-board
                v-if="isSharedView && currentView !== 'categories-shared'"
                :categories="categories"
                :current-user-id="currentUserId"
                @edit-task="openEditTaskModal"
                @task-status-changed="handleTaskStatusChange"
            />

            <!-- Shared Categories View (共有カテゴリービュー) -->
            <shared-categories-view
                v-if="currentView === 'categories-shared'"
                :current-user-id="currentUserId"
            />
        </main>

        <!-- Task Add/Edit Modal -->
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

        <!-- Delete Confirmation Modal -->
        <delete-confirm-modal
            v-if="showDeleteConfirmModal"
            :todo-title="selectedTaskData?.title || ''"
            :is-recurring="isRecurringTask(selectedTaskData)"
            @confirm="confirmDelete"
            @cancel="showDeleteConfirmModal = false"
        />

        <!-- Inline Notification Component -->
        <div class="fixed top-0 right-0 p-4 z-50">
            <div
                v-if="notificationMessage"
                :class="[
                    'p-4 rounded-md shadow-md transition-all duration-300 transform',
                    notificationVisible
                        ? 'translate-y-0 opacity-100'
                        : '-translate-y-4 opacity-0',
                    notificationType === 'error'
                        ? 'bg-red-500 text-white'
                        : 'bg-green-500 text-white',
                ]"
            >
                {{ notificationMessage }}
            </div>
        </div>
        <!-- Location Sharing Modal -->
        <share-by-location-modal
            v-if="showShareByLocationModal"
            @close="showShareByLocationModal = false"
            @shared="handleLocationShared"
        />
    </div>
</template>

<script>
import { ref, computed, onMounted, defineAsyncComponent, watch } from "vue";
import TodoApi from "../api/todo";
import CategoryApi from "../api/category";

// コンポーネントインポート
const TodoList = defineAsyncComponent(() => import("./TodoList.vue"));
const TaskModal = defineAsyncComponent(() => import("./TaskModal.vue"));
const DeleteConfirmModal = defineAsyncComponent(
    () => import("./DeleteConfirmModal.vue"),
);
// 新しく追加するKanbanBoardコンポーネント
const KanbanBoard = defineAsyncComponent(() => import("./KanbanBoard.vue"));

// 他のコンポーネント
import AppHeader from "./AppHeader.vue";
import WeeklyDateNavigation from "./WeeklyDateNavigation.vue";
import SharedCategoriesView from "./SharedCategoriesView.vue";
import ShareByLocationModal from "./ShareByLocationModal.vue";

export default {
    name: "TodoApp",

    components: {
        TodoList,
        TaskModal,
        DeleteConfirmModal,
        AppHeader,
        WeeklyDateNavigation,
        KanbanBoard,
        SharedCategoriesView,
        ShareByLocationModal,
    },

    setup() {
        // 状態変数
        const todos = ref([]);
        const categories = ref([]);
        const currentView = ref("today");
        const currentUserId = ref(null);

        // 現在日付
        const today = new Date();
        const currentDate = ref(
            `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`,
        );

        // モーダル状態
        const showTaskModal = ref(false);
        const taskModalMode = ref("add");
        const selectedTaskId = ref(null);
        const selectedTaskData = ref(null);
        const showDeleteConfirmModal = ref(false);
        const deleteAllRecurring = ref(false);

        // 通知状態
        const notificationMessage = ref("");
        const notificationType = ref("success");
        const notificationVisible = ref(false);
        let notificationTimeout = null;

        // 計算プロパティ

        // 共有ビューかどうかを判定
        const isSharedView = computed(() => {
            return ["shared", "kanban", "categories-shared"].includes(
                currentView.value,
            );
        });

        const showShareByLocationModal = ref(false);

        // Add this to the setup function methods:
        const openShareByLocationModal = () => {
            showShareByLocationModal.value = true;
        };

        const handleLocationShared = (shareInfo) => {
            showNotification(
                `${shareInfo.taskCount}件のタスクを${shareInfo.email}に共有しました`,
                "success",
            );
            loadTasks();
        };

        const getLocationName = (location) => {
            switch (location) {
                case "INBOX":
                    return "未分類";
                case "TODAY":
                    return "今日";
                case "SCHEDULED":
                    return "予定";
                default:
                    return location;
            }
        };

        function showNotification(message, type = "success", duration = 3000) {
            // Clear any existing timeout
            if (notificationTimeout) {
                clearTimeout(notificationTimeout);
            }

            // Set notification properties
            notificationMessage.value = message;
            notificationType.value = type;
            notificationVisible.value = true;

            // Auto-hide the notification
            notificationTimeout = setTimeout(() => {
                notificationVisible.value = false;
            }, duration);
        }

        // ===============================
        // Utility Functions
        // ===============================

        /**
         * Format date for comparison
         * @param {string|Date} dateString - Date string or Date object
         * @returns {string} Date string in YYYY-MM-DD format
         */
        function formatDateForComparison(dateString) {
            if (!dateString) return "";

            try {
                // Handle different date formats
                let date;
                if (typeof dateString === "string") {
                    // If already in YYYY-MM-DD format, return as is
                    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
                        return dateString;
                    }
                    date = new Date(dateString);
                } else if (dateString instanceof Date) {
                    date = dateString;
                } else {
                    return "";
                }

                // Check if date is valid
                if (isNaN(date.getTime())) {
                    return "";
                }

                // Format date as YYYY-MM-DD in local timezone
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
            } catch (e) {
                return "";
            }
        }

        /**
         * Format date for API
         * @param {string} dateStr Date string
         * @returns {string|null} Date string in YYYY-MM-DD format or null
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
         * Check if task is recurring
         * @param {Object} task Task object
         * @returns {boolean} Whether task is recurring
         */
        function isRecurringTask(task) {
            if (!task) return false;

            return (
                task.recurrence_type &&
                task.recurrence_type !== "none" &&
                task.recurrence_type !== null
            );
        }

        /**
         * Handle error and show notification
         * @param {Error} error Error object
         * @param {string} defaultMessage Default error message
         */
        function handleError(error, defaultMessage) {
            const errorMessage = error?.response?.data?.error || defaultMessage;
            showNotification(errorMessage, "error");
        }

        // ===============================
        // Computed Properties
        // ===============================

        /**
         * Format date for display
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

            // Japanese format
            const options = {
                year: "numeric",
                month: "long",
                day: "numeric",
                weekday: "long",
            };
            return date.toLocaleDateString("ja-JP", options);
        });

        /**
         * Filter todos based on current date
         */
        const filteredTodos = computed(() => {
            if (isSharedView.value) {
                return todos.value;
            }

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
        // Data Loading Functions
        // ===============================

        /**
         * Load tasks
         */
        async function loadTasks() {
            try {
                let response;

                if (isSharedView.value) {
                    // 共有ビューの場合は共有タスクをロード
                    response = await TodoApi.getTasks("shared");
                } else {
                    // 通常ビューの場合は日付などでフィルタリング
                    response = await TodoApi.getTasks(
                        currentView.value,
                        currentDate.value,
                    );
                }

                todos.value = response.data;
            } catch (error) {
                // エラーハンドリング
                handleError(error, "タスクの読み込みに失敗しました");
            }
        }

        async function loadCategories() {
            try {
                const response = await CategoryApi.getCategories();

                if (!response || !response.data) {
                    categories.value = [];
                    return;
                }

                // Ensure categories are properly formatted
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

        // TodoApp.vue の handleTaskStatusChange 関数修正

        // タスクステータス変更ハンドラ（KanbanBoardからの通知用）
        async function handleTaskStatusChange(taskId, newStatus) {
            try {
                console.log(
                    `Handling task status change: Task ${taskId} -> ${newStatus}`,
                );

                // 専用の更新ステータスメソッドを使用
                await TodoApi.updateTaskStatus(taskId, newStatus);

                // 成功メッセージ表示
                showNotification("タスクステータスを更新しました", "success");

                // 状態の最適化更新 - 既存のtodos配列内のタスクを直接更新
                const taskIndex = todos.value.findIndex((t) => t.id === taskId);
                if (taskIndex !== -1) {
                    console.log(
                        `Optimistically updating task ${taskId} status to ${newStatus}`,
                    );
                    todos.value[taskIndex].status = newStatus;

                    // 配列を新しいものとして設定して反応性をトリガー
                    todos.value = [...todos.value];
                } else {
                    // タスクが見つからない場合は全タスクを再読み込み
                    await loadTasks();
                }
            } catch (error) {
                console.error("Error updating task status:", error);
                handleError(error, "タスクステータスの更新に失敗しました");

                // エラー時にはタスクリストを完全に再読み込み
                await loadTasks();
            }
        }

        // ===============================
        // View Functions
        // ===============================

        /**
         * Set view
         * @param {string} view View type
         */
        function setView(view) {
            currentView.value = view;
            if (view === "today") {
                goToToday();
            }
            updateUrl(view);

            loadTasks();
        }

        function updateUrl(view) {
            const url = new URL(window.location);
            url.searchParams.set("view", view);
            window.history.replaceState({}, "", url);
        }

        // ===============================
        // Date Functions
        // ===============================

        /**
         * Select a specific date
         * @param {string} date Date string
         */
        function selectDate(date) {
            currentDate.value = date;
            currentView.value = "date";
            loadTasks();
        }

        // ===============================
        // Task Modal Functions
        // ===============================

        /**
         * Open add task modal
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
         * Open edit task modal
         * @param {Object|number|string} task Task object or ID
         */
        async function openEditTaskModal(task) {
            try {
                // Handle direct ID input (as number or string)
                if (
                    (typeof task === "number" || typeof task === "string") &&
                    !isNaN(Number(task))
                ) {
                    await fetchAndEditTask(Number(task));
                    return;
                }

                // Handle empty task or empty array
                if (!task || (Array.isArray(task) && task.length === 0)) {
                    showNotification("編集するタスクが見つかりません", "error");
                    return;
                }

                // Handle task as object with ID property
                if (typeof task === "object" && task !== null) {
                    // Force reload categories before opening modal
                    await loadCategories();

                    taskModalMode.value = "edit";

                    // Ensure task ID is properly set
                    if (task.id === undefined || task.id === null) {
                        showNotification("タスクIDが見つかりません", "error");
                        return;
                    }

                    selectedTaskId.value = Number(task.id);

                    // Create deep copy of task to avoid reference issues
                    selectedTaskData.value = JSON.parse(JSON.stringify(task));

                    showTaskModal.value = true;
                    console.log("Opening edit modal:", task);
                }
            } catch (error) {
                handleError(error, "タスク編集の準備中にエラーが発生しました");
            }
        }

        /**
         * Fetch task data by ID and open edit modal
         * @param {number} taskId Task ID
         */
        async function fetchAndEditTask(taskId) {
            try {
                // First, check if task is already loaded
                const task = todos.value.find((t) => t.id === taskId);

                if (task) {
                    // Ensure categories are loaded
                    await loadCategories();

                    taskModalMode.value = "edit";
                    selectedTaskId.value = Number(taskId);
                    selectedTaskData.value = JSON.parse(JSON.stringify(task));
                    showTaskModal.value = true;
                    return;
                }

                // Fetch task data from API
                const response = await TodoApi.getTaskById(taskId);

                // Ensure categories are loaded
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
         * Close task modal
         */
        function closeTaskModal() {
            showTaskModal.value = false;
        }

        // ===============================
        // Task Functions
        // ===============================

        /**
         * Submit task (create or update)
         * @param {Object} taskData Task data
         */
        async function submitTask(taskData) {
            try {
                // Clone data to avoid modifying original
                const preparedData = { ...taskData };

                // Format dates for API
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
                    // Add new task
                    response = await TodoApi.createTask(preparedData);
                } else {
                    // Ensure task ID is available
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

                    // Check if adding due date to a previous memo
                    const isAddingDueDateToMemo =
                        preparedData.due_date &&
                        (!selectedTaskData.value.due_date ||
                            selectedTaskData.value.due_date === null);

                    console.log("Data to send to API:", preparedData);
                    // Update existing task
                    response = await TodoApi.updateTask(taskId, preparedData);

                    // If adding due date to a memo, refresh memo list
                    if (isAddingDueDateToMemo) {
                        await refreshMemoList();
                    }
                }
                console.log("API response:", response.data);
                closeTaskModal();

                // After closing modal, reload tasks based on current view
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
                    // Disable cache
                    params: { _t: new Date().getTime() },
                });

                // Response data is automatically processed
                const html = response.data;

                // Find memo list containers
                const memoContainers = document.querySelectorAll(
                    ".memo-list-container",
                );

                if (memoContainers.length > 0) {
                    // Update all memo containers with new HTML
                    memoContainers.forEach((container) => {
                        container.innerHTML = html;
                    });

                    // Reattach event listeners
                    attachMemoListEvents();
                    return true;
                } else {
                    return false;
                }
            } catch (error) {
                console.error("Error updating memo list:", error);
                return false;
            }
        }

        /**
         * Reattach memo list events
         */
        function attachMemoListEvents() {
            // Find all trash buttons in memo list and attach click handlers
            const trashButtons = document.querySelectorAll(
                '.memo-list-container button[onclick*="trashMemo"]',
            );
            // Reattach other event listeners as needed
        }

        /**
         * Toggle task status
         * @param {Object} task Task object
         */
        async function toggleTaskStatus(task) {
            try {
                await TodoApi.toggleTask(task.id);

                // Optimistic update
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
         * Handle task delete
         * @param {number} id Task ID
         * @param {boolean} deleteAllRecurringFlag Delete all recurring tasks flag
         */
        function handleTaskDelete(id, deleteAllRecurringFlag) {
            // First close task modal
            closeTaskModal();

            // Show delete confirmation modal
            selectedTaskId.value = id;
            deleteAllRecurring.value = deleteAllRecurringFlag;

            // Find task for confirmation
            const task = todos.value.find((t) => t.id === id);

            if (task) {
                selectedTaskData.value = task;
            }

            // Check if task is recurring
            const recurring = isRecurringTask(selectedTaskData.value);

            // Show confirmation modal with recurring status
            showDeleteConfirmModal.value = true;
        }

        /**
         * Confirm task delete
         * @param {Object} task Task object
         */
        function confirmDeleteTask(task) {
            selectedTaskId.value = task.id;
            selectedTaskData.value = task;

            // Check if task is recurring before showing modal
            const recurring = isRecurringTask(task);

            // Pass the recurring status to the modal
            showDeleteConfirmModal.value = true;
        }

        /**
         * Confirm delete
         * @param {boolean} confirmed Confirmed flag
         */
        async function confirmDelete(
            confirmed = true,
            deleteAllRecurringFlag = false,
        ) {
            // Only continue if user confirmed
            if (!confirmed) {
                showDeleteConfirmModal.value = false;
                return;
            }

            try {
                await TodoApi.deleteTask(
                    selectedTaskId.value,
                    deleteAllRecurringFlag,
                );
                showNotification("タスクを削除しました");
                showDeleteConfirmModal.value = false;

                // Update task list
                await loadTasks();

                // Force update filtered tasks
                todos.value = [...todos.value]; // Trigger reactivity
            } catch (error) {
                handleError(error, "タスクの削除に失敗しました");
            }
        }

        /**
         * Go to today - FIXED to use local date format
         */
        function goToToday() {
            const today = new Date();
            currentDate.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
        }

        /**
         * Go to previous day - FIXED to use local date format
         */
        function previousDay() {
            const date = new Date(currentDate.value);
            date.setDate(date.getDate() - 1);
            currentDate.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
        }

        /**
         * Go to next day - FIXED to use local date format
         */
        function nextDay() {
            const date = new Date(currentDate.value);
            date.setDate(date.getDate() + 1);
            currentDate.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
        }

        onMounted(() => {
            // Bladeからビュータイプを取得 (data-view属性)
            const todoAppElement = document.getElementById("todo-app");
            const viewFromBlade = todoAppElement?.dataset?.view;

            // URLからビュータイプを取得
            const urlParams = new URLSearchParams(window.location.search);
            const viewParam = urlParams.get("view");

            // Blade -> URL -> デフォルトの優先順位でビュータイプを設定
            if (
                viewFromBlade &&
                [
                    "today",
                    "date",
                    "scheduled",
                    "inbox",
                    "shared",
                    "kanban",
                ].includes(viewFromBlade)
            ) {
                currentView.value = viewFromBlade;
            } else if (
                viewParam &&
                [
                    "today",
                    "date",
                    "scheduled",
                    "inbox",
                    "shared",
                    "kanban",
                ].includes(viewParam)
            ) {
                currentView.value = viewParam;
            }

            // 現在のユーザーIDを取得
            if (window.Laravel && window.Laravel.user) {
                currentUserId.value = window.Laravel.user.id;
            }

            // データ読み込み
            loadTasks();
            loadCategories();

            // Listen for edit-todo events from legacy code
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
                            showNotification(
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
            // 状態変数
            todos,
            categories,
            currentView,
            currentDate,
            formattedDate,
            filteredTodos,
            isSharedView,
            currentUserId,

            // モーダル関連
            showTaskModal,
            taskModalMode,
            selectedTaskId,
            selectedTaskData,
            showDeleteConfirmModal,

            // 通知関連
            notificationMessage,
            notificationType,
            notificationVisible,

            // 関数
            setView,
            selectDate,
            previousDay,
            nextDay,
            goToToday,
            openAddTaskModal,
            openEditTaskModal,
            isRecurringTask,
            closeTaskModal,
            submitTask,
            toggleTaskStatus,
            handleTaskDelete,
            confirmDeleteTask,
            confirmDelete,
            loadCategories,
            handleTaskStatusChange,
            showShareByLocationModal,
            openShareByLocationModal,
            handleLocationShared,
        };
    },
};
</script>
