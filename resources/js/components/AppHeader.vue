<template>
    <header class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <!-- Main Header Section -->
            <div class="flex justify-between items-center mb-3">
                <!-- App Title and Info -->
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">TodoList</h1>
                </div>

                <!-- View Selector -->
                <div class="flex space-x-3">
                    <button
                        @click="$emit('set-view', 'today')"
                        :class="[
                            'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                            currentView === 'today'
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                        ]"
                    >
                        Today
                    </button>
                    <button
                        @click="$emit('show-calendar')"
                        :class="[
                            'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                            currentView === 'calendar'
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                        ]"
                    >
                        Calendar
                    </button>
                </div>
            </div>

            <!-- Action Section -->
            <div class="flex justify-center mt-3">
                <button
                    @click="$emit('add-task')"
                    class="w-full max-w-md py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                >
                    <span class="flex items-center justify-center">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Add New Task
                    </span>
                </button>
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

    emits: ["set-view", "show-calendar", "add-task"],
};
</script>
