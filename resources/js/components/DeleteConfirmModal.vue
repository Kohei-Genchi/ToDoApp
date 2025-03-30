<template>
    <div class="fixed inset-0 flex items-center justify-center z-50">
        <!-- 背景オーバーレイ -->
        <div
            class="absolute inset-0 bg-black bg-opacity-30"
            @click="cancel"
        ></div>

        <!-- モーダルコンテンツ -->
        <div
            class="bg-white rounded-lg shadow-md w-full max-w-md relative z-10 p-6"
        >
            <h3 class="text-lg font-medium mb-4">タスクの削除</h3>

            <div>
                <!-- 警告メッセージ -->
                <div class="flex items-center mb-4 text-red-600">
                    <svg
                        class="h-5 w-5 mr-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    <p class="text-gray-700 font-medium">
                        「{{
                            todoTitle || "このタスク"
                        }}」を削除します。この操作は元に戻せません。
                    </p>
                </div>

                <!-- 繰り返しタスクの場合の追加警告 -->
                <div
                    v-if="isRecurring"
                    class="mt-2 p-2 bg-yellow-50 border border-yellow-100 rounded-md text-sm text-yellow-700"
                >
                    <p>
                        これは繰り返しタスクです。すべての関連するタスクも削除されます。
                    </p>
                </div>
            </div>

            <!-- アクションボタン -->
            <div class="flex justify-end space-x-3 mt-6">
                <button
                    type="button"
                    @click="cancel"
                    class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200"
                >
                    キャンセル
                </button>
                <button
                    type="button"
                    @click="confirm"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                >
                    削除
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "DeleteConfirmModal",

    props: {
        /**
         * 削除するタスクのタイトル
         */
        todoTitle: {
            type: String,
            default: "このタスク",
        },

        /**
         * 繰り返しタスクかどうか
         */
        isRecurring: {
            type: Boolean,
            default: false,
        },
    },

    emits: ["confirm", "cancel"],

    setup(props, { emit }) {
        /**
         * すべての繰り返しタスクを削除するかどうか
         */
        const deleteAllRecurring = false;

        /**
         * 削除を確認
         * 親コンポーネントに確認イベントを発行
         */
        function confirm() {
            emit("confirm", true, deleteAllRecurring);
        }

        /**
         * 削除をキャンセル
         * 親コンポーネントにキャンセルイベントを発行
         */
        function cancel() {
            emit("cancel");
            // キャンセルを明確に示すために確認イベントもfalseで発行
            emit("confirm", false);
        }

        return {
            deleteAllRecurring,
            confirm,
            cancel,
        };
    },
};
</script>
