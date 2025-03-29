<template>
    <div>
        <div
            v-if="todos.length === 0"
            class="bg-white rounded-lg shadow-sm p-8 text-center"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-12 w-12 mx-auto text-gray-400 mb-3"
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
            <h3 class="text-lg font-medium text-gray-700 mb-2">
                タスクはありません
            </h3>
            <p class="text-sm text-gray-500 mb-4">
                新しいタスクを追加しましょう
            </p>
        </div>

        <!-- Task list -->
        <div v-else class="bg-white rounded-lg shadow-sm">
            <ul class="divide-y divide-gray-100">
                <task-item
                    v-for="todo in todos"
                    :key="todo.id"
                    :todo="todo"
                    :category="getCategoryById(todo.category_id)"
                    :current-user-id="currentUserId"
                    @toggle="$emit('toggle-task', todo)"
                    @edit="$emit('edit-task', todo)"
                    @delete="$emit('delete-task', todo)"
                />
            </ul>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from "vue";

import TaskItem from "./TaskItem.vue";

export default {
    name: "TodoList",
    components: {
        TaskItem,
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

    setup(props) {
        // Get current user ID from Laravel global variable
        const currentUserId = ref(null);

        /**
         * Get category by ID
         * @param {number} categoryId Category ID
         * @returns {Object|null} Category object
         */
        const getCategoryById = (categoryId) => {
            if (!categoryId) return null;
            return (
                props.categories.find((cat) => cat.id === categoryId) || null
            );
        };

        // Initialize
        onMounted(() => {
            // Get current user ID from Laravel global variable
            if (window.Laravel && window.Laravel.user) {
                currentUserId.value = window.Laravel.user.id;
            }
        });

        return {
            currentUserId,
            getCategoryById,
        };
    },
};
</script>
