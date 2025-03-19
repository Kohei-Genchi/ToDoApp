<template>
    <div class="bg-gray-100 min-h-screen main-content">
        <!-- Header ("+ New Task" button included) -->
        <app-header
            :current-view="currentView"
            @set-view="setView"
            @show-calendar="showCalendarView"
            @show-shared="showSharedTasksView"
            @add-task="openAddTaskModal"
        />

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
            <!-- Weekly Date Navigation - new component -->
            <weekly-date-navigation
                v-if="currentView !== 'calendar' && currentView !== 'shared'"
                :current-date="currentDate"
                @date-selected="selectDate"
            />

            <!-- Calendar Month Navigation -->
            <month-navigation
                v-if="currentView === 'calendar'"
                :formatted-month="formattedMonth"
                @previous-month="previousMonth"
                @next-month="nextMonth"
            />

            <!-- Calendar View -->
            <todo-calendar
                v-if="currentView === 'calendar'"
                :current-date="currentDate"
                :todos="todos"
                @date-selected="selectDate"
                @edit-task="openEditTaskModal"
            />

            <shared-tasks-calendar-view
                v-if="currentView === 'shared'"
                @back="setView('today')"
                @task-updated="loadSharedTasks"
                @task-created="loadSharedTasks"
                @task-deleted="loadSharedTasks"
            />

            <!-- Task List (normal view) -->
            <todo-list
                v-if="currentView !== 'calendar' && currentView !== 'shared'"
                :todos="filteredTodos"
                :categories="categories"
                @toggle-task="toggleTaskStatus"
                @edit-task="openEditTaskModal"
                @delete-task="confirmDeleteTask"
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

        <!-- Notification Component -->
        <notification-component ref="notification" />
    </div>
</template>

<script>
import { ref, computed, onMounted, defineAsyncComponent } from "vue";
import TodoApi from "../api/todo";
import CategoryApi from "../api/category";

// Component imports
const TodoList = defineAsyncComponent(() => import("./TodoList.vue"));
const TodoCalendar = defineAsyncComponent(() => import("./TodoCalendar.vue"));
const TaskModal = defineAsyncComponent(() => import("./TaskModal.vue"));
const DeleteConfirmModal = defineAsyncComponent(
    () => import("./DeleteConfirmModal.vue"),
);
const NotificationComponent = defineAsyncComponent(
    () => import("./UI/NotificationComponent.vue"),
);
const SharedTasksCalendarView = defineAsyncComponent(
    () => import("./SharedTasksCalendarView.vue"),
);

