<template>
    <div class="p-2 flex justify-between items-center border-b border-gray-200">
        <div class="flex items-center space-x-2">
            <h2 class="text-lg font-medium text-gray-900">共有タスク</h2>
            <span class="text-sm text-gray-500">{{ formattedDate }}</span>
            <!-- User sharing button -->
            <button
                @click="$emit('open-global-share')"
                class="ml-2 px-2 py-1 text-sm bg-green-600 text-white hover:bg-green-700 rounded flex items-center"
                title="ユーザーを追加して全てのタスクを共有"
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
                ユーザー共有
            </button>
        </div>
        <div class="flex space-x-2">
            <button
                @click="$emit('previous-day')"
                class="p-1 rounded hover:bg-gray-100"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 text-gray-600"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
            <button
                @click="$emit('go-to-today')"
                class="px-2 py-1 text-sm text-blue-600 hover:bg-blue-50 rounded"
            >
                今日
            </button>
            <button
                @click="$emit('next-day')"
                class="p-1 rounded hover:bg-gray-100"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 text-gray-600"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: "HeaderNavigation",
    props: {
        formattedDate: {
            type: String,
            required: true,
        },
    },
    emits: ["previous-day", "next-day", "go-to-today", "open-global-share"],
};
</script>
