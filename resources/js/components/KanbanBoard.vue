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
        <div
            v-else-if="!isLoading"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
        >
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
                <div class="space-y-2">
                    <div
                        v-for="task in getTasksByStatus('pending')"
                        :key="task.id"
                        class="bg-white rounded shadow p-3 cursor-pointer"
                        draggable="true"
                        @dragstart="onDragStart($event, task.id)"
                        @click="$emit('edit-task', task)"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-sm">
                                {{ task.title }}
                            </h4>
                            <span
                                v-if="task.category"
                                class="w-2 h-2 rounded-full"
                                :style="{
                                    backgroundColor: task.category.color,
                                }"
                            ></span>
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
                    </div>
                </div>
            </div>

            <!-- In Progress Column -->
            <div
                class="bg-blue-100 rounded-lg p-4"
                @dragover.prevent
                @drop="onDrop($event, 'in_progress')"
            >
                <!-- 同様の内容で 'in_progress' ステータス用 -->
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
                <div class="space-y-2">
                    <div
                        v-for="task in getTasksByStatus('in_progress')"
                        :key="task.id"
                        class="bg-white rounded shadow p-3 cursor-pointer"
                        draggable="true"
                        @dragstart="onDragStart($event, task.id)"
                        @click="$emit('edit-task', task)"
                    >
                        <!-- タスクカード内容（To Doカラムと同様） -->
                    </div>
                </div>
            </div>

            <!-- Review Column -->
            <div
                class="bg-yellow-100 rounded-lg p-4"
                @dragover.prevent
                @drop="onDrop($event, 'review')"
            >
                <!-- 同様の内容で 'review' ステータス用 -->
            </div>

            <!-- Completed Column -->
            <div
                class="bg-green-100 rounded-lg p-4"
                @dragover.prevent
                @drop="onDrop($event, 'completed')"
            >
                <!-- 同様の内容で 'completed' ステータス用 -->
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from "vue";
import TodoApi from "../api/todo";

export default {
    name: "KanbanBoard",

    props: {
        // 親コンポーネントから渡されるカテゴリーリスト
        categories: {
            type: Array,
            default: () => [],
        },
        // 現在のユーザーID
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

        // 初期タスク読み込み
        const loadTasks = async () => {
            try {
                isLoading.value = true;
                errorMessage.value = "";

                // 共有タスクを取得
                const response = await TodoApi.getTasks("shared");

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
        };

        // 特定ステータスのタスクを取得
        const getTasksByStatus = (status) => {
            return filteredTasks.value.filter((task) => task.status === status);
        };

        // ドラッグ開始ハンドラー
        const onDragStart = (event, taskId) => {
            draggedTaskId.value = taskId;
            event.dataTransfer.setData("text/plain", taskId);
            event.target.classList.add("opacity-50");
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
                // 親コンポーネントにステータス変更を通知
                emit("task-status-changed", Number(taskId), newStatus);

                // 楽観的UI更新（APIレスポンス待たずに表示を更新）
                const taskIndex = allTasks.value.findIndex(
                    (t) => t.id === Number(taskId),
                );
                if (taskIndex !== -1) {
                    allTasks.value[taskIndex].status = newStatus;
                    applyFilters(); // 再フィルタリング
                }
            } catch (error) {
                console.error("Error updating task status:", error);
                errorMessage.value = "タスクステータスの更新に失敗しました。";
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
            // TodoApp.vueのopenAddTaskModalを呼び出す
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

            // メソッド
            getTasksByStatus,
            onDragStart,
            onDrop,
            formatDate,
            formatTime,
            applyFilters,
            openAddTaskModal,
        };
    },
};
</script>

<style scoped>
.kanban-container {
    min-height: calc(100vh - 180px);
}

/* ドラッグ＆ドロップスタイル */
[draggable="true"] {
    cursor: grab;
}

.opacity-50 {
    opacity: 0.5;
}
</style>
