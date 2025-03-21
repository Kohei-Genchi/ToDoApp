<template>
    <div>
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <!-- カレンダーヘッダー - 曜日表示 -->
            <div
                class="grid grid-cols-7 text-center text-xs text-gray-700 bg-gray-100"
            >
                <div class="py-1 text-red-600 font-medium">日</div>
                <div class="py-1 font-medium">月</div>
                <div class="py-1 font-medium">火</div>
                <div class="py-1 font-medium">水</div>
                <div class="py-1 font-medium">木</div>
                <div class="py-1 font-medium">金</div>
                <div class="py-1 text-blue-600 font-medium">土</div>
            </div>

            <!-- カレンダーグリッド -->
            <div class="grid grid-cols-7 border-t border-l border-gray-200">
                <div
                    v-for="(day, index) in calendarDays"
                    :key="index"
                    @click="selectDate(day.date)"
                    :class="[
                        'h-20 p-1 border-r border-b border-gray-200 relative',
                        day.isCurrentMonth ? 'bg-white' : 'bg-gray-50',
                        day.isToday ? 'bg-yellow-50' : '',
                        day.isSelected ? 'bg-blue-50' : '',
                    ]"
                >
                    <!-- 日付表示 -->
                    <div class="flex justify-between items-start">
                        <div
                            :class="[
                                'text-xs',
                                !day.isCurrentMonth ? 'text-gray-400' : '',
                                day.isToday
                                    ? 'font-bold bg-yellow-100 rounded-full w-4 h-4 flex items-center justify-center'
                                    : '',
                                day.date.split('-')[2] === '01'
                                    ? 'font-bold'
                                    : '',
                            ]"
                        >
                            {{ parseInt(day.date.split("-")[2]) }}
                        </div>

                        <!-- タスク数バッジ -->
                        <div
                            v-if="day.todos.length > 0"
                            class="text-xs bg-blue-100 text-blue-800 px-1 rounded-full"
                        >
                            {{ day.todos.length }}
                        </div>
                    </div>

                    <!-- その日のタスク（最大2件表示） -->
                    <div class="mt-1 space-y-0.5">
                        <div
                            v-for="todo in day.todos.slice(0, 2)"
                            :key="todo.id"
                            :class="[
                                'flex items-center rounded text-xs hover:bg-gray-100 truncate',
                                todo.status === 'completed'
                                    ? 'text-gray-400 line-through'
                                    : '',
                            ]"
                            :style="{
                                borderLeft:
                                    '2px solid ' +
                                    (todo.category
                                        ? todo.category.color
                                        : '#ddd'),
                            }"
                            @click.stop="editTask(todo)"
                        >
                            <span class="truncate pl-0.5 text-xs">
                                {{ todo.title }}
                            </span>
                        </div>

                        <!-- 「もっと見る」インジケーター -->
                        <div
                            v-if="day.todos.length > 2"
                            class="text-xs text-blue-500 cursor-pointer hover:underline"
                            @click.stop="viewAllTasks(day.date)"
                        >
                            +{{ day.todos.length - 2 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, ref, watch } from "vue";

export default {
    name: "TodoCalendar",

    props: {
        /**
         * 現在選択されている日付（YYYY-MM-DD形式）
         */
        currentDate: {
            type: String,
            required: true,
        },

        /**
         * 表示するタスク一覧
         */
        todos: {
            type: Array,
            default: () => [],
        },
    },

    emits: ["date-selected", "edit-task", "view-all-tasks", "add-task"],

    setup(props, { emit }) {
        /**
         * 選択された日付
         */
        const selectedDate = ref(props.currentDate);

        /**
         * 日付をYYYY-MM-DD形式に一貫してフォーマット
         * @param {string|Date} dateString 日付文字列またはDateオブジェクト
         * @returns {string} YYYY-MM-DD形式の日付文字列
         */
        function formatDateToLocalYMD(dateString) {
            if (!dateString) return "";

            // すでにYYYY-MM-DD形式の場合はそのまま返す
            if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return dateString;

            try {
                const date = new Date(dateString);
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
            } catch (e) {
                return "";
            }
        }

        /**
         * カレンダーの日付データを生成
         */
        const calendarDays = computed(() => {
            const days = [];

            // 現在の日付から年と月を取得
            const date = new Date(props.currentDate);
            const year = date.getFullYear();
            const month = date.getMonth();

            // 月の最初の日
            const firstDayOfMonth = new Date(year, month, 1);

            // 月の最初の日を含む週の最初の日（日曜日）
            const startDay = new Date(firstDayOfMonth);
            startDay.setDate(
                firstDayOfMonth.getDate() - firstDayOfMonth.getDay(),
            );

            // 今日の日付（ハイライト表示用）
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // 6週間分の日付を生成
            for (let week = 0; week < 6; week++) {
                for (let weekday = 0; weekday < 7; weekday++) {
                    const currentDay = new Date(startDay);
                    currentDay.setDate(startDay.getDate() + week * 7 + weekday);

                    // 日付をYYYY-MM-DD形式にフォーマット
                    const dateString = formatDateToLocalYMD(currentDay);

                    // この日のタスクを取得
                    const todosForDay = getTodosForDate(dateString);

                    days.push({
                        date: dateString,
                        isCurrentMonth: currentDay.getMonth() === month,
                        isToday: currentDay.getTime() === today.getTime(),
                        isSelected: dateString === selectedDate.value,
                        todos: todosForDay,
                    });
                }
            }

            return days;
        });

        /**
         * props.currentDateの変更を監視
         */
        watch(
            () => props.currentDate,
            (newDate) => {
                selectedDate.value = newDate;
            },
        );

        /**
         * 日付を選択
         * @param {string} date YYYY-MM-DD形式の日付
         */
        function selectDate(date) {
            selectedDate.value = date;
            emit("date-selected", date);
        }

        /**
         * 特定の日付のすべてのタスクを表示
         * @param {string} date YYYY-MM-DD形式の日付
         */
        function viewAllTasks(date) {
            emit("date-selected", date);
        }

        /**
         * 特定の日付のタスクを取得
         * @param {string} date YYYY-MM-DD形式の日付
         * @returns {Array} タスクの配列
         */
        function getTodosForDate(date) {
            return props.todos.filter((todo) => {
                // タスクの日付をローカルのYYYY-MM-DD形式にフォーマット
                const todoDate = formatDateToLocalYMD(todo.due_date);
                return todoDate === date;
            });
        }

        /**
         * タスクを編集
         * @param {Object} todo タスクオブジェクト
         */
        function editTask(todo) {
            emit("edit-task", todo);
        }

        return {
            calendarDays,
            selectDate,
            editTask,
            viewAllTasks,
        };
    },
};
</script>
<style scoped>
/* カレンダーセルの高さを縮小 */
.calendar-day {
    height: 16px;
    min-height: 16px;
}

/* カレンダーグリッドの余白削減 */
.grid-gap-0 {
    gap: 0;
}

/* 日付数字のフォントサイズ縮小 */
.date-text {
    font-size: 0.75rem;
}

/* タスクアイテムの高さ縮小 */
.task-item {
    padding: 0.125rem 0.25rem;
    font-size: 0.7rem;
}
</style>
