<template>
    <div>
        <!-- Empty state when no tasks -->
        <empty-state v-if="todos.length === 0" />

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
import EmptyState from "./EmptyState.vue";
import TaskItem from "./TaskItem.vue";

export default {
    name: "TodoList",
    components: {
        EmptyState,
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
