<!-- resources/js/components/TaskSelectionBar.vue -->
<template>
    <div
        v-if="selectedTasks.length > 0"
        class="fixed bottom-0 left-0 right-0 bg-blue-600 text-white p-3 flex justify-between items-center shadow-lg transform transition-transform"
        :class="{
            'translate-y-0': selectedTasks.length > 0,
            'translate-y-full': selectedTasks.length === 0,
        }"
    >
        <div class="flex items-center">
            <span class="font-medium"
                >{{ selectedTasks.length }}件のタスクを選択中</span
            >
            <button
                @click="$emit('clear-selection')"
                class="ml-3 text-sm bg-blue-700 hover:bg-blue-800 px-2 py-1 rounded"
            >
                選択解除
            </button>
        </div>

        <div class="flex items-center space-x-2">
            <button
                @click="$emit('share-tasks', selectedTasks)"
                class="flex items-center bg-green-500 hover:bg-green-600 px-3 py-1.5 rounded text-sm font-medium"
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
                @click="$emit('add-to-category', selectedTasks)"
                class="bg-blue-700 hover:bg-blue-800 px-3 py-1.5 rounded text-sm font-medium"
            >
                カテゴリー追加
            </button>

            <button
                @click="$emit('batch-edit', selectedTasks)"
                class="bg-blue-700 hover:bg-blue-800 px-3 py-1.5 rounded text-sm font-medium"
            >
                一括編集
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: "TaskSelectionBar",

    props: {
        selectedTasks: {
            type: Array,
            required: true,
        },
    },

    emits: ["clear-selection", "share-tasks", "add-to-category", "batch-edit"],
};
</script>
