<template>
    <div
        class="mb-2 py-2 px-3 text-sm rounded overflow-hidden transition-colors duration-200 hover:shadow-md text-left flex items-center"
        :class="taskStatusClasses"
    >
        <!-- Status selection -->
        <select
            v-model="taskStatus"
            @change="updateStatus"
            class="mr-2 text-xs rounded py-1 px-2 font-medium border shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
            :class="selectStatusClasses"
        >
            <option value="pending">待機</option>
            <option value="ongoing">進行</option>
            <option value="paused">中断</option>
            <option value="completed">完了</option>
        </select>

        <!-- Task title and time -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <div class="font-medium text-base truncate">
                {{ task.title }}
            </div>
            <div class="flex items-center mt-0.5">
                <div v-if="task.due_time" class="text-xs opacity-75 mr-2">
                    <span
                        class="bg-gray-100 px-1 py-0.5 rounded inline-flex items-center"
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
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        {{ formattedTime }}
                    </span>
                </div>
                <div v-if="task.category" class="text-xs">
                    <span class="px-1.5 py-0.5 rounded" :style="categoryStyle">
                        {{ task.category.name }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Edit button (owner only) -->
        <button
            v-if="isOwner"
            @click.stop="$emit('edit', task)"
            class="ml-1 text-gray-500 hover:text-blue-600 flex-shrink-0"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
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
</template>

<script>
import { ref, computed } from "vue";

export default {
    name: "TaskCell",
    props: {
        task: {
            type: Object,
            required: true,
        },
        isOwner: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["update-status", "edit"],

    setup(props, { emit }) {
        // Status colors
        const STATUS_COLORS = {
            pending: {
                bg: "bg-gray-100",
                text: "text-gray-700",
                select: "bg-gray-100 text-gray-700",
            },
            ongoing: {
                bg: "bg-blue-100",
                text: "text-blue-700",
                select: "bg-blue-100 text-blue-700",
            },
            paused: {
                bg: "bg-yellow-100",
                text: "text-yellow-700",
                select: "bg-yellow-100 text-yellow-700",
            },
            completed: {
                bg: "bg-green-100",
                text: "text-green-700",
                select: "bg-green-100 text-green-700",
            },
        };

        // Task status
        const taskStatus = ref(props.task.status || "pending");

        // Task status classes
        const taskStatusClasses = computed(() => {
            const statusStyle =
                STATUS_COLORS[props.task.status] || STATUS_COLORS["pending"];
            const baseClasses = `${statusStyle.bg} ${statusStyle.text} w-full`;

            // Special styles based on share type
            const specialClasses = props.task.isGloballyShared
                ? "border-2 border-green-300 "
                : props.task.ownerInfo
                  ? "border-dashed border "
                  : "";

            return `${baseClasses} ${specialClasses}`;
        });

        // Status select classes
        const selectStatusClasses = computed(() => {
            return (
                STATUS_COLORS[props.task.status] || STATUS_COLORS["pending"]
            ).select;
        });

        // Category style
        const categoryStyle = computed(() => {
            if (!props.task.category || !props.task.category.color) return {};
            return {
                backgroundColor: `${props.task.category.color}25`,
                color: props.task.category.color,
            };
        });

        // Formatted time
        const formattedTime = computed(() => {
            return formatTaskTime(props.task.due_time);
        });

        // Update status
        function updateStatus() {
            emit("update-status", props.task.id, taskStatus.value);
        }

        // Format task time
        function formatTaskTime(timeString) {
            try {
                if (timeString instanceof Date) {
                    return timeString.toLocaleTimeString("ja-JP", {
                        hour: "2-digit",
                        minute: "2-digit",
                    });
                }

                if (typeof timeString === "string") {
                    if (timeString.includes("T")) {
                        // ISO format
                        const date = new Date(timeString);
                        return date.toLocaleTimeString("ja-JP", {
                            hour: "2-digit",
                            minute: "2-digit",
                        });
                    } else if (timeString.includes(":")) {
                        // HH:MM format
                        return timeString.substr(0, 5);
                    }
                }

                return timeString;
            } catch (e) {
                console.error("Time format error:", e);
                return timeString;
            }
        }

        return {
            taskStatus,
            taskStatusClasses,
            selectStatusClasses,
            categoryStyle,
            formattedTime,
            updateStatus,
        };
    },
};
</script>
