<!-- KanbanBoard.vue のタグ構造を修正 -->
<template>
    <div class="kanban-container">
        <!-- ヘッダー（フィルター、コントロール） -->
        <div class="bg-white shadow-sm p-4 mb-4 rounded-lg">
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
            >
                <h1 class="text-xl font-semibold text-gray-800">
                    チームタスク管理ボード
                </h1>

                <div class="flex flex-wrap gap-2">
                    <!-- カテゴリーフィルター -->
                    <select
                        v-model="selectedCategoryId"
                        class="bg-white border border-gray-300 rounded-md px-3 py-1 text-sm"
                        @change="applyFilters"
                    >
                        <option value="">全てのカテゴリー</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>

                    <!-- ユーザーフィルター -->
                    <select
                        v-model="selectedUserId"
                        class="bg-white border border-gray-300 rounded-md px-3 py-1 text-sm"
                        @change="applyFilters"
                    >
                        <option value="">全てのメンバー</option>
                        <option
                            v-for="user in teamUsers"
                            :key="user.id"
                            :value="user.id"
                        >
                            {{ user.name }}
                        </option>
                    </select>

                    <!-- タスク検索 -->
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="タスクを検索..."
                            class="bg-white border border-gray-300 rounded-md pl-8 pr-3 py-1 text-sm"
                            @input="applyFilters"
                        />
                        <svg
                            class="absolute left-2 top-1.5 h-4 w-4 text-gray-400"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>

                    <!-- タスク追加ボタン -->
                    <button
                        @click="openAddTaskModal"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm font-medium flex items-center"
                    >
                        <svg
                            class="h-4 w-4 mr-1"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        新しいタスク
                    </button>
                </div>
            </div>
        </div>

        <!-- ローディングインジケーター -->
        <div v-if="isLoading" class="flex justify-center items-center h-64">
            <div
                class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"
            ></div>
        </div>

        <!-- エラーメッセージ -->
        <div
            v-if="errorMessage"
            class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
        >
            <strong class="font-bold">エラー!</strong>
            <span class="block sm:inline"> {{ errorMessage }}</span>
            <button
                @click="errorMessage = ''"
                class="absolute top-0 bottom-0 right-0 px-4"
            >
                <svg
                    class="h-6 w-6 text-red-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <!-- カンバンボード -->
        <div v-else-if="!isLoading" class="grid grid-cols-4 gap-4">
            <!-- To Do Column -->
            <div
                class="bg-gray-100 rounded-lg p-4"
                @dragover.prevent
                @drop="onDrop($event, 'pending')"
            >
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-medium">To Do</h3>
                    <div class="flex items-center">
                        <span
                            class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full mr-2"
                        >
                            {{ getTasksByStatus("pending").length }}
                        </span>
                        <button
                            @click="openAddTaskModal('pending')"
                            class="bg-white p-1 rounded-full shadow hover:bg-gray-100"
                        >
                            <svg
                                class="w-4 h-4 text-gray-600"
                                xmlns="http://www.w3.org/2000/svg"
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
                </div>
                <div class="space-y-2 task-container">
                    <div
                        v-for="task in getTasksByStatus('pending')"
                        :key="task.id"
                        class="bg-white rounded shadow p-3 cursor-pointer task-card"
                        :class="{
                            'cursor-not-allowed opacity-75':
                                task.can_edit === false,
                            'my-task': task.user_id === currentUserId,
                        }"
                        :draggable="task.can_edit !== false"
                        @dragstart="onDragStart($event, task.id, task.can_edit)"
                        @click="handleTaskClick(task)"
                        :data-task-id="task.id"
                        :data-status="'pending'"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-sm">
                                {{ task.title }}
                            </h4>
                            <div class="flex items-center space-x-1">
                                <!-- 所有者バッジ -->
                                <span
                                    v-if="task.user_id === currentUserId"
                                    class="text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full"
                                >
                                    自分
                                </span>
                                <!-- カテゴリー色 -->
                                <span
                                    v-if="task.category"
                                    class="w-2 h-2 rounded-full"
                                    :style="{
                                        backgroundColor: task.category.color,
                                    }"
                                ></span>
                                <!-- 読み取り専用アイコン -->
                                <svg
                                    v-if="task.can_edit === false"
                                    class="h-3 w-3 text-gray-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div
                            v-if="task.due_date"
                            class="mt-2 text-xs text-gray-500"
                        >
                            {{ formatDate(task.due_date) }}
                            <span v-if="task.due_time">{{
                                formatTime(task.due_time)
                            }}</span>
                        </div>
                        <!-- タスク所有者情報 -->
                        <div
                            v-if="task.user && task.user_id !== currentUserId"
                            class="mt-1 text-xs text-gray-400"
                        >
                            {{ task.user.name }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- In Progress Column -->
            <div
                class="bg-blue-100 rounded-lg p-4"
                @dragover.prevent
                @drop="onDrop($event, 'in_progress')"
                data-status="in_progress"
            >
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-medium">In Progress</h3>
                    <div class="flex items-center">
                        <span
                            class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full mr-2"
                        >
                            {{ getTasksByStatus("in_progress").length }}
                        </span>
                        <button
                            @click="openAddTaskModal('in_progress')"
                            class="bg-white p-1 rounded-full shadow hover:bg-gray-100"
                        >
                            <svg
                                class="w-4 h-4 text-gray-600"
                                xmlns="http://www.w3.org/2000/svg"
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
                </div>
                <div class="space-y-2 task-container">
                    <div
                        v-for="task in getTasksByStatus('in_progress')"
                        :key="task.id"
                        class="bg-white rounded shadow p-3 cursor-pointer task-card"
                        :class="{
                            'cursor-not-allowed opacity-75':
                                task.can_edit === false,
                            'my-task': task.user_id === currentUserId,
                        }"
                        :draggable="task.can_edit !== false"
                        @dragstart="onDragStart($event, task.id, task.can_edit)"
                        @click="handleTaskClick(task)"
                        :data-task-id="task.id"
                        :data-status="'in_progress'"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-sm">
                                {{ task.title }}
                            </h4>
                            <div class="flex items-center space-x-1">
                                <span
                                    v-if="task.user_id === currentUserId"
                                    class="text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full"
                                >
                                    自分
                                </span>
                                <span
                                    v-if="task.category"
                                    class="w-2 h-2 rounded-full"
                                    :style="{
                                        backgroundColor: task.category.color,
                                    }"
                                ></span>
                                <svg
                                    v-if="task.can_edit === false"
                                    class="h-3 w-3 text-gray-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div
                            v-if="task.due_date"
                            class="mt-2 text-xs text-gray-500"
                        >
                            {{ formatDate(task.due_date) }}
                            <span v-if="task.due_time">{{
                                formatTime(task.due_time)
                            }}</span>
                        </div>
                        <div
                            v-if="task.user && task.user_id !== currentUserId"
                            class="mt-1 text-xs text-gray-400"
                        >
                            {{ task.user.name }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review Column -->
            <div
                class="bg-yellow-100 rounded-lg p-4"
                @dragover.prevent
                @drop="onDrop($event, 'review')"
                data-status="review"
            >
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-medium">Review</h3>
                    <div class="flex items-center">
                        <span
                            class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full mr-2"
                        >
                            {{ getTasksByStatus("review").length }}
                        </span>
                        <button
                            @click="openAddTaskModal('review')"
                            class="bg-white p-1 rounded-full shadow hover:bg-gray-100"
                        >
                            <svg
                                class="w-4 h-4 text-gray-600"
                                xmlns="http://www.w3.org/2000/svg"
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
                </div>
                <div class="space-y-2 task-container">
                    <div
                        v-for="task in getTasksByStatus('review')"
                        :key="task.id"
                        class="bg-white rounded shadow p-3 cursor-pointer task-card"
                        :class="{
                            'cursor-not-allowed opacity-75':
                                task.can_edit === false,
                            'my-task': task.user_id === currentUserId,
                        }"
                        :draggable="task.can_edit !== false"
                        @dragstart="onDragStart($event, task.id, task.can_edit)"
                        @click="handleTaskClick(task)"
                        :data-task-id="task.id"
                        :data-status="'review'"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-sm">
                                {{ task.title }}
                            </h4>
                            <div class="flex items-center space-x-1">
                                <span
                                    v-if="task.user_id === currentUserId"
                                    class="text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full"
                                >
                                    自分
                                </span>
                                <span
                                    v-if="task.category"
                                    class="w-2 h-2 rounded-full"
                                    :style="{
                                        backgroundColor: task.category.color,
                                    }"
                                ></span>
                                <svg
                                    v-if="task.can_edit === false"
                                    class="h-3 w-3 text-gray-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div
                            v-if="task.due_date"
                            class="mt-2 text-xs text-gray-500"
                        >
                            {{ formatDate(task.due_date) }}
                            <span v-if="task.due_time">{{
                                formatTime(task.due_time)
                            }}</span>
                        </div>
                        <div
                            v-if="task.user && task.user_id !== currentUserId"
                            class="mt-1 text-xs text-gray-400"
                        >
                            {{ task.user.name }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed Column -->
            <div
                class="bg-green-100 rounded-lg p-4"
                @dragover.prevent
                @drop="onDrop($event, 'completed')"
                data-status="completed"
            >
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-medium">Completed</h3>
                    <div class="flex items-center">
                        <span
                            class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full mr-2"
                        >
                            {{ getTasksByStatus("completed").length }}
                        </span>
                        <button
                            @click="openAddTaskModal('completed')"
                            class="bg-white p-1 rounded-full shadow hover:bg-gray-100"
                        >
                            <svg
                                class="w-4 h-4 text-gray-600"
                                xmlns="http://www.w3.org/2000/svg"
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
                </div>
                <div class="space-y-2 task-container">
                    <div
                        v-for="task in getTasksByStatus('completed')"
                        :key="task.id"
                        class="bg-white rounded shadow p-3 cursor-pointer task-card"
                        :class="{
                            'cursor-not-allowed opacity-75':
                                task.can_edit === false,
                            'my-task': task.user_id === currentUserId,
                        }"
                        :draggable="task.can_edit !== false"
                        @dragstart="onDragStart($event, task.id, task.can_edit)"
                        @click="handleTaskClick(task)"
                        :data-task-id="task.id"
                        :data-status="'completed'"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-sm">
                                {{ task.title }}
                            </h4>
                            <div class="flex items-center space-x-1">
                                <span
                                    v-if="task.user_id === currentUserId"
                                    class="text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full"
                                >
                                    自分
                                </span>
                                <span
                                    v-if="task.category"
                                    class="w-2 h-2 rounded-full"
                                    :style="{
                                        backgroundColor: task.category.color,
                                    }"
                                ></span>
                                <svg
                                    v-if="task.can_edit === false"
                                    class="h-3 w-3 text-gray-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div
                            v-if="task.due_date"
                            class="mt-2 text-xs text-gray-500"
                        >
                            {{ formatDate(task.due_date) }}
                            <span v-if="task.due_time">{{
                                formatTime(task.due_time)
                            }}</span>
                        </div>
                        <div
                            v-if="task.user && task.user_id !== currentUserId"
                            class="mt-1 text-xs text-gray-400"
                        >
                            {{ task.user.name }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 権限エラーメッセージモーダル -->
        <div
            v-if="showPermissionError"
            class="fixed inset-0 flex items-center justify-center z-50"
        >
            <div
                class="absolute inset-0 bg-black bg-opacity-40"
                @click="showPermissionError = false"
            ></div>
            <div class="bg-white rounded-lg p-5 max-w-md relative z-10">
                <div class="flex items-start">
                    <svg
                        class="h-6 w-6 text-yellow-500 mr-3"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            権限がありません
                        </h3>
                        <p class="text-sm text-gray-500">
                            このタスクは閲覧のみ許可されています。編集するには、タスクの所有者に編集権限をリクエストしてください。
                        </p>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button
                        @click="showPermissionError = false"
                        class="px-4 py-2 bg-blue-500 text-white rounded-md text-sm"
                    >
                        閉じる
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, watch } from "vue";
import TodoApi from "../api/todo";

