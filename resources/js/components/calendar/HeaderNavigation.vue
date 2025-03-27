<!-- resources/js/components/calendar/HeaderNavigation.vue -->
<template>
    <div class="flex justify-between items-center mb-4 px-4 py-2 bg-white">
        <button
            @click="$emit('previous-day')"
            class="p-1 rounded-full hover:bg-gray-100 focus:outline-none"
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

        <div class="flex flex-col items-center">
            <h2 class="text-lg font-medium text-gray-900">
                {{ formattedDate }}
            </h2>
            <button
                @click="$emit('go-to-today')"
                class="text-xs text-blue-600 hover:text-blue-800"
            >
                今日
            </button>
        </div>

        <button
            @click="$emit('next-day')"
            class="p-1 rounded-full hover:bg-gray-100 focus:outline-none"
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
    emits: ["previous-day", "next-day", "go-to-today"],
};
</script>
