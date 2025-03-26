// Updated TaskItem.vue without individual task sharing UI

<template>
    <li
        class="cosmic-task-item hover:bg-gray-800/50 transition-all duration-300"
    >
        <div class="p-3 sm:px-4 flex items-center">
            <!-- Checkbox -->
            <div class="mr-3 flex-shrink-0">
                <input
                    type="checkbox"
                    :checked="todo.status === 'completed'"
                    @change="$emit('toggle', todo)"
                    class="h-5 w-5 text-orange-500 rounded focus:ring-orange-500 cosmic-checkbox"
                />
            </div>

            <!-- Task content -->
            <div
                class="flex-1 min-w-0"
                @click="handleEdit"
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

                    <!-- Category based shared status indicator -->
                    <span
                        v-if="isSharedViaCategory"
                        class="ml-2 p-0.5 rounded text-xs border flex items-center"
                        :class="{
                            'text-orange-400 border-orange-500/30 bg-orange-900/20':
                                todo.user_id === currentUserId,
                            'text-blue-400 border-blue-500/30 bg-blue-900/20':
                                todo.user_id !== currentUserId,
                        }"
                        :title="sharedTooltip"
                    >
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
                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"
                            />
                        </svg>
                        <span>{{ sharedViaCategory ? "共有中" : "" }}</span>
                    </span>

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
                    v-if="formattedTime || isSharedViaCategory"
                    class="text-sm text-gray-400 mt-0.5 flex space-x-2"
                >
                    <span v-if="formattedTime" class="inline-flex items-center">
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

                    <!-- Owner info when viewing a task from a shared category -->
                    <span
                        v-if="
                            isSharedViaCategory &&
                            todo.user_id !== currentUserId &&
                            todo.user
                        "
                        class="inline-flex items-center"
                    >
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
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                        {{ todo.user.name }}
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

        /**
         * Check if task is from a shared category
         */
        const isSharedViaCategory = computed(() => {
            // A task is considered shared via category if:
            // 1. It has a user_id different from the current user (shared with current user)
            // 2. Or it belongs to the current user and has a category that might be shared
            return (
                props.todo.user_id !== props.currentUserId ||
                props.category != null
            );
        });

        /**
         * Check if the category of this task is shared
         */
        const sharedViaCategory = computed(() => {
            return props.category != null;
        });

        /**
         * Get tooltip text for shared indicator
         */
        const sharedTooltip = computed(() => {
            if (props.todo.user_id === props.currentUserId) {
                return `このタスクはカテゴリー「${props.category?.name || ""}」を通して共有されています`;
            } else {
                return "このタスクは共有カテゴリーから共有されています";
            }
        });

        return {
            isRecurring,
            recurrenceLabel,
            formattedTime,
            handleEdit,
            isSharedViaCategory,
            sharedViaCategory,
            sharedTooltip,
        };
    },
};
</script>

<style scoped>
.cosmic-task-item {
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(8px);
    border-left: 3px solid #ff9933;
    margin-bottom: 4px;
    border-radius: 0.375rem;
}

.cosmic-task-item::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255, 153, 51, 0.05), transparent);
    pointer-events: none;
}

.cosmic-checkbox {
    border: 2px solid #ff9933;
    transition: all 0.2s ease;
}

.cosmic-checkbox:checked {
    background-color: #ff9933;
    border-color: #ff9933;
}
</style>
