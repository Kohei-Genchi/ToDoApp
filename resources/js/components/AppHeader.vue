<template>
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
            <!-- Single row layout with app title and all buttons -->
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-900">Todo App</h1>

                <!-- All buttons in a single row -->
                <div class="flex space-x-2">
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

                    <!-- Shared tasks button - directs to Kanban board -->
                    <a
                        href="/tasks/kanban"
                        :class="[
                            'px-3 py-1 rounded-md text-sm font-medium',
                            currentView === 'shared'
                                ? 'bg-green-600 text-white'
                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                        ]"
                    >
                        <span class="flex items-center">
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
                        </span>
                    </a>

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
        /**
         * Current view
         */
        currentView: {
            type: String,
            required: true,
        },
    },

    emits: ["set-view", "add-task"],

    computed: {
        /**
         * Check if user has subscription
         */
        hasSubscription() {
            return (
                window.Laravel &&
                window.Laravel.user &&
                window.Laravel.user.has_subscription
            );
        },
    },

    methods: {
        /**
         * Show subscription required message
         */
        showSubscriptionMessage(feature) {
            alert(
                `${feature}機能を使用するにはサブスクリプションが必要です。「設定 > サブスクリプション」からご登録ください。`,
            );
        },
    },
};
</script>
