<template>
    <div class="w-full overflow-y-auto max-h-[70vh]">
        <!-- 時間ごとの行 -->
        <div
            v-for="hour in fullHours"
            :key="'row-' + hour"
            :data-hour="hour"
            :class="[
                'flex border-b border-gray-200 min-h-[60px]',
                isCurrentHour(hour) ? 'bg-blue-50 relative' : '',
            ]"
        >
            <!-- 時間セル (固定幅) -->
            <div
                class="w-16 px-1 py-1 border-r border-gray-200 text-left flex-shrink-0"
                :class="[
                    isCurrentHour(hour)
                        ? 'bg-blue-100 font-bold text-blue-700'
                        : 'bg-gray-50 text-gray-500',
                ]"
            >
                <div
                    class="text-xs"
                    :class="{ 'text-blue-700': isCurrentHour(hour) }"
                >
                    {{ hour }}:00
                </div>
            </div>

            <!-- 現在時刻のインジケーターライン -->
            <div
                v-if="isCurrentHour(hour)"
                class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"
            ></div>

            <!-- ユーザーごとのセル -->
            <div class="flex flex-1">
                <div
                    v-for="user in sharedUsers"
                    :key="'cell-' + hour + '-' + user.id"
                    class="flex-1 min-w-[250px] border-r border-gray-200 relative min-h-12 p-0.5 overflow-hidden border-l border-gray-200"
                >
                    <!-- この時間とユーザーのタスク -->
                    <task-cell
                        v-for="task in getTasksForHourAndUser(hour, user.id)"
                        :key="'task-' + task.id"
                        :task="task"
                        :is-owner="isCurrentUserOwner(task)"
                        @update-status="onUpdateTaskStatus"
                        @edit="onEditTask"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import TaskCell from "./TaskCell.vue";

