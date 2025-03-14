<!-- resources/js/components/SidebarMemosComponent.vue -->
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

export default {
    name: "SidebarMemosComponent",
    components: {
        QuickInputComponent,
        MemoListComponent,
    },
    data() {
        return {
            refreshCounter: 0,
        };
    },
    methods: {
        handleTaskAdded() {
            // Increment the counter to trigger a refresh in MemoListComponent
            this.refreshCounter++;
        },
    },
};
</script>
