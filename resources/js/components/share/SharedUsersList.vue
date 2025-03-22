// SharedUsersList.vue
<template>
    <div class="mb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">共有済みユーザー</h4>
        <ul class="space-y-2">
            <li
                v-for="user in sharedUsers"
                :key="user.id"
                class="flex items-center justify-between p-2 bg-gray-50 rounded"
            >
                <div>
                    <p class="text-sm font-medium">{{ user.name }}</p>
                    <p class="text-xs text-gray-500">
                        {{ user.email }}
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <select
                        v-if="user.pivot"
                        v-model="user.pivot.permission"
                        @change="updateUserPermission(user)"
                        class="text-xs border rounded p-1"
                    >
                        <option value="view">閲覧のみ</option>
                        <option value="edit">編集可能</option>
                    </select>
                    <select v-else class="text-xs border rounded p-1" disabled>
                        <option>権限なし</option>
                    </select>
                    <button
                        @click="unshareUser(user)"
                        class="text-red-500 hover:text-red-700"
                        title="共有解除"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    name: "SharedUsersList",
    props: {
        sharedUsers: {
            type: Array,
            required: true,
        },
    },
    emits: ["update-permission", "unshare"],
    setup(props, { emit }) {
        /**
         * ユーザーの権限を更新
         */
        function updateUserPermission(user) {
            emit("update-permission", user);
        }

        /**
         * ユーザーの共有を解除
         */
        function unshareUser(user) {
            emit("unshare", user);
        }

        return {
            updateUserPermission,
            unshareUser,
        };
    },
};
</script>
