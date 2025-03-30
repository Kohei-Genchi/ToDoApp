// resources/js/components/UnifiedCategoriesView.vue

<template>
    <div class="max-w-7xl mx-auto p-4">
        <!-- Page header -->
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-medium text-gray-800">
                共有カテゴリー管理
            </h2>
            <a
                href="{{ route('todos.index') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-500 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
            >
                タスク一覧へ戻る
            </a>
        </div>

        <!-- Loading indicator -->
        <div v-if="isLoading" class="flex justify-center py-8">
            <div
                class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"
            ></div>
        </div>

        <!-- Error message -->
        <div
            v-else-if="errorMessage"
            class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
        >
            <strong class="font-bold">エラー!</strong>
            <span class="block sm:inline"> {{ errorMessage }}</span>
        </div>

        <!-- Main content -->
        <div v-else class="space-y-8">
            <!-- Category management section -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium">カテゴリー管理</h2>
                    <button
                        v-if="!showCategoryInput"
                        @click="showCategoryInput = true"
                        class="px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700"
                    >
                        + 新しいカテゴリー
                    </button>
                </div>

                <!-- New category form -->
                <div
                    v-if="showCategoryInput"
                    class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200"
                >
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        新しいカテゴリー
                    </h3>
                    <div class="flex items-center gap-2 mb-3">
                        <input
                            type="color"
                            v-model="newCategory.color"
                            class="h-10 w-10 rounded-full border-0 overflow-hidden cursor-pointer p-0"
                        />
                        <input
                            type="text"
                            v-model="newCategory.name"
                            placeholder="カテゴリー名"
                            class="flex-1 border rounded-md p-2 text-sm"
                        />
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button
                            @click="showCategoryInput = false"
                            class="px-3 py-1.5 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300"
                        >
                            キャンセル
                        </button>
                        <button
                            @click="createCategory"
                            :disabled="!newCategory.name.trim() || isSubmitting"
                            :class="[
                                'px-3 py-1.5 text-white text-sm font-medium rounded-md',
                                !newCategory.name.trim() || isSubmitting
                                    ? 'bg-blue-400 cursor-not-allowed'
                                    : 'bg-blue-600 hover:bg-blue-700',
                            ]"
                        >
                            {{
                                isSubmitting ? "保存中..." : "カテゴリーを追加"
                            }}
                        </button>
                    </div>
                </div>

                <!-- Categories list -->
                <div class="mt-4">
                    <p
                        v-if="categories.length === 0"
                        class="text-gray-500 text-center py-4"
                    >
                        カテゴリーはありません
                    </p>

                    <ul v-else class="divide-y divide-gray-100">
                        <li
                            v-for="category in categories"
                            :key="category.id"
                            class="py-3"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span
                                        class="w-4 h-4 rounded-full mr-3"
                                        :style="{
                                            backgroundColor: category.color,
                                        }"
                                    ></span>
                                    <span class="font-medium">{{
                                        category.name
                                    }}</span>
                                </div>
                                <div class="flex space-x-2">
                                    <button
                                        @click="openShareModal(category)"
                                        class="flex items-center px-2 py-1 text-sm text-white bg-blue-500 hover:bg-blue-600 rounded"
                                    >
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
                                        共有
                                    </button>
                                    <button
                                        @click="openEditModal(category)"
                                        class="px-2 py-1 text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 rounded"
                                    >
                                        編集
                                    </button>
                                    <button
                                        @click="
                                            confirmDeleteCategory(category.id)
                                        "
                                        class="px-2 py-1 text-sm text-red-600 bg-red-50 hover:bg-red-100 rounded"
                                    >
                                        削除
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Shared Categories section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Categories shared with me -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-medium text-gray-700 mb-3">
                        自分と共有されているカテゴリー
                    </h3>

                    <p v-if="sharedWithMe.length === 0" class="text-gray-500">
                        他のユーザーからの共有カテゴリーはありません。
                    </p>

                    <ul v-else class="space-y-2">
                        <li
                            v-for="category in sharedWithMe"
                            :key="category.id"
                            class="p-3 bg-gray-50 rounded-md border border-gray-200 flex justify-between items-center"
                        >
                            <div class="flex items-center">
                                <span
                                    class="w-3 h-3 rounded-full mr-2"
                                    :style="{ backgroundColor: category.color }"
                                ></span>
                                <span class="font-medium">{{
                                    category.name
                                }}</span>
                                <span
                                    class="ml-2 text-xs text-gray-500"
                                    v-if="category.user"
                                >
                                    {{ category.user.name }} から
                                </span>
                            </div>
                            <span
                                class="text-xs px-2 py-1 rounded-full"
                                :class="[
                                    category.pivot &&
                                    category.pivot.permission === 'edit'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-blue-100 text-blue-800',
                                ]"
                            >
                                {{
                                    category.pivot &&
                                    category.pivot.permission === "edit"
                                        ? "編集可能"
                                        : "閲覧のみ"
                                }}
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- My categories shared with others -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-medium text-gray-700 mb-3">
                        自分が共有しているカテゴリー
                    </h3>

                    <p
                        v-if="mySharedCategories.length === 0"
                        class="text-gray-500"
                    >
                        まだカテゴリーを共有していません。カテゴリーページから共有設定ができます。
                    </p>

                    <ul v-else class="space-y-2">
                        <li
                            v-for="category in mySharedCategories"
                            :key="category.id"
                            class="p-3 bg-gray-50 rounded-md border border-gray-200"
                        >
                            <div class="flex items-center mb-2">
                                <span
                                    class="w-3 h-3 rounded-full mr-2"
                                    :style="{ backgroundColor: category.color }"
                                ></span>
                                <span class="font-medium">{{
                                    category.name
                                }}</span>
                            </div>

                            <div class="pl-5">
                                <p class="text-xs text-gray-500 mb-1">
                                    共有先:
                                </p>
                                <div
                                    v-for="user in category.sharedWith"
                                    :key="user.id"
                                    class="flex justify-between items-center py-1"
                                >
                                    <span class="text-sm">{{ user.name }}</span>
                                    <span
                                        class="text-xs px-2 py-0.5 rounded-full"
                                        :class="[
                                            user.pivot &&
                                            user.pivot.permission === 'edit'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-blue-100 text-blue-800',
                                        ]"
                                    >
                                        {{
                                            user.pivot &&
                                            user.pivot.permission === "edit"
                                                ? "編集可能"
                                                : "閲覧のみ"
                                        }}
                                    </span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Share Requests section -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-medium text-gray-700 mb-3">共有リクエスト</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Outgoing requests -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-600 mb-2">
                            送信中のリクエスト
                        </h4>

                        <p
                            v-if="outgoingRequests.length === 0"
                            class="text-gray-500 text-sm"
                        >
                            送信中のリクエストはありません。
                        </p>

                        <ul v-else class="space-y-2">
                            <li
                                v-for="request in outgoingRequests"
                                :key="request.id"
                                class="p-2 bg-gray-50 rounded border border-gray-200 text-sm"
                            >
                                <div class="flex justify-between">
                                    <span>{{ request.recipient_email }}</span>
                                    <span class="text-xs text-gray-500">
                                        {{ request.created_at }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-gray-600">
                                    {{ request.item_name }} (
                                    {{
                                        request.permission === "edit"
                                            ? "編集可能"
                                            : "閲覧のみ"
                                    }}
                                    )
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Incoming requests -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-600 mb-2">
                            受信中のリクエスト
                        </h4>

                        <p
                            v-if="incomingRequests.length === 0"
                            class="text-gray-500 text-sm"
                        >
                            受信中のリクエストはありません。
                        </p>

                        <ul v-else class="space-y-2">
                            <li
                                v-for="request in incomingRequests"
                                :key="request.id"
                                class="p-2 bg-gray-50 rounded border border-gray-200 text-sm"
                            >
                                <div class="flex justify-between">
                                    <span>{{ request.requester_name }}</span>
                                    <span class="text-xs text-gray-500">
                                        {{ request.created_at }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-gray-600">
                                    {{ request.item_name }} (
                                    {{
                                        request.permission === "edit"
                                            ? "編集可能"
                                            : "閲覧のみ"
                                    }}
                                    )
                                </div>
                                <div class="mt-2 flex space-x-2">
                                    <button
                                        @click="
                                            approveShareRequest(request.token)
                                        "
                                        class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded hover:bg-green-200"
                                    >
                                        承認
                                    </button>
                                    <button
                                        @click="
                                            rejectShareRequest(request.token)
                                        "
                                        class="text-xs px-2 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200"
                                    >
                                        拒否
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Category Modal -->
        <div
            v-if="showEditModal"
            class="fixed inset-0 flex items-center justify-center z-50"
        >
            <div
                class="absolute inset-0 bg-black bg-opacity-30"
                @click="showEditModal = false"
            ></div>
            <div
                class="bg-white rounded-lg shadow-md w-full max-w-md relative z-10 p-6"
            >
                <h3 class="text-lg font-medium mb-4">カテゴリーの編集</h3>
                <div class="space-y-4">
                    <div>
                        <label
                            for="edit-name"
                            class="block text-sm font-medium text-gray-700"
                        >
                            カテゴリー名
                        </label>
                        <input
                            type="text"
                            id="edit-name"
                            v-model="editingCategory.name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        />
                    </div>
                    <div>
                        <label
                            for="edit-color"
                            class="block text-sm font-medium text-gray-700"
                        >
                            カラー
                        </label>
                        <div class="mt-1 flex items-center">
                            <input
                                type="color"
                                id="edit-color"
                                v-model="editingCategory.color"
                                class="h-8 w-12 border border-gray-300 rounded"
                            />
                            <span class="ml-2 text-sm text-gray-500"
                                >カテゴリーの色を選択</span
                            >
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button
                            @click="showEditModal = false"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300"
                        >
                            キャンセル
                        </button>
                        <button
                            @click="updateCategory"
                            :disabled="
                                !editingCategory.name.trim() || isSubmitting
                            "
                            :class="[
                                'px-4 py-2 text-white rounded',
                                !editingCategory.name.trim() || isSubmitting
                                    ? 'bg-blue-400 cursor-not-allowed'
                                    : 'bg-blue-500 hover:bg-blue-600',
                            ]"
                        >
                            {{ isSubmitting ? "更新中..." : "更新" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Share Category Modal -->
        <div
            v-if="showShareModal"
            class="fixed inset-0 flex items-center justify-center z-50"
        >
            <div
                class="absolute inset-0 bg-black bg-opacity-30"
                @click="showShareModal = false"
            ></div>
            <div
                class="bg-white rounded-lg shadow-md w-full max-w-md relative z-10 p-6"
            >
                <h3 class="text-lg font-medium mb-4">カテゴリーを共有</h3>

                <div class="flex items-center mb-4">
                    <span
                        class="w-4 h-4 rounded-full mr-3"
                        :style="{ backgroundColor: sharingCategory.color }"
                    ></span>
                    <span class="font-medium">
                        {{ sharingCategory.name }}
                    </span>
                </div>

                <div class="space-y-4">
                    <div>
                        <label
                            for="share-email"
                            class="block text-sm font-medium text-gray-700"
                        >
                            メールアドレス
                        </label>
                        <input
                            type="email"
                            id="share-email"
                            v-model="shareEmail"
                            placeholder="共有する相手のメールアドレス"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        />
                    </div>

                    <div>
                        <label
                            for="share-permission"
                            class="block text-sm font-medium text-gray-700"
                        >
                            権限
                        </label>
                        <select
                            id="share-permission"
                            v-model="sharePermission"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        >
                            <option value="view">閲覧のみ</option>
                            <option value="edit">編集可能</option>
                        </select>
                    </div>

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="slack-auth-required"
                            v-model="slackAuthRequired"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        />
                        <label
                            for="slack-auth-required"
                            class="ml-2 block text-sm text-gray-900"
                        >
                            Slack認証を必須にする
                        </label>
                    </div>

                    <div v-if="shareError" class="text-red-500 text-sm">
                        {{ shareError }}
                    </div>

                    <div v-if="shareSuccess" class="text-green-500 text-sm">
                        {{ shareSuccess }}
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button
                            @click="showShareModal = false"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300"
                        >
                            キャンセル
                        </button>
                        <button
                            @click="shareCategory"
                            :disabled="!shareEmail.trim() || isSubmitting"
                            :class="[
                                'px-4 py-2 text-white rounded',
                                !shareEmail.trim() || isSubmitting
                                    ? 'bg-blue-400 cursor-not-allowed'
                                    : 'bg-blue-500 hover:bg-blue-600',
                            ]"
                        >
                            {{ isSubmitting ? "共有中..." : "共有する" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "UnifiedCategoriesView",

    data() {
        return {
            // Data
            categories: [],
            sharedWithMe: [],
            mySharedCategories: [],
            outgoingRequests: [],
            incomingRequests: [],
            isLoading: true,
            errorMessage: null,

            // UI state
            showCategoryInput: false,
            showEditModal: false,
            showShareModal: false,
            isSubmitting: false,

            // Form data
            newCategory: {
                name: "",
                color: "#3b82f6",
            },
            editingCategory: {
                id: null,
                name: "",
                color: "#3b82f6",
            },
            sharingCategory: {
                id: null,
                name: "",
                color: "#3b82f6",
            },
            shareEmail: "",
            sharePermission: "view",
            slackAuthRequired: true,

            // Status messages
            shareError: "",
            shareSuccess: "",
        };
    },

    mounted() {
        this.loadData();
    },

    methods: {
        async loadData() {
            this.isLoading = true;
            this.errorMessage = null;

            try {
                // Load categories
                const categoriesResponse = await axios.get("/api/categories", {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                // Load shared categories
                const sharedResponse = await axios.get(
                    "/api/shared/categories",
                    {
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );

                // Load share requests
                const requestsResponse = await axios.get(
                    "/api/share-requests",
                    {
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );

                // Update state
                this.categories = categoriesResponse.data || [];
                this.sharedWithMe = sharedResponse.data || [];

                // Get categories that the user has shared with others
                this.mySharedCategories = this.categories.filter(
                    (cat) => cat.sharedWith && cat.sharedWith.length > 0,
                );

                // Share requests
                const requestsData = requestsResponse.data || {};
                this.outgoingRequests = requestsData.outgoing_requests || [];
                this.incomingRequests = requestsData.incoming_requests || [];
            } catch (error) {
                console.error("Error loading data:", error);
                this.errorMessage =
                    "データの読み込みに失敗しました。ページを更新してください。";
            } finally {
                this.isLoading = false;
            }
        },

        async createCategory() {
            if (!this.newCategory.name.trim()) {
                return;
            }

            this.isSubmitting = true;

            try {
                const response = await axios.post("/api/categories", {
                    name: this.newCategory.name.trim(),
                    color: this.newCategory.color,
                });

                // Reset form
                this.newCategory.name = "";
                this.newCategory.color = "#3b82f6";
                this.showCategoryInput = false;

                // Reload data
                await this.loadData();
            } catch (error) {
                console.error("Error creating category:", error);
                alert(
                    error.response?.data?.message ||
                        "カテゴリーの作成に失敗しました",
                );
            } finally {
                this.isSubmitting = false;
            }
        },

        openEditModal(category) {
            this.editingCategory = {
                id: category.id,
                name: category.name,
                color: category.color,
            };
            this.showEditModal = true;
        },

        async updateCategory() {
            if (!this.editingCategory.name.trim()) {
                return;
            }

            this.isSubmitting = true;

            try {
                const response = await axios.put(
                    `/api/categories/${this.editingCategory.id}`,
                    {
                        name: this.editingCategory.name.trim(),
                        color: this.editingCategory.color,
                    },
                );

                // Close modal
                this.showEditModal = false;

                // Reload data
                await this.loadData();
            } catch (error) {
                console.error("Error updating category:", error);
                alert(
                    error.response?.data?.message ||
                        "カテゴリーの更新に失敗しました",
                );
            } finally {
                this.isSubmitting = false;
            }
        },

        confirmDeleteCategory(categoryId) {
            if (
                confirm(
                    "このカテゴリーを削除しますか？関連するタスクのカテゴリーも削除されます。",
                )
            ) {
                this.deleteCategory(categoryId);
            }
        },

        async deleteCategory(categoryId) {
            try {
                const response = await axios.delete(
                    `/api/categories/${categoryId}`,
                );

                // Reload data
                await this.loadData();
            } catch (error) {
                console.error("Error deleting category:", error);
                alert(
                    error.response?.data?.message ||
                        "カテゴリーの削除に失敗しました",
                );
            }
        },

        openShareModal(category) {
            this.sharingCategory = {
                id: category.id,
                name: category.name,
                color: category.color,
            };
            this.shareEmail = "";
            this.sharePermission = "view";
            this.slackAuthRequired = true;
            this.shareError = "";
            this.shareSuccess = "";
            this.showShareModal = true;
        },

        async shareCategory() {
            if (!this.shareEmail.trim()) {
                this.shareError = "メールアドレスを入力してください";
                return;
            }

            this.isSubmitting = true;
            this.shareError = "";
            this.shareSuccess = "";

            try {
                const response = await axios.post(
                    `/api/categories/${this.sharingCategory.id}/shares`,
                    {
                        email: this.shareEmail.trim(),
                        permission: this.sharePermission,
                        slack_auth_required: this.slackAuthRequired,
                    },
                );

                const data = response.data;

                if (data.success) {
                    this.shareSuccess = this.slackAuthRequired
                        ? "カテゴリー共有リクエストが送信されました。相手はSlackで承認する必要があります。"
                        : "カテゴリーが共有されました";

                    // Auto close after delay
                    setTimeout(() => {
                        this.showShareModal = false;
                        this.shareSuccess = "";
                        this.loadData();
                    }, 2000);
                } else if (
                    data.message &&
                    data.message.includes("already been sent")
                ) {
                    this.shareSuccess =
                        "カテゴリー共有リクエストが既に送信されています。相手はSlackで承認する必要があります。";

                    // Auto close after delay
                    setTimeout(() => {
                        this.showShareModal = false;
                        this.shareSuccess = "";
                    }, 2000);
                } else if (data.error) {
                    this.shareError = data.error;
                }
            } catch (error) {
                console.error("Error sharing category:", error);
                this.shareError =
                    error.response?.data?.message ||
                    "共有処理中にエラーが発生しました";
            } finally {
                this.isSubmitting = false;
            }
        },

        async approveShareRequest(token) {
            try {
                const response = await axios.get(
                    `/api/share-requests/${token}/approve`,
                );
                await this.loadData();
            } catch (error) {
                console.error("Error approving request:", error);
                alert("共有リクエストの承認に失敗しました");
            }
        },

        async rejectShareRequest(token) {
            try {
                const response = await axios.get(
                    `/api/share-requests/${token}/reject`,
                );
                await this.loadData();
            } catch (error) {
                console.error("Error rejecting request:", error);
                alert("共有リクエストの拒否に失敗しました");
            }
        },
    },
};
</script>
