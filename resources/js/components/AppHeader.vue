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
                    <button
                        @click="$emit('show-calendar')"
                        :class="[
                            'px-3 py-1 rounded-md text-sm font-medium',
                            currentView === 'calendar'
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                        ]"
                    >
                        カレンダー
                    </button>
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
         * 現在のビュー
         */
        currentView: {
            type: String,
            required: true,
        },
    },

    emits: ["set-view", "show-calendar", "add-task"],
};
</script>
