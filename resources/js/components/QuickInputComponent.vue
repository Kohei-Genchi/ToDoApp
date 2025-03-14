<!-- resources/js/components/QuickInputComponent.vue -->
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
                        :class="{ 'animate-spin': isSubmitting }"
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
        async submitTask() {
            if (!this.taskTitle.trim() || this.isSubmitting) {
                return;
            }

            this.isSubmitting = true;

            try {
                // Send POST request to create a new memo task
                const response = await axios.post(
                    "/api/memos", // Changed from /api/memos to match your API route
                    { title: this.taskTitle },
                    {
                        headers: {
                            "X-CSRF-TOKEN": this.csrfToken,
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );

                // If successful, emit an event to notify parent component
                if (response.data && response.data.memo) {
                    this.$emit("task-added", response.data.memo);
                    this.taskTitle = ""; // Clear the input field
                    this.$refs.inputField.focus(); // Focus back on input
                }
            } catch (error) {
                console.error("Error submitting task:", error);
                alert("タスクの追加に失敗しました");
            } finally {
                this.isSubmitting = false;
            }
        },

        async loadMemos() {
            try {
                // Fetch the updated memo list HTML
                const response = await axios.get("/api/memos-partial", {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "text/html",
                    },
                });

                // Update all memo list containers on the page
                if (response.data) {
                    const memoContainers = document.querySelectorAll(
                        ".memo-list-container",
                    );
                    if (memoContainers.length > 0) {
                        memoContainers.forEach((container) => {
                            container.innerHTML = response.data;
                        });

                        // Reattach event handlers to new elements
                        this.attachEventHandlers();
                    }
                }
            } catch (error) {
                console.error("Error loading memos:", error);
            }
        },

        attachEventHandlers() {
            // Find all memo items and attach click handlers
            const memoItems = document.querySelectorAll(
                '.memo-list-container [onclick^="editTodo"]',
            );
            memoItems.forEach((item) => {
                // The original onclick attribute is preserved, so we don't need to modify it
            });
        },
    },
};
</script>
