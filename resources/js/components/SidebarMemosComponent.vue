<template>
    <div>
        <!-- Quick Input Component -->
        <quick-input-component @task-added="handleTaskAdded" />

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
