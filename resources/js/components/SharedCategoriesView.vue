<template>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-medium text-gray-900">
                共有されたカテゴリー
            </h2>
            <div class="text-sm text-gray-500">
                {{ sharedCategories.length }} カテゴリー
            </div>
        </div>

        <!-- 読み込み中表示 -->
        <div v-if="isLoading" class="flex justify-center py-8">
            <svg
                class="animate-spin h-8 w-8 text-blue-500"
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

        <!-- カテゴリーがない場合 -->
        <div
            v-else-if="sharedCategories.length === 0"
            class="py-8 text-center text-gray-500"
        >
            共有されているカテゴリーはありません
        </div>

        <!-- カテゴリー一覧 -->
        <div v-else class="space-y-4">
            <div
                v-for="category in sharedCategories"
                :key="category.id"
                class="border rounded-lg p-4 hover:bg-gray-50 transition"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div
                            class="w-4 h-4 rounded-full mr-3"
                            :style="{ backgroundColor: category.color }"
                        ></div>
                        <div>
                            <h3 class="font-medium">{{ category.name }}</h3>
                            <p class="text-sm text-gray-500">
                                所有者: {{ category.user.name }}
                                <span
                                    class="ml-2 px-2 py-0.5 bg-gray-100 rounded-full text-xs"
                                >
                                    {{
                                        category.pivot.permission === "edit"
                                            ? "編集可能"
                                            : "閲覧のみ"
                                    }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <button
                        @click="viewCategoryTasks(category)"
                        class="text-sm text-blue-500 hover:text-blue-700"
                    >
                        タスク一覧
                    </button>
                </div>

                <!-- このカテゴリーのタスク（展開時のみ表示） -->
                <div v-if="expandedCategory === category.id" class="mt-4 pl-7">
                    <div
                        v-if="isLoadingTasks"
                        class="py-2 text-center text-sm text-gray-500"
                    >
                        タスクを読み込み中...
                    </div>
                    <div
                        v-else-if="categoryTasks.length === 0"
                        class="py-2 text-center text-sm text-gray-500"
                    >
                        このカテゴリーにはタスクがありません
                    </div>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="task in categoryTasks"
                            :key="task.id"
                            class="flex items-center p-2 bg-white border rounded-md"
                            :class="{
                                'line-through text-gray-400':
                                    task.status === 'completed',
                            }"
                        >
                            <input
                                type="checkbox"
                                :checked="task.status === 'completed'"
                                @change="toggleTaskStatus(task)"
                                :disabled="
                                    task.pivot &&
                                    task.pivot.permission === 'view'
                                "
                                class="mr-3 h-4 w-4"
                            />
                            <span class="flex-1">{{ task.title }}</span>
                            <div
                                v-if="task.due_date"
                                class="text-xs text-gray-500"
                            >
                                {{ formatDate(task.due_date) }}
                                <span v-if="task.due_time">{{
                                    formatTime(task.due_time)
                                }}</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import axios from "axios";

export default {
    name: "SharedCategoriesView",

    setup() {
        const sharedCategories = ref([]);
        const isLoading = ref(true);
        const expandedCategory = ref(null);
        const categoryTasks = ref([]);
        const isLoadingTasks = ref(false);

        // カテゴリー一覧の取得
        const loadSharedCategories = async () => {
            try {
                isLoading.value = true;
                const response = await axios.get("/api/shared-categories", {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });
                sharedCategories.value = response.data;
            } catch (error) {
                console.error("Error loading shared categories:", error);
            } finally {
                isLoading.value = false;
            }
        };

        // 特定のカテゴリーに属するタスクの取得
        const loadCategoryTasks = async (categoryId) => {
            try {
                isLoadingTasks.value = true;
                const response = await axios.get("/api/todos", {
                    params: {
                        category_id: categoryId,
                        view: "category",
                    },
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });
                categoryTasks.value = response.data;
            } catch (error) {
                console.error("Error loading category tasks:", error);
                categoryTasks.value = [];
            } finally {
                isLoadingTasks.value = false;
            }
        };

        // カテゴリーのタスク一覧を表示
        const viewCategoryTasks = async (category) => {
            if (expandedCategory.value === category.id) {
                // 既に展開されている場合は閉じる
                expandedCategory.value = null;
                categoryTasks.value = [];
            } else {
                // 展開してタスクを読み込む
                expandedCategory.value = category.id;
                await loadCategoryTasks(category.id);
            }
        };

        // タスクの完了状態を切り替え
        const toggleTaskStatus = async (task) => {
            // 閲覧権限しかない場合は操作不可
            if (task.pivot && task.pivot.permission === "view") {
                return;
            }

            try {
                const newStatus =
                    task.status === "completed" ? "pending" : "completed";

                // 楽観的UI更新
                task.status = newStatus;

                // API更新
                await axios.put(
                    `/api/todos/${task.id}`,
                    {
                        status: newStatus,
                    },
                    {
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );
            } catch (error) {
                console.error("Error toggling task status:", error);
                // エラー時は元に戻す
                task.status =
                    task.status === "completed" ? "pending" : "completed";
                alert("タスク状態の更新に失敗しました");
            }
        };

        // 日付のフォーマット
        const formatDate = (dateString) => {
            if (!dateString) return "";
            const date = new Date(dateString);
            return date.toLocaleDateString("ja-JP", {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
        };

        // 時間のフォーマット
        const formatTime = (timeString) => {
            if (!timeString) return "";

            // 時間文字列の形式によって処理を分ける
            if (timeString.includes("T")) {
                // ISO形式の場合
                const date = new Date(timeString);
                return date.toLocaleTimeString("ja-JP", {
                    hour: "2-digit",
                    minute: "2-digit",
                });
            } else {
                // HH:MM:SS形式の場合
                return timeString.substring(0, 5);
            }
        };

        // 初期ロード
        onMounted(() => {
            loadSharedCategories();
        });

        return {
            sharedCategories,
            isLoading,
            expandedCategory,
            categoryTasks,
            isLoadingTasks,
            viewCategoryTasks,
            toggleTaskStatus,
            formatDate,
            formatTime,
        };
    },
};
</script>
