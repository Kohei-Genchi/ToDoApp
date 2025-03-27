<template>
    <div
        class="kanban-card bg-white rounded shadow-sm p-3 cursor-pointer hover:shadow-md transition-shadow duration-200"
        draggable="true"
        @dragstart="$emit('dragstart', $event)"
        @click="$emit('click')"
    >
        <!-- Card header with category indicator -->
        <div v-if="task.category" class="mb-2 flex items-center">
            <div
                class="h-2 w-2 rounded-full mr-1.5"
                :style="{ backgroundColor: task.category.color }"
            ></div>
            <span class="text-xs text-gray-600">{{ task.category.name }}</span>
        </div>

        <!-- Task title -->
        <h4
            class="font-medium text-gray-800 mb-1.5"
            :class="{
                'line-through text-gray-400': task.status === 'completed',
            }"
        >
            {{ task.title }}
        </h4>

        <!-- Due date and assignee info -->
        <div class="flex justify-between items-end mt-2">
            <!-- Due date -->
            <div
                v-if="task.due_date"
                class="flex items-center text-xs text-gray-600"
            >
                <svg
                    class="h-3.5 w-3.5 mr-0.5"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
                <span :class="{ 'text-red-600 font-medium': isOverdue }">{{
                    formattedDueDate
                }}</span>

                <!-- Time if available -->
                <span v-if="task.due_time" class="ml-1">{{
                    formattedTime
                }}</span>
            </div>

            <!-- Assignee -->
            <div class="flex items-center">
                <!-- If shared task, show the owner -->
                <div v-if="isSharedTask" class="flex items-center">
                    <span
                        class="inline-block bg-gray-100 rounded-full h-6 w-6 overflow-hidden flex items-center justify-center text-xs text-gray-600 mr-1"
                    >
                        {{ userInitials }}
                    </span>
                    <span
                        v-if="showUsername"
                        class="text-xs text-gray-600 truncate max-w-[100px]"
                    >
                        {{ task.user?.name }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, inject } from "vue";

export default {
    name: "KanbanCard",

    props: {
        task: {
            type: Object,
            required: true,
        },
        showUsername: {
            type: Boolean,
            default: true,
        },
    },

    emits: ["click", "dragstart"],

    setup(props) {
        // Get current user ID for shared task detection
        const currentUserId = computed(() => {
            return window.Laravel?.user?.id || null;
        });

        // Check if task is from another user (shared)
        const isSharedTask = computed(() => {
            return props.task.user_id !== currentUserId.value;
        });

        // Format due date
        const formattedDueDate = computed(() => {
            if (!props.task.due_date) return "";

            const date = new Date(props.task.due_date);
            return date.toLocaleDateString(undefined, {
                month: "short",
                day: "numeric",
            });
        });

        // Format time if available
        const formattedTime = computed(() => {
            if (!props.task.due_time) return "";

            try {
                let timeStr = props.task.due_time;

                // Handle both time formats (HH:MM:SS and ISO datetime)
                if (timeStr.includes("T")) {
                    const date = new Date(timeStr);
                    return date.toLocaleTimeString(undefined, {
                        hour: "2-digit",
                        minute: "2-digit",
                        hour12: false,
                    });
                } else {
                    // Handle HH:MM:SS format
                    return timeStr.substring(0, 5);
                }
            } catch (error) {
                console.error("Error formatting time:", error);
                return "";
            }
        });

        // Check if task is overdue
        const isOverdue = computed(() => {
            if (!props.task.due_date || props.task.status === "completed")
                return false;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const dueDate = new Date(props.task.due_date);
            dueDate.setHours(0, 0, 0, 0);

            return dueDate < today;
        });

        // Get user initials for avatar
        const userInitials = computed(() => {
            if (!props.task.user?.name) return "?";

            const nameParts = props.task.user.name.split(" ");
            if (nameParts.length >= 2) {
                return `${nameParts[0][0]}${nameParts[1][0]}`.toUpperCase();
            }

            return nameParts[0][0].toUpperCase();
        });

        return {
            isSharedTask,
            formattedDueDate,
            formattedTime,
            isOverdue,
            userInitials,
        };
    },
};
</script>

<style scoped>
.kanban-card {
    border-left: 3px solid transparent;
}

.kanban-card:hover {
    border-left-color: #3b82f6; /* Blue highlight on hover */
}
</style>
