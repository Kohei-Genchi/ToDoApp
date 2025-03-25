<template>
    <div>
        <!-- Quick Input Component -->
        <quick-input-component
            @task-added="handleTaskAdded"
            @multiple-tasks-added="handleMultipleTasksAdded"
        />

        <!-- Memo List Component -->
        <memo-list-component :refreshTrigger="refreshCounter" />
    </div>
</template>

<script>
import QuickInputComponent from "./QuickInputComponent.vue";
import MemoListComponent from "./MemoListComponent.vue";
import axios from "axios";

export default {
    name: "SidebarMemosComponent",
    components: {
        QuickInputComponent,
        MemoListComponent,
    },
    data() {
        return {
            refreshCounter: 0,
            csrfToken:
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "",
        };
    },
    methods: {
        // Handle single task added
        handleTaskAdded() {
            // Increment the counter to trigger a refresh in MemoListComponent
            this.refreshCounter++;
        },

        // Handle multiple tasks added from voice input
        async handleMultipleTasksAdded(tasks) {
            if (!tasks || tasks.length === 0) return;

            try {
                // Create each task sequentially
                for (const taskTitle of tasks) {
                    await axios.post(
                        "/api/memos",
                        { title: taskTitle },
                        {
                            headers: {
                                "X-CSRF-TOKEN": this.csrfToken,
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-Requested-With": "XMLHttpRequest",
                            },
                        },
                    );
                }

                // Refresh the memo list after all tasks are created
                this.refreshCounter++;

                // Show notification if available
                this.showNotification(
                    `${tasks.length}個のタスクを作成しました`,
                );
            } catch (error) {
                console.error("Error creating multiple tasks:", error);
                this.showNotification(
                    "タスクの作成中にエラーが発生しました",
                    "error",
                );
            }
        },

        // Simple notification helper
        showNotification(message, type = "success") {
            if (window.showNotification) {
                window.showNotification(message, type);
            } else {
                // Fallback if global notification function not available
                console.log(`[${type}] ${message}`);
            }
        },
    },
};
</script>
