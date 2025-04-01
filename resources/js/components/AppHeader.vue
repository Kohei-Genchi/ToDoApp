<!-- AppHeader.vue update -->
<template>
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-900">Todo App</h1>

                <div class="flex space-x-2">
                    <!-- タスク一覧ボタン -->
                    <button
                        @click="$emit('set-view', 'today')"
                        :class="[
                            'px-3 py-1 rounded-md text-sm font-medium',
                            currentView === 'today'
                                ? 'bg-blue-500 text-white'
                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                        ]"
                    >
                        今日
                    </button>

                    <!-- 共有タスクボタン -->
                    <button
                        @click="$emit('set-view', 'shared')"
                        :class="[
                            'px-3 py-1 rounded-md text-sm font-medium flex items-center',
                            currentView === 'shared' || currentView === 'kanban'
                                ? 'bg-green-600 text-green-50 font-bold shadow-sm'
                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                        ]"
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
                        共有タスク
                    </button>

                    <!-- 場所でタスク共有ボタン - 新規追加 -->
                    <button
                        @click="$emit('open-share-modal')"
                        class="px-3 py-1 rounded-md text-sm font-medium flex items-center bg-green-600 text-white hover:bg-green-700"
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
                        タスク共有
                    </button>

                    <!-- 共有カテゴリーボタン -->
                    <button
                        @click="$emit('set-view', 'categories-shared')"
                        :class="[
                            'px-3 py-1 rounded-md text-sm font-medium flex items-center',
                            currentView === 'categories-shared'
                                ? 'bg-green-600 text-green-50 font-bold shadow-sm'
                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                        ]"
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
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        共有カテゴリー
                    </button>

                    <!-- 新規タスクボタン -->
                    <button
                        @click="$emit('add-task')"
                        class="px-3 py-1 rounded-md text-sm font-medium text-white bg-blue-500 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        + 新しいタスク
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>

<script>
export default {
    name: "AppHeader",
    props: {
        currentView: {
            type: String,
            required: true,
        },
    },
    emits: ["set-view", "add-task", "open-share-modal"], // Add the new event
};
</script>
