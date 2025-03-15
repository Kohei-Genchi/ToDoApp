<template>
    <div>
        <!-- タスクがない場合のメッセージ -->
        <empty-state v-if="todos.length === 0" />

        <!-- タスク一覧 -->
        <div v-else class="bg-white rounded-lg shadow-sm">
            <ul class="divide-y divide-gray-100">
                <task-item
                    v-for="todo in todos"
                    :key="todo.id"
                    :todo="todo"
                    :category="getCategoryById(todo.category_id)"
                    @toggle="toggleTask"
                    @edit="editTask"
                    @delete="$emit('delete-task', todo)"
                />
            </ul>
        </div>
    </div>
</template>

<script>
import { computed } from "vue";
import EmptyState from "./EmptyState.vue";
import TaskItem from "./TaskItem.vue";

export default {
    name: "TodoList",
    components: {
        EmptyState,
        TaskItem
    },

    props: {
        todos: {
            type: Array,
            default: () => [],
        },
        categories: {
            type: Array,
            default: () => [],
        },
    },

    emits: ["toggle-task", "edit-task", "delete-task"],

    setup(props, { emit }) {
        /**
         * 完了済みタスク数
         */
        const completedCount = computed(
            () =>
                props.todos.filter((todo) => todo.status === "completed")
                    .length,
        );

        /**
         * 未完了タスク数
         */
        const pendingCount = computed(
            () =>
                props.todos.filter((todo) => todo.status !== "completed")
                    .length,
        );

        /**
         * カテゴリIDからカテゴリオブジェクトを取得
         * @param {number} categoryId カテゴリID
         * @returns {Object|null} カテゴリオブジェクト
         */
        const getCategoryById = (categoryId) => {
            if (!categoryId) return null;
            return (
                props.categories.find((cat) => cat.id === categoryId) || null
            );
        };

        /**
         * タスク完了状態切り替えハンドラ
         * @param {Object} todo タスクオブジェクト
         */
        const toggleTask = (todo) => {
            emit("toggle-task", todo);
        };

        /**
         * タスク編集ハンドラ
         * @param {Object} todo タスクオブジェクト
         */
        const editTask = (todo) => {
            emit("edit-task", todo);
        };

        return {
            completedCount,
            pendingCount,
            getCategoryById,
            toggleTask,
            editTask,
        };
    },
};
</script>
