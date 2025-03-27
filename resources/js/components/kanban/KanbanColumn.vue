<template>
    <div
        class="kanban-column flex-shrink-0 w-80 flex flex-col rounded-lg shadow-sm"
        :class="column.color"
        @dragover.prevent
        @drop="onDrop"
    >
        <!-- Column header -->
        <div
            class="p-3 font-medium text-gray-800 border-b border-gray-200 flex justify-between items-center"
        >
            <div class="flex items-center">
                <h3>{{ column.name }}</h3>
                <span
                    class="ml-2 bg-white text-gray-600 text-xs px-2 py-0.5 rounded-full"
                >
                    {{ tasks.length }}
                </span>
            </div>
            <button
                @click="$emit('add-task')"
                class="text-gray-500 hover:text-gray-700"
                title="Add task to this column"
            >
                <svg
                    class="h-5 w-5"
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
            </button>
        </div>

        <!-- Task list -->
        <div class="p-2 flex-1 overflow-y-auto">
            <!-- Empty state -->
            <div
                v-if="tasks.length === 0"
                class="flex flex-col items-center justify-center text-center p-4 h-32"
            >
                <svg
                    class="h-8 w-8 text-gray-400 mb-2"
                    xmlns="http://www.w3.org/2000/svg"
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
                <p class="text-sm text-gray-500">No tasks in this column</p>
                <button
                    @click="$emit('add-task')"
                    class="mt-2 text-xs text-blue-600 hover:text-blue-800 font-medium"
                >
                    Add a task
                </button>
            </div>

            <!-- Tasks -->
            <div v-else class="space-y-2">
                <kanban-card
                    v-for="task in tasks"
                    :key="task.id"
                    :task="task"
                    @click="$emit('edit-task', task)"
                    @dragstart="onDragStart($event, task.id)"
                />
            </div>
        </div>
    </div>
</template>

<script>
import { defineComponent } from "vue";
import KanbanCard from "./KanbanCard.vue";

export default defineComponent({
    name: "KanbanColumn",

    components: {
        KanbanCard,
    },

    props: {
        column: {
            type: Object,
            required: true,
        },
        tasks: {
            type: Array,
            default: () => [],
        },
    },

    emits: ["add-task", "edit-task", "drop"],

    setup(props, { emit }) {
        // Handle drag start
        const onDragStart = (event, taskId) => {
            event.dataTransfer.effectAllowed = "move";
            event.dataTransfer.setData("text/plain", taskId);
        };

        // Handle drop
        const onDrop = (event) => {
            const taskId = Number(event.dataTransfer.getData("text/plain"));
            emit("drop", taskId, props.column.id);
        };

        return {
            onDragStart,
            onDrop,
        };
    },
});
</script>

<style scoped>
.kanban-column {
    height: 100%;
    min-height: 200px;
    max-height: calc(100vh - 200px);
}

/* Custom scrollbar for task list */
.kanban-column > div:last-child::-webkit-scrollbar {
    width: 4px;
}

.kanban-column > div:last-child::-webkit-scrollbar-track {
    background: transparent;
}

.kanban-column > div:last-child::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.kanban-column > div:last-child::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
