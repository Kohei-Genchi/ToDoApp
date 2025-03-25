<template>
    <div class="share-requests p-4 bg-white rounded-lg shadow">
        <h3 class="text-lg font-medium mb-4">共有リクエスト</h3>

        <div v-if="isLoading" class="py-4 text-center">
            <div
                class="inline-block animate-spin h-5 w-5 border-2 border-gray-500 border-t-transparent rounded-full mr-2"
            ></div>
            読み込み中...
        </div>

        <template v-else>
            <!-- Outgoing Requests -->
            <div v-if="outgoingRequests.length > 0" class="mb-6">
                <h4 class="font-medium text-gray-700 mb-2">
                    送信済みリクエスト
                </h4>
                <div class="space-y-2">
                    <div
                        v-for="request in outgoingRequests"
                        :key="request.id"
                        class="p-3 bg-blue-50 border border-blue-100 rounded-md"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium">
                                    {{ request.task_title }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    <span
                                        class="inline-block bg-blue-100 px-2 py-0.5 rounded text-xs mr-2"
                                    >
                                        {{
                                            request.share_type === "task"
                                                ? "タスク共有"
                                                : "全タスク共有"
                                        }}
                                    </span>
                                    受信者: {{ request.recipient_email }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ request.created_at }} 送信 (期限:
                                    {{ request.expires_at }})
                                </p>
                            </div>
                            <button
                                @click="cancelRequest(request.id)"
                                class="text-sm text-red-500 hover:text-red-700"
                            >
                                キャンセル
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Incoming Requests -->
            <div v-if="incomingRequests.length > 0" class="mb-6">
                <h4 class="font-medium text-gray-700 mb-2">
                    受信したリクエスト
                </h4>
                <div class="space-y-2">
                    <div
                        v-for="request in incomingRequests"
                        :key="request.id"
                        class="p-3 bg-green-50 border border-green-100 rounded-md"
                    >
                        <div>
                            <p class="font-medium">{{ request.task_title }}</p>
                            <p class="text-sm text-gray-600">
                                <span
                                    class="inline-block bg-green-100 px-2 py-0.5 rounded text-xs mr-2"
                                >
                                    {{
                                        request.share_type === "task"
                                            ? "タスク共有"
                                            : "全タスク共有"
                                    }}
                                </span>
                                送信者: {{ request.requester_name }} ({{
                                    request.requester_email
                                }})
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ request.created_at }} 受信 (期限:
                                {{ request.expires_at }})
                            </p>
                        </div>
                        <div class="flex space-x-2 mt-2">
                            <button
                                @click="approveRequest(request.token)"
                                class="px-3 py-1 bg-green-500 text-white text-sm rounded hover:bg-green-600"
                            >
                                承認
                            </button>
                            <button
                                @click="rejectRequest(request.token)"
                                class="px-3 py-1 bg-gray-500 text-white text-sm rounded hover:bg-gray-600"
                            >
                                拒否
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No requests -->
            <div
                v-if="
                    outgoingRequests.length === 0 &&
                    incomingRequests.length === 0
                "
                class="p-4 bg-gray-50 rounded-md text-center text-gray-500"
            >
                共有リクエストはありません
            </div>
        </template>
    </div>
</template>

<script>
import TaskShareApi from "../../api/taskShare";

export default {
    name: "ShareRequestsList",

    data() {
        return {
            outgoingRequests: [],
            incomingRequests: [],
            isLoading: true,
        };
    },

    mounted() {
        this.loadShareRequests();
    },

    methods: {
        async loadShareRequests() {
            this.isLoading = true;

            try {
                const response = await TaskShareApi.getShareRequests();

                this.outgoingRequests = response.data.outgoing_requests || [];
                this.incomingRequests = response.data.incoming_requests || [];
            } catch (error) {
                console.error("Failed to load share requests:", error);
                this.showNotification(
                    "共有リクエストの読み込みに失敗しました",
                    "error",
                );
            } finally {
                this.isLoading = false;
            }
        },

        async cancelRequest(requestId) {
            if (!confirm("このリクエストをキャンセルしますか？")) {
                return;
            }

            try {
                await TaskShareApi.cancelShareRequest(requestId);

                this.showNotification("リクエストがキャンセルされました");
                this.outgoingRequests = this.outgoingRequests.filter(
                    (req) => req.id !== requestId,
                );
            } catch (error) {
                console.error("Failed to cancel share request:", error);
                this.showNotification(
                    "リクエストのキャンセルに失敗しました",
                    "error",
                );
            }
        },

        async approveRequest(token) {
            try {
                await TaskShareApi.approveShareRequest(token);

                this.showNotification("共有リクエストを承認しました");
                this.loadShareRequests();
            } catch (error) {
                console.error("Failed to approve share request:", error);
                this.showNotification(
                    "リクエストの承認に失敗しました",
                    "error",
                );
            }
        },

        async rejectRequest(token) {
            try {
                await TaskShareApi.rejectShareRequest(token);

                this.showNotification("共有リクエストを拒否しました");
                this.loadShareRequests();
            } catch (error) {
                console.error("Failed to reject share request:", error);
                this.showNotification(
                    "リクエストの拒否に失敗しました",
                    "error",
                );
            }
        },

        showNotification(message, type = "success") {
            // Either use an existing notification system or implement a simple one
            if (window.showNotification) {
                window.showNotification(message, type);
            } else {
                alert(message);
            }
        },
    },
};
</script>