export default {
    name: "KanbanBoard",

    props: {
        categories: {
            type: Array,
            default: () => [],
        },
        currentUserId: {
            type: Number,
            default: null,
        },
    },

    emits: ["edit-task", "task-status-changed"],

    setup(props, { emit }) {
        // 状態
        const allTasks = ref([]);
        const filteredTasks = ref([]);
        const isLoading = ref(true);
        const errorMessage = ref("");

        // フィルター
        const selectedCategoryId = ref("");
        const selectedUserId = ref("");
        const searchQuery = ref("");
        const teamUsers = ref([]);

        // ドラッグ＆ドロップ
        const draggedTaskId = ref(null);

        // 権限エラーメッセージ用の状態
        const showPermissionError = ref(false);

        // 初期タスク読み込み
        const loadTasks = async () => {
            try {
                isLoading.value = true;
                errorMessage.value = "";

                // 権限情報を含めて共有タスクを取得
                const response =
                    await TodoApi.getTasksWithPermissions("shared");

                console.log(
                    "Loaded shared tasks with permissions:",
                    response.data,
                );

                allTasks.value = response.data;

                // チームメンバー情報を抽出
                const userMap = new Map();
                allTasks.value.forEach((task) => {
                    if (task.user && !userMap.has(task.user.id)) {
                        userMap.set(task.user.id, {
                            id: task.user.id,
                            name: task.user.name,
                            email: task.user.email,
                        });
                    }
                });
                teamUsers.value = Array.from(userMap.values());

                // フィルターを適用
                applyFilters();
            } catch (error) {
                console.error("Error loading tasks:", error);
                errorMessage.value = "タスクの読み込みに失敗しました。";
            } finally {
                isLoading.value = false;
            }
        };

        // フィルター適用
        const applyFilters = () => {
            console.log("Applying filters with:", {
                categoryId: selectedCategoryId.value,
                userId: selectedUserId.value,
                searchQuery: searchQuery.value,
            });

            filteredTasks.value = allTasks.value.filter((task) => {
                // カテゴリーフィルター
                if (
                    selectedCategoryId.value &&
                    task.category_id != selectedCategoryId.value
                ) {
                    return false;
                }

                // ユーザーフィルター
                if (
                    selectedUserId.value &&
                    task.user_id != selectedUserId.value
                ) {
                    return false;
                }

                // 検索クエリ
                if (searchQuery.value) {
                    const query = searchQuery.value.toLowerCase();
                    return task.title.toLowerCase().includes(query);
                }

                return true;
            });

            console.log("Filtered tasks:", filteredTasks.value);

            // ステータスごとのタスク数をログ出力（デバッグ用）
            console.log("Tasks by status:", {
                pending: getTasksByStatus("pending").length,
                in_progress: getTasksByStatus("in_progress").length,
                review: getTasksByStatus("review").length,
                completed: getTasksByStatus("completed").length,
            });
        };

        // 特定ステータスのタスクを取得
        const getTasksByStatus = (status) => {
            return filteredTasks.value.filter((task) => task.status === status);
        };

        // ドラッグ開始ハンドラー - 権限チェックを追加
        const onDragStart = (event, taskId, canEdit) => {
            // 編集権限がない場合はドラッグを中止
            if (canEdit === false) {
                event.preventDefault();
                showPermissionError.value = true;
                return;
            }

            draggedTaskId.value = taskId;
            event.dataTransfer.setData("text/plain", taskId);

            // ドラッグ中のスタイルを適用
            event.target.classList.add("opacity-50");
        };

        // タスククリック処理 - 権限チェックを追加
        const handleTaskClick = (task) => {
            // 編集できないタスクをクリックしたときは権限エラーを表示
            if (task.can_edit === false) {
                showPermissionError.value = true;
                return;
            }

            // 通常通り編集モーダルを開く
            emit("edit-task", task);
        };

        // ドロップハンドラー
        const onDrop = async (event, newStatus) => {
            // ドラッグ中のスタイルをリセット
            document.querySelectorAll(".opacity-50").forEach((el) => {
                el.classList.remove("opacity-50");
            });

            const taskId = event.dataTransfer.getData("text/plain");
            if (!taskId) return;

            try {
                console.log(`Dropping task ${taskId} to ${newStatus}`);

                // 親コンポーネントにステータス変更を通知
                emit("task-status-changed", Number(taskId), newStatus);

                // 楽観的UI更新（APIレスポンス待たずに表示を更新）
                const taskIndex = allTasks.value.findIndex(
                    (t) => t.id === Number(taskId),
                );
                if (taskIndex !== -1) {
                    console.log(
                        `Updating task ${taskId} status from ${allTasks.value[taskIndex].status} to ${newStatus}`,
                    );
                    allTasks.value[taskIndex].status = newStatus;
                    applyFilters(); // 再フィルタリング
                }
            } catch (error) {
                console.error("Error updating task status:", error);
                errorMessage.value = "タスクステータスの更新に失敗しました。";

                // エラー発生時はタスクを再読み込み
                await loadTasks();
            }
        };

        // 日付フォーマット
        const formatDate = (dateStr) => {
            if (!dateStr) return "";
            const date = new Date(dateStr);
            return new Intl.DateTimeFormat("ja-JP", {
                year: "numeric",
                month: "2-digit",
                day: "2-digit",
            }).format(date);
        };

        // 時間フォーマット
        const formatTime = (timeStr) => {
            if (!timeStr) return "";

            // ISO形式かHH:MM形式かを判断
            if (timeStr.includes("T")) {
                const date = new Date(timeStr);
                return `${String(date.getHours()).padStart(2, "0")}:${String(date.getMinutes()).padStart(2, "0")}`;
            } else {
                // HH:MM(:SS)形式の場合
                return timeStr.substring(0, 5);
            }
        };

        // 新規タスク追加モーダルを開く
        const openAddTaskModal = (status = "pending") => {
            // 新規タスク追加画面を開く処理
            const taskData = {
                title: "",
                description: "",
                status: status,
                _isSharedViewEdit: true, // 共有ビューからの編集フラグ
            };

            // グローバル関数を使うか、状態を親コンポーネントに渡す
            if (window.editTodo) {
                window.editTodo(null, taskData);
            } else {
                emit("edit-task", taskData);
            }
        };

        // コンポーネントがマウントされたとき
        onMounted(() => {
            loadTasks();
        });

        // フィルターが変更されたとき
        watch([selectedCategoryId, selectedUserId, searchQuery], () => {
            applyFilters();
        });

        return {
            // 状態
            allTasks,
            filteredTasks,
            isLoading,
            errorMessage,
            selectedCategoryId,
            selectedUserId,
            searchQuery,
            teamUsers,
            showPermissionError,

            // メソッド
            getTasksByStatus,
            onDragStart,
            onDrop,
            formatDate,
            formatTime,
            applyFilters,
            openAddTaskModal,
            handleTaskClick,
        };
    },
};
</script>

<style scoped>
.kanban-container {
    min-height: calc(100vh - 180px);
}

/* CSS Grid を強制的に4カラム表示に */
.grid-cols-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

/* ドラッグ＆ドロップスタイル */
[draggable="true"] {
    cursor: grab;
}

.opacity-50 {
    opacity: 0.5;
}

/* モバイル表示のためのレスポンシブ対応（必要に応じて） */
@media (max-width: 1024px) {
    .grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .grid-cols-4 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
}

/* スクロールバーのスタイリング */
.task-container {
    max-height: calc(100vh - 250px);
    overflow-y: auto;
}

.task-container::-webkit-scrollbar {
    width: 4px;
}

.task-container::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
}

.task-container::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 2px;
}

/* 自分のタスクのスタイル */
.my-task {
    border-left: 3px solid #3b82f6; /* 青色のボーダーで自分のタスクを強調 */
}

/* 共有タスクのスタイル */
.task-card:not(.my-task) {
    border-left: 3px solid #10b981; /* 緑色のボーダーで共有タスクを示す */
}

/* 読み取り専用タスクのスタイル */
.cursor-not-allowed {
    cursor: not-allowed !important;
}
</style>