export default {
    name: "CalendarBody",
    components: {
        TaskCell,
    },
    props: {
        fullHours: {
            type: Array,
            required: true,
        },
        sharedUsers: {
            type: Array,
            required: true,
        },
        currentDate: {
            type: String,
            required: true,
        },
        sharedTasks: {
            type: Array,
            required: true,
        },
        currentUserId: {
            type: Number,
            default: 0,
        },
    },
    emits: ["edit-task", "update-task-status", "scroll-to-current-time"],

    setup(props, { emit }) {
        // 現在時刻のインジケーター
        let timeUpdateInterval = null;

        // =============== メソッド ===============
        /**
         * 現在時刻かどうかをチェック
         */
        const isCurrentHour = (hour) => {
            const now = new Date();
            return now.getHours() === hour;
        };

        /**
         * 特定の時間帯とユーザーに対応するタスクを取得
         */
        const getTasksForHourAndUser = (hour, userId) => {
            if (
                !userId ||
                !props.sharedTasks ||
                props.sharedTasks.length === 0
            ) {
                return [];
            }

            // ユーザーIDを一貫した形式に変換
            const columnUserId = parseInt(userId, 10);
            if (isNaN(columnUserId)) {
                console.warn("無効なユーザーID:", userId);
                return [];
            }

            // このユーザーとこの時間帯に対応するタスクをフィルタリング
            return props.sharedTasks.filter((task) => {
                try {
                    // タスクの所有者IDをチェック
                    const taskOwnerId = task.user_id
                        ? parseInt(task.user_id, 10)
                        : null;
                    if (taskOwnerId !== columnUserId) return false;

                    // 日付が一致するかチェック
                    const taskDate = formatDateForComparison(task.due_date);
                    if (taskDate !== props.currentDate) return false;

                    // 時間が指定されており、現在の時間に一致するかチェック
                    if (!task.due_time) return false;
                    const taskHour = extractHour(task.due_time);
                    return taskHour === hour;
                } catch (error) {
                    console.error("タスク処理エラー:", error, task);
                    return false;
                }
            });
        };

        /**
         * タスクの所有者かどうかをチェック
         */
        const isCurrentUserOwner = (task) => {
            if (!task || !props.currentUserId) return false;
            const taskUserId = parseInt(task.user_id, 10);
            const currentId = parseInt(props.currentUserId, 10);
            return taskUserId === currentId;
        };

        /**
         * 時間文字列から時間部分を抽出
         */
        const extractHour = (timeString) => {
            try {
                if (!timeString) return null;

                if (timeString instanceof Date) {
                    return timeString.getHours();
                }

                if (typeof timeString === "string") {
                    if (timeString.includes("T")) {
                        // ISO形式: "2025-03-19T09:00:00.000000Z"
                        const date = new Date(timeString);
                        return date.getHours();
                    } else if (timeString.includes(":")) {
                        // HH:MM または HH:MM:SS 形式
                        return parseInt(timeString.split(":")[0], 10);
                    }
                }

                // 最終手段 - 数値として解析
                const num = parseInt(timeString, 10);
                if (!isNaN(num) && num >= 0 && num < 24) {
                    return num;
                }

                console.warn(`時間の解析に失敗: ${timeString}`);
                return null;
            } catch (e) {
                console.error("時間抽出エラー:", e, "対象時間:", timeString);
                return null;
            }
        };

        /**
         * 日付の比較用にフォーマット
         */
        const formatDateForComparison = (dateString) => {
            if (!dateString) return "";

            try {
                // すでにYYYY-MM-DD形式の場合はそのまま返す
                if (
                    typeof dateString === "string" &&
                    /^\d{4}-\d{2}-\d{2}$/.test(dateString)
                ) {
                    return dateString;
                }

                // 日付文字列をローカル日付形式に変換
                const date = new Date(dateString);
                if (isNaN(date.getTime())) {
                    console.error("無効な日付:", dateString);
                    return "";
                }

                // YYYY-MM-DD形式にフォーマット
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, "0");
                const day = String(date.getDate()).padStart(2, "0");

                return `${year}-${month}-${day}`;
            } catch (e) {
                console.error(
                    "日付フォーマットエラー:",
                    e,
                    "対象日付:",
                    dateString,
                );
                return "";
            }
        };

        /**
         * 現在時刻のインジケーターを追加
         */
        const addCurrentTimeIndicator = () => {
            const calendarContainer = document.querySelector(
                ".w-full.overflow-y-auto",
            );
            if (!calendarContainer) return;

            // 既存のインジケーターを削除
            const existingIndicator = document.getElementById(
                "current-time-indicator",
            );
            if (existingIndicator) {
                existingIndicator.remove();
            }

            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();

            // 現在の時間行を探す
            const hourRows = calendarContainer.querySelectorAll("[data-hour]");
            let currentRow = null;

            for (const row of hourRows) {
                const hourAttr = row.getAttribute("data-hour");
                if (hourAttr && parseInt(hourAttr, 10) === currentHour) {
                    currentRow = row;
                    break;
                }
            }

            if (currentRow) {
                // インジケーターを作成
                const indicator = document.createElement("div");
                indicator.id = "current-time-indicator";
                indicator.className =
                    "absolute w-full h-0.5 bg-red-500 z-30 shadow-md transform -translate-y-1/2";

                // 分数に基づいて位置を計算
                const rowHeight = currentRow.clientHeight;
                const minutePercentage = currentMinute / 60;
                const topOffset =
                    currentRow.offsetTop + rowHeight * minutePercentage;

                indicator.style.top = `${topOffset}px`;
                indicator.style.left = "0";
                indicator.style.right = "0";

                // 時間ラベルを追加
                const timeLabel = document.createElement("div");
                timeLabel.className =
                    "absolute left-0 -translate-y-1/2 bg-red-500 text-white text-xs px-1 py-0.5 rounded-r shadow";
                timeLabel.textContent = `${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}`;
                indicator.appendChild(timeLabel);

                // カレンダーコンテナに追加
                calendarContainer.appendChild(indicator);
            }
        };

        /**
         * タスク編集
         */
        const onEditTask = (task) => {
            emit("edit-task", task);
        };

        /**
         * タスクステータス更新
         */
        const onUpdateTaskStatus = (taskId, newStatus) => {
            emit("update-task-status", taskId, newStatus);
        };

        // =============== ライフサイクルフック ===============
        // コンポーネントマウント時に現在時刻インジケーターをセットアップ
        onMounted(() => {
            addCurrentTimeIndicator();

            // 1分ごとにインジケーターを更新
            timeUpdateInterval = setInterval(() => {
                addCurrentTimeIndicator();
            }, 60000);
        });

        // インジケーター更新インターバルのクリーンアップ
        onBeforeUnmount(() => {
            if (timeUpdateInterval) {
                clearInterval(timeUpdateInterval);
            }
        });

        return {
            isCurrentHour,
            getTasksForHourAndUser,
            isCurrentUserOwner,
            onEditTask,
            onUpdateTaskStatus,
        };
    },
};
</script>
