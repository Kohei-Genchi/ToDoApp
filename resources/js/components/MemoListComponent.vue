<!-- resources/js/components/MemoListComponent.vue -->
<template>
    <div>
        <div class="flex items-center justify-between mb-2">
            <div class="text-xs text-gray-400 uppercase tracking-wider">
                メモ一覧
            </div>
            <div class="text-xs bg-gray-600 px-1.5 py-0.5 rounded-full">
                {{ memos.length }}
            </div>
        </div>

        <div
            class="memo-list-container sidebar-memo-list space-y-1 max-h-96 overflow-y-auto pr-1 custom-scrollbar"
        >
            <div v-if="isLoading" class="text-center py-2">
                <svg
                    class="animate-spin h-5 w-5 text-white mx-auto"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
            </div>

            <div
                v-else-if="memos.length === 0"
                class="text-xs text-gray-500 text-center py-2"
            >
                メモはありません
            </div>

            <div
                v-for="memo in memos"
                :key="memo.id"
                class="group bg-gray-700 hover:bg-gray-600 rounded py-1.5 px-2 cursor-pointer transition-colors"
                @click="editMemo(memo)"
                :style="{
                    'border-left': `3px solid ${memo.category ? memo.category.color : '#6B7280'}`,
                }"
            >
                <div class="flex items-center justify-between">
                    <div class="text-sm truncate pr-1">{{ memo.title }}</div>
                    <div
                        class="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                    >
                        <button
                            type="button"
                            @click.stop="deleteMemo(memo.id)"
                            class="text-gray-400 hover:text-gray-200"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <div
                    v-if="memo.category"
                    class="text-xs text-gray-400 mt-0.5 flex items-center"
                >
                    <span
                        class="w-2 h-2 rounded-full mr-1"
                        :style="{ 'background-color': memo.category.color }"
                    ></span>
                    {{ memo.category.name }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "MemoListComponent",
    props: {
        refreshTrigger: {
            type: Number,
            default: 0,
        },
    },
    data() {
        return {
            memos: [],
            isLoading: true,
            csrfToken:
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "",
        };
    },
    watch: {
        // Watch for changes to the refreshTrigger prop to reload memos
        refreshTrigger() {
            this.loadMemos();
        },
    },
    mounted() {
        this.loadMemos();
    },
    methods: {
        async loadMemos() {
            this.isLoading = true;
            try {
                // Get memos using the dedicated memos API endpoint
                const response = await axios.get("/api/memos", {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                });

                if (response.data) {
                    // Use the memos data directly from the API response
                    this.memos = response.data;
                }
            } catch (error) {
                console.error("Error loading memos:", error);
            } finally {
                this.isLoading = false;
            }
        },

        editMemo(memo) {
            // Use the global editTodo function that's already defined in app.js
            if (window.editTodo) {
                window.editTodo(memo.id, memo);
            }
        },

        async deleteMemo(memoId) {
            if (!confirm("このメモを削除してもよろしいですか？")) {
                return;
            }

            try {
                await axios.delete(`/api/todos/${memoId}`, {
                    headers: {
                        "X-CSRF-TOKEN": this.csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    },
                });

                // Reload memos after deletion
                this.loadMemos();
            } catch (error) {
                console.error("Error deleting memo:", error);
                alert("メモの削除に失敗しました");
            }
        },
    },
};
</script>
