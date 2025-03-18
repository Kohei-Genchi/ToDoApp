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
                    @toggle="toggleTask"
                    @edit="editTask"
                    @delete="$emit('delete-task', todo)"
                    @share="shareTask(todo)"
                />
            </ul>
        </div>

        <!-- Task Share Modal -->
        <task-share-modal
            v-if="showShareModal"
            :task="selectedShareTask"
            @close="showShareModal = false"
        />
    </div>
</template>

<script>
import { computed, ref, onMounted } from "vue";
import EmptyState from "./EmptyState.vue";
import TaskItem from "./TaskItem.vue";
import TaskShareModal from "./TaskShareModal.vue";

export default {
    name: "TodoList",
    components: {
        EmptyState,
        TaskItem,
        TaskShareModal,
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
        // Get current user ID from Laravel global variable
        const currentUserId = ref(null);

        // State for share modal
        const showShareModal = ref(false);
        const selectedShareTask = ref(null);

        /**
         * Number of completed tasks
         */
        const completedCount = computed(
            () =>
                props.todos.filter((todo) => todo.status === "completed")
                    .length,
        );

        /**
         * Number of pending tasks
         */
        const pendingCount = computed(
            () =>
                props.todos.filter((todo) => todo.status !== "completed")
                    .length,
        );

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

        /**
         * Task status toggle handler
         * @param {Object} todo Task object
         */
        const toggleTask = (todo) => {
            emit("toggle-task", todo);
        };

        /**
         * Task edit handler
         * @param {Object} todo Task object
         */
        const editTask = (todo) => {
            emit("edit-task", todo);
            console.log("TodoList received edit event:", todo);
        };

        /**
         * Task share handler
         * @param {Object} todo Task object
         */
        const shareTask = (todo) => {
            selectedShareTask.value = todo;
            showShareModal.value = true;
        };

        // Initialize
        onMounted(() => {
            // Get current user ID from Laravel global variable
            if (window.Laravel && window.Laravel.user) {
                currentUserId.value = window.Laravel.user.id;
            }
        });

        return {
            completedCount,
            pendingCount,
            getCategoryById,
            toggleTask,
            editTask,
            shareTask,
            currentUserId,
            showShareModal,
            selectedShareTask,
        };
    },
};
</script>
