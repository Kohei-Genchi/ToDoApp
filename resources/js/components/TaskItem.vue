<template>
    <li
        class="cosmic-task-item hover:bg-gray-800/50 transition-all duration-300 group"
    >
        <div class="p-3 sm:px-4 flex items-center">
            <!-- Completion checkbox -->
            <div class="mr-3 flex-shrink-0">
                <input
                    type="checkbox"
                    :checked="todo.status === 'completed'"
                    @change="$emit('toggle', todo)"
                    class="h-5 w-5 text-orange-500 rounded focus:ring-orange-500 cosmic-checkbox"
                    @click.stop
                />
            </div>

            <!-- Task content -->
            <div
                class="flex-1 min-w-0"
                @click="handleItemClick"
                style="cursor: pointer"
            >
                <div class="flex items-center">
                    <p
                        class="font-medium"
                        :class="
                            todo.status === 'completed'
                                ? 'line-through text-gray-400'
                                : 'text-gray-100'
                        "
                    >
                        {{ todo.title }}
                    </p>

                    <!-- Category label -->
                    <span
                        v-if="category"
                        class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium backdrop-blur-sm shadow-sm"
                        :style="{
                            backgroundColor: `${category.color}30`,
                            color: category.color,
                            borderColor: `${category.color}50`,
                            borderWidth: '1px',
                        }"
                    >
                        {{ category.name }}
                    </span>

                    <!-- Recurrence icon -->
                    <span v-if="isRecurring" class="ml-2 text-xs text-gray-300">
                        <span class="inline-flex items-center">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3 w-3 mr-0.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                            {{ recurrenceLabel }}
                        </span>
                    </span>
                </div>

                <!-- Time display -->
                <div
                    v-if="formattedTime"
                    class="text-sm text-gray-400 mt-0.5 flex space-x-2"
                >
                    <span class="inline-flex items-center">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-3.5 w-3.5 mr-0.5"
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
                        {{ formattedTime }}
                    </span>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex-shrink-0 ml-3 flex space-x-1">
                <!-- Delete button -->
                <button
                    @click.stop="$emit('delete')"
                    class="text-gray-400 hover:text-red-400 transition-colors"
                    title="削除"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </li>
</template>

<script>
import { computed, ref } from "vue";

export default {
    name: "TaskItem",
    props: {
        todo: {
            type: Object,
            required: true,
        },
        category: {
            type: Object,
            default: null,
        },
        currentUserId: {
            type: Number,
            default: null,
        },
    },

    emits: ["toggle", "edit", "delete"],

    setup(props, { emit }) {
        /**
         * Is this a recurring task?
         */
        const isRecurring = computed(
            () =>
                props.todo.recurrence_type &&
                props.todo.recurrence_type !== "none",
        );

        // Handle clicking on the task item
        const handleItemClick = () => {
            // Edit the task
            emit("edit", props.todo);
        };

        /**
         * Get label for recurrence type
         */
        const recurrenceLabel = computed(() => {
            switch (props.todo.recurrence_type) {
                case "daily":
                    return "毎日";
                case "weekly":
                    return "毎週";
                case "monthly":
                    return "毎月";
                default:
                    return "";
            }
        });

        /**
         * Format time string
         */
        const formattedTime = computed(() => {
            if (!props.todo.due_time) return "";
            const date = new Date(props.todo.due_time);
            return date
                .toLocaleTimeString("en-US", {
                    hour: "2-digit",
                    minute: "2-digit",
                    hour12: false,
                })
                .replace(/^24/, "00");
        });

        /**
         * Task edit handler
         */
        const handleEdit = () => {
            emit("edit", props.todo);
        };

        return {
            isRecurring,
            recurrenceLabel,
            formattedTime,
            handleEdit,
            handleItemClick,
        };
    },
};
</script>
