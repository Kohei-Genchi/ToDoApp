<template>
    <div class="flex border-b border-gray-200 bg-gray-50 sticky top-8 z-10">
        <!-- 時間ヘッダー (固定幅) -->
        <div
            class="w-16 px-1 py-1 text-xs font-medium text-gray-500 text-center flex-shrink-0"
        >
            時間
        </div>

        <!-- メールアドレス列 (均等に分割) -->
        <div class="flex flex-1">
            <div
                v-for="user in sharedUsers"
                :key="'header-' + user.id"
                class="flex-1 min-w-[250px] px-1 py-1 text-center border-l border-gray-200"
            >
                <div class="text-xs text-gray-500 truncate">
                    {{ user.email }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "CalendarEmailRow",
    props: {
        sharedUsers: {
            type: Array,
            required: true,
        },
    },
};
</script>
