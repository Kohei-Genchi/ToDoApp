<template>
    <div class="flex border-b border-gray-200 bg-white sticky top-0 z-20">
        <!-- 時間列ヘッダー (固定幅) -->
        <div
            class="w-16 px-1 py-2 text-xs font-medium text-gray-500 flex-shrink-0"
        ></div>

        <!-- ユーザー名列 (均等に分割) -->
        <div class="flex flex-1">
            <div
                v-for="user in sharedUsers"
                :key="'user-' + user.id"
                class="flex-1 min-w-[250px] px-2 py-2 text-center border-l border-gray-200"
            >
                <div class="text-sm font-medium text-gray-800 truncate">
                    {{ user.name }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "CalendarHeaderRow",
    props: {
        sharedUsers: {
            type: Array,
            required: true,
        },
    },
};
</script>
