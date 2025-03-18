<template>
    <li class="hover:bg-gray-50 transition-colors">
        <div class="p-3 sm:px-4 flex items-center">
            <!-- Checkbox -->
            <div class="mr-3 flex-shrink-0">
                <input
                    type="checkbox"
                    :checked="todo.status === 'completed'"
                    @change="$emit('toggle', todo)"
                    class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500"
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
                                ? 'line-through text-gray-500'
                                : 'text-gray-900'
                        "
                    >
                        {{ todo.title }}
                    </p>

                    <!-- Shared indicator -->
                    <span
                        v-if="isShared"
                        class="ml-2 p-0.5 rounded text-xs border flex items-center"
                        :class="{
                            'text-green-600 border-green-200 bg-green-50':
                                todo.user_id === myUserId,
                            'text-blue-600 border-blue-200 bg-blue-50':
                                todo.user_id !== myUserId,
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
                        <span>{{ sharedLabel }}</span>
                    </span>

                    <!-- Category label -->
                    <span
                        v-if="category"
                        class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium"
                        :style="{
                            backgroundColor: categoryColor,
                            color: category.color,
                        }"
                    >
                        {{ category.name }}
                    </span>

                    <!-- Recurrence icon -->
                    <span v-if="isRecurring" class="ml-2 text-xs text-gray-500">
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
                    v-if="formattedTime || isShared"
                    class="text-sm text-gray-500 mt-0.5 flex space-x-2"
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

                    <!-- Shared with names (limited to first 2) -->
                    <span
                        v-if="
                            isShared &&
                            sharedWith &&
                            sharedWith.length > 0 &&
                            todo.user_id === myUserId
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
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                        <span v-if="sharedWith.length <= 2">
                            {{ sharedWith.map((user) => user.name).join(", ") }}
                        </span>
                        <span v-else>
                            {{
                                sharedWith
                                    .slice(0, 2)
                                    .map((user) => user.name)
                                    .join(", ")
                            }}
                            +{{ sharedWith.length - 2 }}
                        </span>
                    </span>

                    <!-- Owner info when viewing a shared task -->
                    <span
                        v-if="
                            isShared && todo.user_id !== myUserId && todo.user
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
                <!-- Share button (if owned by current user) -->
                <button
                    v-if="todo.user_id === myUserId"
                    @click.stop="$emit('share', todo)"
                    class="text-gray-400 hover:text-blue-600 transition-colors"
                    title="共有"
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
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"
                        />
                    </svg>
                </button>

                <!-- Delete button -->
                <button
                    @click.stop="$emit('delete')"
                    class="text-gray-400 hover:text-red-600 transition-colors"
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
        myUserId: {
            type: Number,
            default: null,
        },
        sharedWith: {
            type: Array,
            default: () => [],
        },
    },

    emits: ["toggle", "edit", "delete", "share"],

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
         * Convert HEX color code to RGBA format
         */
        const categoryColor = computed(() => {
            if (!props.category?.color) return "rgba(155, 155, 155, 0.15)";

            const hex = props.category.color;
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, 0.15)`;
        });

        /**
         * Task edit handler
         */
        const handleEdit = () => {
            emit("edit", props.todo);
            console.log("Edit button clicked:", props.todo);
            // (emit) Send event to parent component (TodoList)
            // handleEdit -> edit event with current todo (task data) passed to parent
        };

        /**
         * Is this task shared?
         */
        const isShared = computed(() => {
            // Task is shared if:
            // 1. It has sharedWith property with users
            // 2. Or it has a user_id different from the current user (meaning it's shared with current user)
            return (
                (props.sharedWith && props.sharedWith.length > 0) ||
                (props.todo.user_id !== props.myUserId &&
                    props.todo.user_id !== undefined)
            );
        });

        /**
         * Get label for shared indicator
         */
        const sharedLabel = computed(() => {
            if (props.todo.user_id === props.myUserId) {
                return "共有中";
            } else {
                return "共有された";
            }
        });

        /**
         * Get tooltip text for shared indicator
         */
        const sharedTooltip = computed(() => {
            if (props.todo.user_id === props.myUserId) {
                return `このタスクは${props.sharedWith?.length || 0}人のユーザーと共有されています`;
            } else {
                return "このタスクは他のユーザーから共有されています";
            }
        });

        return {
            isRecurring,
            recurrenceLabel,
            formattedTime,
            categoryColor,
            handleEdit,
            isShared,
            sharedLabel,
            sharedTooltip,
        };
    },
};
</script>
