<template>
    <div
        class="mb-4 p-2 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800"
    >
        最大共有数 ({{ max }}人)
        に達しています。新しく共有するには、既存の共有を解除してください。
    </div>
</template>

<script>
export default {
    name: "MaxUsersWarning",
    props: {
        max: {
            type: Number,
            required: true,
        },
    },
};
</script>
