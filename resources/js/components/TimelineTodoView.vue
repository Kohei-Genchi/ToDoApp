<!-- TimelineTodoView.vue -->
<template>
    <div class="timeline-todo-view max-w-3xl mx-auto px-4 py-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">今日のタスク</h2>

        <div v-if="loading" class="flex justify-center items-center h-64">
            <div
                class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"
            ></div>
        </div>

        <div v-else class="space-y-8">
            <!-- 朝のタスク -->
            <div v-if="morningTodos.length > 0" class="timeline-section">
                <h3
                    class="flex items-center text-lg font-medium text-amber-600 mb-2"
                >
                    <svg
                        class="w-5 h-5 mr-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                        />
                    </svg>
                    朝
                </h3>
                <div class="ml-4 border-l-2 border-amber-200 pl-6 space-y-6">
                    <div
                        v-for="todo in morningTodos"
                        :key="todo.id"
                        class="relative flex items-start group"
                    >
                        <div class="absolute -left-10 mt-1">
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center"
                                :style="{
                                    backgroundColor: todo.category
                                        ? todo.category.color
                                        : '#d1d5db',
                                }"
                            >
                                <svg
                                    class="w-4 h-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between">
                                <span
                                    class="text-sm font-medium text-gray-500"
                                    >{{ formatTime(todo.due_time) }}</span
                                >
                                <div
                                    class="opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <button
                                        @click="editTask(todo)"
                                        class="text-gray-400 hover:text-gray-600"
                                    >
                                        <svg
                                            class="w-5 h-5"
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
                            <h4
                                :class="[
                                    'text-lg font-medium',
                                    todo.status === 'completed'
                                        ? 'line-through text-gray-400'
                                        : 'text-gray-800',
                                ]"
                            >
                                {{ todo.title }}
                            </h4>
                            <div
                                v-if="todo.category"
                                class="mt-1 flex items-center"
                            >
                                <span
                                    class="w-2 h-2 rounded-full mr-1"
                                    :style="{
                                        backgroundColor: todo.category.color,
                                    }"
                                ></span>
                                <span class="text-xs text-gray-500">{{
                                    todo.category.name
                                }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <button
                                @click="toggleTask(todo)"
                                :class="[
                                    'w-6 h-6 rounded-full border-2 flex items-center justify-center',
                                    todo.status === 'completed'
                                        ? 'bg-green-400 border-green-500'
                                        : 'bg-white border-gray-300',
                                ]"
                            >
                                <svg
                                    v-if="todo.status === 'completed'"
                                    class="w-4 h-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 昼のタスク -->
            <div v-if="afternoonTodos.length > 0" class="timeline-section">
                <h3
                    class="flex items-center text-lg font-medium text-blue-600 mb-2"
                >
                    <svg
                        class="w-5 h-5 mr-2"
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
                    昼
                </h3>
                <div class="ml-4 border-l-2 border-blue-200 pl-6 space-y-6">
                    <div
                        v-for="todo in afternoonTodos"
                        :key="todo.id"
                        class="relative flex items-start group"
                    >
                        <div class="absolute -left-10 mt-1">
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center"
                                :style="{
                                    backgroundColor: todo.category
                                        ? todo.category.color
                                        : '#d1d5db',
                                }"
                            >
                                <svg
                                    class="w-4 h-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between">
                                <span
                                    class="text-sm font-medium text-gray-500"
                                    >{{ formatTime(todo.due_time) }}</span
                                >
                                <div
                                    class="opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <button
                                        @click="editTask(todo)"
                                        class="text-gray-400 hover:text-gray-600"
                                    >
                                        <svg
                                            class="w-5 h-5"
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
                            <h4
                                :class="[
                                    'text-lg font-medium',
                                    todo.status === 'completed'
                                        ? 'line-through text-gray-400'
                                        : 'text-gray-800',
                                ]"
                            >
                                {{ todo.title }}
                            </h4>
                            <div
                                v-if="todo.category"
                                class="mt-1 flex items-center"
                            >
                                <span
                                    class="w-2 h-2 rounded-full mr-1"
                                    :style="{
                                        backgroundColor: todo.category.color,
                                    }"
                                ></span>
                                <span class="text-xs text-gray-500">{{
                                    todo.category.name
                                }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <button
                                @click="toggleTask(todo)"
                                :class="[
                                    'w-6 h-6 rounded-full border-2 flex items-center justify-center',
                                    todo.status === 'completed'
                                        ? 'bg-green-400 border-green-500'
                                        : 'bg-white border-gray-300',
                                ]"
                            >
                                <svg
                                    v-if="todo.status === 'completed'"
                                    class="w-4 h-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 夜のタスク -->
            <div v-if="eveningTodos.length > 0" class="timeline-section">
                <h3
                    class="flex items-center text-lg font-medium text-indigo-600 mb-2"
                >
                    <svg
                        class="w-5 h-5 mr-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                        />
                    </svg>
                    夜
                </h3>
                <div class="ml-4 border-l-2 border-indigo-200 pl-6 space-y-6">
                    <div
                        v-for="todo in eveningTodos"
                        :key="todo.id"
                        class="relative flex items-start group"
                    >
                        <div class="absolute -left-10 mt-1">
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center"
                                :style="{
                                    backgroundColor: todo.category
                                        ? todo.category.color
                                        : '#d1d5db',
                                }"
                            >
                                <svg
                                    class="w-4 h-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between">
                                <span
                                    class="text-sm font-medium text-gray-500"
                                    >{{ formatTime(todo.due_time) }}</span
                                >
                                <div
                                    class="opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <button
                                        @click="editTask(todo)"
                                        class="text-gray-400 hover:text-gray-600"
                                    >
                                        <svg
                                            class="w-5 h-5"
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
                            <h4
                                :class="[
                                    'text-lg font-medium',
                                    todo.status === 'completed'
                                        ? 'line-through text-gray-400'
                                        : 'text-gray-800',
                                ]"
                            >
                                {{ todo.title }}
                            </h4>
                            <div
                                v-if="todo.category"
                                class="mt-1 flex items-center"
                            >
                                <span
                                    class="w-2 h-2 rounded-full mr-1"
                                    :style="{
                                        backgroundColor: todo.category.color,
                                    }"
                                ></span>
                                <span class="text-xs text-gray-500">{{
                                    todo.category.name
                                }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <button
                                @click="toggleTask(todo)"
                                :class="[
                                    'w-6 h-6 rounded-full border-2 flex items-center justify-center',
                                    todo.status === 'completed'
                                        ? 'bg-green-400 border-green-500'
                                        : 'bg-white border-gray-300',
                                ]"
                            >
                                <svg
                                    v-if="todo.status === 'completed'"
                                    class="w-4 h-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 時間設定なしのタスク -->
            <div v-if="noTimeTodos.length > 0" class="timeline-section">
                <h3
                    class="flex items-center text-lg font-medium text-gray-600 mb-2"
                >
                    <svg
                        class="w-5 h-5 mr-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"
                        />
                    </svg>
                    時間なし
                </h3>
                <div class="ml-4 border-l-2 border-gray-200 pl-6 space-y-6">
                    <div
                        v-for="todo in noTimeTodos"
                        :key="todo.id"
                        class="relative flex items-start group"
                    >
                        <div class="absolute -left-10 mt-1">
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center"
                                :style="{
                                    backgroundColor: todo.category
                                        ? todo.category.color
                                        : '#d1d5db',
                                }"
                            >
                                <svg
                                    class="w-4 h-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500"
                                    >終日</span
                                >
                                <div
                                    class="opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <button
                                        @click="editTask(todo)"
                                        class="text-gray-400 hover:text-gray-600"
                                    >
                                        <svg
                                            class="w-5 h-5"
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
                            <h4
                                :class="[
                                    'text-lg font-medium',
                                    todo.status === 'completed'
                                        ? 'line-through text-gray-400'
                                        : 'text-gray-800',
                                ]"
                            >
                                {{ todo.title }}
                            </h4>
                            <div
                                v-if="todo.category"
                                class="mt-1 flex items-center"
                            >
                                <span
                                    class="w-2 h-2 rounded-full mr-1"
                                    :style="{
                                        backgroundColor: todo.category.color,
                                    }"
                                ></span>
                                <span class="text-xs text-gray-500">{{
                                    todo.category.name
                                }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <button
                                @click="toggleTask(todo)"
                                :class="[
                                    'w-6 h-6 rounded-full border-2 flex items-center justify-center',
                                    todo.status === 'completed'
                                        ? 'bg-green-400 border-green-500'
                                        : 'bg-white border-gray-300',
                                ]"
                            >
                                <svg
                                    v-if="todo.status === 'completed'"
                                    class="w-4 h-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- タスクがない場合 -->
            <div
                v-if="todos.length === 0"
                class="flex flex-col items-center justify-center py-12 text-center"
            >
                <svg
                    class="w-16 h-16 text-gray-300 mb-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
                <h3 class="text-xl font-medium text-gray-700 mb-1">
                    今日のタスクはありません
                </h3>
                <p class="text-gray-500">新しいタスクを追加してみましょう</p>
            </div>
        </div>
    </div>
