<template>
    <div class="mb-4">
        <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">
            クイック入力
        </div>
        <form @submit.prevent="submitTask">
            <div class="flex items-center bg-gray-700 rounded overflow-hidden">
                <input
                    type="text"
                    v-model="taskTitle"
                    required
                    placeholder="新しいメモを入力"
                    class="w-full bg-gray-700 px-3 py-2 text-sm focus:outline-none text-white"
                    ref="inputField"
                />
                <button
                    type="submit"
                    class="px-2 py-2 text-gray-400 hover:text-white"
                    :disabled="isSubmitting"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        :class="{
                            'animate-spin': isSubmitting,
                        }"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                </button>
            </div>
            <!-- Error message -->
            <div v-if="errorMessage" class="mt-2 text-xs text-red-400">
                {{ errorMessage }}
            </div>
        </form>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "QuickInputComponent",

    data() {
        return {
            taskTitle: "",
            isSubmitting: false,
            errorMessage: "",
            csrfToken:
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "",
        };
    },

    mounted() {
        // Focus the input field when component mounts
        this.$refs.inputField.focus();
    },

    methods: {
        /**
         * Submit a text task
         */
        async submitTask() {
            if (!this.taskTitle.trim() || this.isSubmitting) {
                return;
            }

            this.isSubmitting = true;
            this.clearError();

            try {
                const response = await this.createMemo(this.taskTitle);

                // If successful, emit an event to notify parent component
                if (response.data && response.data.memo) {
                    this.$emit("task-added", response.data.memo);
                    this.resetForm();
                }
            } catch (error) {
                this.handleError("タスクの追加に失敗しました", error);
            } finally {
                this.isSubmitting = false;
            }
        },

        /**
         * Create a memo via API
         * @param {string} title - The task title
         * @returns {Promise} - API response
         */
        async createMemo(title) {
            return axios.post(
                "/api/memos",
                { title },
                {
                    headers: this.getHeaders(),
                },
            );
        },

        /**
         * Get common request headers
         * @returns {Object} - Headers object
         */
        getHeaders() {
            return {
                "X-CSRF-TOKEN": this.csrfToken,
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            };
        },

        /**
         * Reset form after successful submission
         */
        resetForm() {
            this.taskTitle = "";
            this.$refs.inputField.focus();
        },

        /**
         * Show error message to user
         * @param {string} message - Error message
         * @param {Error} error - Error object for logging
         */
        handleError(message, error = null) {
            if (error) console.error(message, error);
            this.showTemporaryMessage(message, 5000);
        },

        /**
         * Show a temporary message and clear it after a delay
         * @param {string} message - Message to display
         * @param {number} duration - Duration in milliseconds
         */
        showTemporaryMessage(message, duration = 3000) {
            this.errorMessage = message;
            setTimeout(() => {
                this.clearError();
            }, duration);
        },

        /**
         * Clear error message
         */
        clearError() {
            this.errorMessage = "";
        },
    },
};
</script>
