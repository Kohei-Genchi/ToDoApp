// ShareForm.vue
<template>
    <div class="mb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">
            新しいユーザーと共有
        </h4>
        <div class="flex space-x-2">
            <input
                type="email"
                :value="email"
                @input="$emit('update:email', $event.target.value)"
                placeholder="メールアドレスを入力"
                class="flex-1 border rounded p-2 text-sm"
            />
            <select
                :value="permission"
                @change="$emit('update:permission', $event.target.value)"
                class="border rounded p-2 text-sm"
            >
                <option value="view">閲覧のみ</option>
                <option value="edit">編集可能</option>
            </select>
        </div>
        <div class="mt-2">
            <button
                @click="$emit('share')"
                class="w-full bg-blue-500 text-white rounded py-2 text-sm hover:bg-blue-600 transition"
                :disabled="!isValidEmail || isSubmitting"
                :class="{
                    'opacity-50 cursor-not-allowed':
                        !isValidEmail || isSubmitting,
                }"
            >
                {{ isSubmitting ? "処理中..." : "共有する" }}
            </button>
        </div>
        <p v-if="errorMessage" class="mt-2 text-sm text-red-500">
            {{ errorMessage }}
        </p>
    </div>
</template>

<script>
export default {
    name: "ShareForm",
    props: {
        email: {
            type: String,
            required: true,
        },
        permission: {
            type: String,
            required: true,
        },
        isSubmitting: {
            type: Boolean,
            default: false,
        },
        isValidEmail: {
            type: Boolean,
            default: false,
        },
        errorMessage: {
            type: String,
            default: "",
        },
    },
    emits: ["update:email", "update:permission", "share"],
};
</script>