// Component imports
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
        WeeklyDateNavigation,
        SharedTasksCalendarView,
    },

    setup() {
        // ===============================
        // State
        // ===============================
        const todos = ref([]);
        const categories = ref([]);
        const currentView = ref("today");
        const currentDate = ref(new Date().toISOString().split("T")[0]);

        // Modal state
        const showTaskModal = ref(false);
        const taskModalMode = ref("add");
        const selectedTaskId = ref(null);
        const selectedTaskData = ref(null);
        const showDeleteConfirmModal = ref(false);
        const deleteAllRecurring = ref(false);

        // Notification reference
        const notification = ref(null);

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
            return task?.recurrence_type && task.recurrence_type !== "none";
        }

        /**
         * Handle error and show notification
         * @param {Error} error Error object
         * @param {string} defaultMessage Default error message
         */
        function handleError(error, defaultMessage) {
            const errorMessage = error?.response?.data?.error || defaultMessage;
            notification.value?.show(errorMessage, "error");
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
         * Format month for display
         */
        const formattedMonth = computed(() => {
            const date = new Date(currentDate.value);
            const options = { year: "numeric", month: "long" };
            return date.toLocaleDateString("ja-JP", options);
        });

        /**
         * Filter todos based on current date
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
        // Data Loading Functions
        // ===============================

        /**
         * Load tasks
         */
        async function loadTasks() {
            try {
                const response = await TodoApi.getTasks(
                    currentView.value,
                    currentDate.value,
                );
                // console.log(response.data[0]);
                //Check if response data is an array - handle objects and null
                if (!Array.isArray(response.data)) {
                    todos.value = [];
                    return;
                }

                // Process response to ensure consistency
                todos.value = response.data.map((todo) => {
                    // Create deep copy to avoid reference issues
                    const processedTodo = { ...todo };
                    // Ensure ID is a number
                    if (processedTodo.id !== undefined) {
                        processedTodo.id = Number(processedTodo.id);
                    }

                    // Ensure category_id is a number
                    if (
                        processedTodo.category_id !== null &&
                        processedTodo.category_id !== undefined
                    ) {
                        processedTodo.category_id = Number(
                            processedTodo.category_id,
                        );
                    }

                    // Add formatted date for comparison
                    if (processedTodo.due_date) {
                        processedTodo.formatted_due_date =
                            formatDateForComparison(processedTodo.due_date);
                    }

                    return processedTodo;
                });
            } catch (error) {
                handleError(error, "タスクの読み込みに失敗しました");
            }
            console.log("Updated task list:", todos.value);
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
                currentDate.value = new Date().toISOString().split("T")[0];
            }
            loadTasks();
        }

        /**
         * Show calendar view
         */
        function showCalendarView() {
            currentView.value = "calendar";
            loadTasks();
        }

        /**
         * Show shared tasks view - directly show calendar view of shared tasks
         */
        function showSharedTasksView() {
            currentView.value = "shared";
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

        /**
         * Go to previous month
         */
        function previousMonth() {
            const date = new Date(currentDate.value);
            date.setMonth(date.getMonth() - 1);
            currentDate.value = date.toISOString().split("T")[0];
            loadTasks();
        }

        /**
         * Go to next month
         */
        function nextMonth() {
            const date = new Date(currentDate.value);
            date.setMonth(date.getMonth() + 1);
            currentDate.value = date.toISOString().split("T")[0];
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

        async function loadSharedTasks() {
            await loadTasks(); // This calls your existing loadTasks method
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
                    notification.value?.show(
                        "編集するタスクが見つかりません",
                        "error",
                    );
                    return;
                }

                // Handle task as object with ID property
                if (typeof task === "object" && task !== null) {
                    // Force reload categories before opening modal
                    await loadCategories();

                    taskModalMode.value = "edit";

                    // Ensure task ID is properly set
                    if (task.id === undefined || task.id === null) {
                        notification.value?.show(
                            "タスクIDが見つかりません",
                            "error",
                        );
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

            // Show confirmation modal - don't delete until user confirms
            showDeleteConfirmModal.value = true;
        }

        /**
         * Confirm task delete
         * @param {Object} task Task object
         */
        function confirmDeleteTask(task) {
            selectedTaskId.value = task.id;
            selectedTaskData.value = task;
            showDeleteConfirmModal.value = true;
        }

        /**
         * Confirm delete
         * @param {boolean} confirmed Confirmed flag
         */
        async function confirmDelete(confirmed = true) {
            // Only continue if user confirmed
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

                // Update task list
                await loadTasks();

                // Force update filtered tasks
                todos.value = [...todos.value]; // Trigger reactivity
            } catch (error) {
                handleError(error, "タスクの削除に失敗しました");
            }
        }

        // Initialization
        onMounted(() => {
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
            // State
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

            // View functions
            setView,
            showCalendarView,
            showSharedTasksView,

            // Date functions
            selectDate,
            previousMonth,
            nextMonth,

            // Task modal functions
            openAddTaskModal,
            openEditTaskModal,
            fetchAndEditTask,
            closeTaskModal,

            // Task functions
            submitTask,
            toggleTaskStatus,
            handleTaskDelete,
            confirmDeleteTask,
            confirmDelete,

            // Other functions
            loadCategories,
            isRecurringTask,
        };
    },
};
</script>
