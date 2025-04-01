<template>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">共有カテゴリー</h2>
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

        <!-- Content when loaded -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Categories shared with me -->
            <div>
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
                            <span class="font-medium">{{ category.name }}</span>
                            <span
                                class="ml-2 text-xs text-gray-500"
                                v-if="category.user"
                                >{{ category.user.name }} から</span
                            >
                        </div>
                        <span
                            class="text-xs px-2 py-1 rounded-full"
                            :class="
                                category.pivot?.permission === 'edit'
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-blue-100 text-blue-800'
                            "
                        >
                            {{
                                category.pivot?.permission === "edit"
                                    ? "編集可能"
                                    : "閲覧のみ"
                            }}
                        </span>
                    </li>
                </ul>
            </div>

            <!-- My categories shared with others -->
            <div>
                <h3 class="font-medium text-gray-700 mb-3">
                    自分が共有しているカテゴリー
                </h3>

                <p v-if="mySharedCategories.length === 0" class="text-gray-500">
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
                            <span class="font-medium">{{ category.name }}</span>
                        </div>

                        <div class="pl-5">
                            <p class="text-xs text-gray-500 mb-1">共有先:</p>
                            <div
                                v-for="user in category.sharedWith"
                                :key="user.id"
                                class="flex justify-between items-center py-1"
                            >
                                <span class="text-sm">{{ user.name }}</span>
                                <span
                                    class="text-xs px-2 py-0.5 rounded-full"
                                    :class="
                                        user.pivot?.permission === 'edit'
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-blue-100 text-blue-800'
                                    "
                                >
                                    {{
                                        user.pivot?.permission === "edit"
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

        <!-- Share Requests Section -->
        <div v-if="!isLoading && !errorMessage" class="mt-8">
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
                                <span class="text-xs text-gray-500">{{
                                    request.created_at
                                }}</span>
                            </div>
                            <div class="mt-1 text-xs text-gray-600">
                                {{ request.item_name }} ({{
                                    request.permission === "edit"
                                        ? "編集可能"
                                        : "閲覧のみ"
                                }})
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
                                <span class="text-xs text-gray-500">{{
                                    request.created_at
                                }}</span>
                            </div>
                            <div class="mt-1 text-xs text-gray-600">
                                {{ request.item_name }} ({{
                                    request.permission === "edit"
                                        ? "編集可能"
                                        : "閲覧のみ"
                                }})
                            </div>
                            <div class="mt-2 flex space-x-2">
                                <button
                                    @click="approveShareRequest(request.token)"
                                    class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded hover:bg-green-200"
                                >
                                    承認
                                </button>
                                <button
                                    @click="rejectShareRequest(request.token)"
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
</template>

<script>
import { ref, onMounted } from "vue";
import axios from "axios";

export default {
    name: "SharedCategoriesView",

    props: {
        currentUserId: {
            type: Number,
            default: null,
        },
    },

    setup() {
        // State variables
        const sharedWithMe = ref([]);
        const mySharedCategories = ref([]);
        const outgoingRequests = ref([]);
        const incomingRequests = ref([]);
        const isLoading = ref(true);
        const errorMessage = ref("");

        // Load all data
        const loadData = async () => {
            try {
                isLoading.value = true;
                errorMessage.value = "";

                console.log("Loading shared categories data...");

                // Use web routes instead of API routes to bypass authentication issues
                // 1. Load categories shared with current user
                const sharedResponse = await axios.get(
                    "/shared-data/categories",
                );
                console.log("Shared categories response:", sharedResponse.data);
                sharedWithMe.value = sharedResponse.data || [];

                // 2. Load categories that current user has shared with others
                const mySharedResponse = await axios.get(
                    "/shared-data/my-shared-categories",
                );
                console.log(
                    "My shared categories response:",
                    mySharedResponse.data,
                );
                mySharedCategories.value = mySharedResponse.data || [];

                // 3. Load share requests (both incoming and outgoing)
                const requestsResponse = await axios.get(
                    "/shared-data/share-requests",
                );
                console.log("Share requests response:", requestsResponse.data);
                const requestsData = requestsResponse.data || {};

                outgoingRequests.value = requestsData.outgoing_requests || [];
                incomingRequests.value = requestsData.incoming_requests || [];
            } catch (error) {
                console.error("Error loading shared categories data:", error);
                errorMessage.value =
                    "データの読み込みに失敗しました。" +
                    (error.response?.data?.message || error.message || "");
            } finally {
                isLoading.value = false;
            }
        };

        // Approve share request
        const approveShareRequest = async (token) => {
            if (!token) return;

            try {
                await axios.get(`/share-requests/${token}/approve`);
                await loadData(); // Reload data after approval
            } catch (error) {
                console.error("Error approving share request:", error);
                errorMessage.value = "共有リクエストの承認に失敗しました。";
            }
        };

        // Reject share request
        const rejectShareRequest = async (token) => {
            if (!token) return;

            try {
                await axios.get(`/share-requests/${token}/reject`);
                await loadData(); // Reload data after rejection
            } catch (error) {
                console.error("Error rejecting share request:", error);
                errorMessage.value = "共有リクエストの拒否に失敗しました。";
            }
        };

        // Load data on component mount
        onMounted(() => {
            console.log("SharedCategoriesView mounted, loading data...");
            loadData();
        });

        return {
            sharedWithMe,
            mySharedCategories,
            outgoingRequests,
            incomingRequests,
            isLoading,
            errorMessage,
            approveShareRequest,
            rejectShareRequest,
        };
    },
};
</script>