</template>

<script>
import TodoApi from "../api/todo";

export default {
    name: "TimelineTodoView",

    data() {
        return {
            todos: [],
            loading: true,
        };
    },

    computed: {
        // 朝のタスク (12時前)
        morningTodos() {
            return this.todos
                .filter((todo) => {
                    const time = todo.due_time ? new Date(todo.due_time) : null;
                    return time && time.getHours() < 12;
                })
                .sort((a, b) => new Date(a.due_time) - new Date(b.due_time));
        },

        // 昼のタスク (12時から17時)
        afternoonTodos() {
            return this.todos
                .filter((todo) => {
                    const time = todo.due_time ? new Date(todo.due_time) : null;
                    return (
                        time && time.getHours() >= 12 && time.getHours() < 17
                    );
                })
                .sort((a, b) => new Date(a.due_time) - new Date(b.due_time));
        },

        // 夜のタスク (17時以降)
        eveningTodos() {
            return this.todos
                .filter((todo) => {
                    const time = todo.due_time ? new Date(todo.due_time) : null;
                    return time && time.getHours() >= 17;
                })
                .sort((a, b) => new Date(a.due_time) - new Date(b.due_time));
        },

        // 時間設定なしのタスク
        noTimeTodos() {
            return this.todos.filter((todo) => !todo.due_time);
        },
    },

    async mounted() {
        await this.loadTodos();
    },

    methods: {
        // タスクを読み込む
        async loadTodos() {
            try {
                this.loading = true;
                const response = await TodoApi.getTasks("today");

                // APIからのレスポンスが配列かどうか確認
                if (Array.isArray(response.data)) {
                    this.todos = response.data;
                } else {
                    this.todos = [];
                    console.error("API response is not an array:", response);
                }
            } catch (error) {
                console.error("Error loading todos:", error);
                this.todos = [];
            } finally {
                this.loading = false;
            }
        },

        // 時間をフォーマット
        formatTime(timeString) {
            if (!timeString) return "";
            const date = new Date(timeString);
            if (isNaN(date.getTime())) return "";

            return date.toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
            });
        },

        // タスク完了状態の切り替え
        async toggleTask(todo) {
            try {
                // タスクのステータスをトグル
                await TodoApi.toggleTask(todo.id);

                // ローカルの状態を更新 (楽観的UI更新)
                todo.status =
                    todo.status === "completed" ? "pending" : "completed";

                // タスクを再読み込み (バックエンド状態との同期を確保)
                await this.loadTodos();
            } catch (error) {
                console.error("Error toggling task status:", error);
            }
        },

        // タスク編集
        editTask(todo) {
            // グローバルな editTodo 関数が定義されていれば使用
            if (window.editTodo) {
                window.editTodo(todo.id);
            } else {
                console.warn("Global editTodo function not available");
            }
        },
    },
};
</script>

<style scoped>
.timeline-todo-view {
    animation: fadeIn 0.3s ease-in-out;
}

.timeline-section {
    transition: all 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
