<!-- TodoList.vue update - partial code for modifications -->
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

        <!-- Task selection toolbar - only visible when tasks are selected -->
        <div
            v-if="selectedTasks.length > 0"
            class="bg-blue-600 text-white px-4 py-2 rounded-md mb-2 flex justify-between items-center"
        >
            <div class="flex items-center">
                <span class="font-medium"
                    >{{ selectedTasks.length }}件のタスクを選択中</span
                >
                <button
                    @click="clearSelection"
                    class="ml-3 text-sm bg-blue-700 hover:bg-blue-800 px-2 py-1 rounded"
                >
                    選択解除
                </button>
            </div>

            <div class="flex items-center space-x-2">
                <button
                    @click="openShareSelectedTasksModal"
                    class="flex items-center bg-green-500 hover:bg-green-600 px-3 py-1 rounded text-sm font-medium"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 mr-1"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"
                        />
                    </svg>
                    共有
                </button>

                <button
                    @click="addSelectedToCategory"
                    class="bg-blue-700 hover:bg-blue-800 px-3 py-1 rounded text-sm font-medium"
                >
                    カテゴリー追加
                </button>
            </div>
        </div>

        <!-- Task list -->
        <div v-else-if="todos.length > 0" class="bg-white rounded-lg shadow-sm">
            <div
                class="px-4 py-2 border-b border-gray-100 flex justify-between items-center"
            >
                <h3 class="font-medium text-gray-700">タスク一覧</h3>
                <button
                    v-if="!selectionMode && todos.length > 1"
                    @click="enableSelectionMode"
                    class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded flex items-center"
                >
                    <svg
                        class="h-3 w-3 mr-1"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"
                        />
                        <path
                            fill-rule="evenodd"
                            d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    複数選択
                </button>
            </div>
            <ul class="divide-y divide-gray-100">
                <task-item
                    v-for="todo in todos"
                    :key="todo.id"
                    :todo="todo"
                    :category="getCategoryById(todo.category_id)"
                    :current-user-id="currentUserId"
                    :selection-mode="selectionMode"
                    :selected-task-ids="selectedTaskIds"
                    @toggle="$emit('toggle-task', todo)"
                    @edit="$emit('edit-task', todo)"
                    @delete="$emit('delete-task', todo)"
                    @toggle-selection="toggleTaskSelection"
                    @enable-selection="enableSelectionModeWithTask"
                />
            </ul>
        </div>

        <!-- Share Selected Tasks Modal -->
        <share-selected-tasks-modal
            v-if="showShareSelectedTasksModal"
            :tasks="selectedTasks"
            @close="showShareSelectedTasksModal = false"
            @shared="handleTasksShared"
        />
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import TaskItem from "./TaskItem.vue";
import ShareSelectedTasksModal from "./ShareSelectedTasksModal.vue";

export default {
    name: "TodoList",
    components: {
        TaskItem,
        ShareSelectedTasksModal,
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
        currentUserId: {
            type: Number,
            default: null,
        },
    },

    emits: ["toggle-task", "edit-task", "delete-task", "shared-tasks"],

    setup(props, { emit }) {
        // Get current user ID from Laravel global variable
        const currentUserId = ref(null);

        // Selection mode state
        const selectionMode = ref(false);
        const selectedTaskIds = ref([]);
        const showShareSelectedTasksModal = ref(false);

        // Computed properties
        const selectedTasks = computed(() => {
            return props.todos.filter((todo) =>
                selectedTaskIds.value.includes(todo.id),
            );
        });

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

        // Task selection methods
        const enableSelectionMode = () => {
            selectionMode.value = true;
            selectedTaskIds.value = [];
        };

        const enableSelectionModeWithTask = (task) => {
            selectionMode.value = true;
            selectedTaskIds.value = [task.id];
        };

        const toggleTaskSelection = (task) => {
            const index = selectedTaskIds.value.indexOf(task.id);
            if (index === -1) {
                selectedTaskIds.value.push(task.id);
            } else {
                selectedTaskIds.value.splice(index, 1);
            }

            // Exit selection mode if no tasks selected
            if (selectedTaskIds.value.length === 0) {
                selectionMode.value = false;
            }
        };

        const clearSelection = () => {
            selectedTaskIds.value = [];
            selectionMode.value = false;
        };

        // Share selected tasks methods
        const openShareSelectedTasksModal = () => {
            if (selectedTasks.value.length === 0) return;
            showShareSelectedTasksModal.value = true;
        };

        const handleTasksShared = (result) => {
            // Emit event to parent
            emit("shared-tasks", result);

            // Clear selection
            clearSelection();
        };

        // Add selected tasks to category
        const addSelectedToCategory = () => {
            if (selectedTasks.value.length === 0) return;
            // This would typically open a category selection modal
            // For now, we'll just console.log
            console.log(
                "Would add these tasks to category:",
                selectedTasks.value,
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
            // Selection mode
            selectionMode,
            selectedTaskIds,
            selectedTasks,
            enableSelectionMode,
            enableSelectionModeWithTask,
            toggleTaskSelection,
            clearSelection,
            // Share selected tasks
            showShareSelectedTasksModal,
            openShareSelectedTasksModal,
            handleTasksShared,
            // Other methods
            addSelectedToCategory,
        };
    },
};
</script>
