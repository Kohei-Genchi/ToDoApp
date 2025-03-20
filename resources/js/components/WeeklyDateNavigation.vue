<template>
    <div class="mb-2">
        <!-- Month and Year with navigation -->
        <div class="flex justify-between items-center mb-2">
            <button
                @click="previousWeek"
                class="text-gray-600 hover:text-gray-900 p-1"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
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

            <h2 class="text-lg font-medium text-gray-900">
                {{ currentMonth }} {{ currentYear }}
            </h2>

            <button
                @click="nextWeek"
                class="text-gray-600 hover:text-gray-900 p-1"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
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

        <!-- Weekday header with dates -->
        <div class="grid grid-cols-7 gap-1 text-center">
            <div
                v-for="(day, index) in weekDays"
                :key="index"
                class="flex flex-col items-center"
            >
                <div
                    class="text-sm font-medium"
                    :class="{
                        'text-red-600': index === 0,
                        'text-blue-600': index === 6,
                    }"
                >
                    {{ day.dayName }}
                </div>
                <div
                    @click="selectDate(day.date)"
                    class="w-8 h-8 flex items-center justify-center text-sm rounded-full cursor-pointer"
                    :class="[
                        isCurrentDate(day.date)
                            ? 'bg-blue-600 text-white'
                            : 'hover:bg-gray-100',
                        day.isCurrentMonth ? 'font-medium' : 'text-gray-400',
                    ]"
                >
                    {{ day.dayNumber }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "WeeklyDateNavigation",

    props: {
        /**
         * Current selected date (YYYY-MM-DD format)
         */
        currentDate: {
            type: String,
            required: true,
        },
    },

    emits: ["date-selected"],

    data() {
        return {
            weekDays: [],
        };
    },

    computed: {
        /**
         * Get the month name for display
         */
        currentMonth() {
            const date = new Date(this.currentDate);
            // Format month name in Japanese
            return date.toLocaleDateString("ja-JP", { month: "long" });
        },

        /**
         * Get the year for display
         */
        currentYear() {
            const date = new Date(this.currentDate);
            return date.getFullYear();
        },
    },

    watch: {
        /**
         * Watch for changes to currentDate prop and update week days
         */
        currentDate: {
            immediate: true,
            handler() {
                this.generateWeekDays();
            },
        },
    },

    methods: {
        /**
         * Generate the array of days for the current week
         */
        generateWeekDays() {
            const currentDate = new Date(this.currentDate);
            const day = currentDate.getDay(); // 0 = Sunday, 6 = Saturday

            // Calculate the first day of the week (Sunday)
            const firstDayOfWeek = new Date(currentDate);
            firstDayOfWeek.setDate(currentDate.getDate() - day);

            const days = [];
            const currentMonth = currentDate.getMonth();

            // Generate 7 days starting from Sunday
            for (let i = 0; i < 7; i++) {
                const date = new Date(firstDayOfWeek);
                date.setDate(firstDayOfWeek.getDate() + i);

                days.push({
                    date: this.formatDate(date),
                    dayName: this.getDayName(i),
                    dayNumber: date.getDate(),
                    isCurrentMonth: date.getMonth() === currentMonth,
                });
            }

            this.weekDays = days;
        },

        /**
         * FIXED: Format a date as YYYY-MM-DD using local date
         */
        formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");
            return `${year}-${month}-${day}`;
        },

        /**
         * Get day name in Japanese based on index
         */
        getDayName(index) {
            const dayNames = ["日", "月", "火", "水", "木", "金", "土"];
            return dayNames[index];
        },

        /**
         * Check if a date is the current date
         */
        isCurrentDate(dateString) {
            return dateString === this.currentDate;
        },

        /**
         * Go to previous week
         */
        previousWeek() {
            const date = new Date(this.currentDate);
            date.setDate(date.getDate() - 7);
            this.$emit("date-selected", this.formatDate(date));
        },

        /**
         * Go to next week
         */
        nextWeek() {
            const date = new Date(this.currentDate);
            date.setDate(date.getDate() + 7);
            this.$emit("date-selected", this.formatDate(date));
        },

        /**
         * Select a specific date
         */
        selectDate(dateString) {
            this.$emit("date-selected", dateString);
        },

        /**
         * FIXED: Go to today using local date format
         */
        goToToday() {
            const today = new Date();
            this.$emit("date-selected", this.formatDate(today));
        },
    },
};
</script>
