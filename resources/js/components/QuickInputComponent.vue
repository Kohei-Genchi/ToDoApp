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
                    placeholder="新しいメモを入力 または 🎤 で音声入力"
                    class="w-full bg-gray-700 px-3 py-2 text-sm focus:outline-none text-white"
                    ref="inputField"
                />
                <!-- Voice Recording Button -->
                <button
                    type="button"
                    @click="toggleRecording"
                    class="px-2 py-2 text-gray-400 hover:text-white"
                    :class="{ 'text-red-500 animate-pulse': isRecording }"
                    title="音声入力"
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
                            d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                        />
                    </svg>
                </button>
                <button
                    type="submit"
                    class="px-2 py-2 text-gray-400 hover:text-white"
                    :disabled="isSubmitting || isProcessingAudio"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        :class="{
                            'animate-spin': isSubmitting || isProcessingAudio,
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
            <!-- Recording indicator -->
            <div
                v-if="isRecording"
                class="mt-2 text-xs text-red-400 animate-pulse"
            >
                録音中... {{ recordingTime }}秒 (タップして終了)
            </div>
            <!-- Processing indicator -->
            <div v-if="isProcessingAudio" class="mt-2 text-xs text-blue-400">
                音声を処理中...
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
            isRecording: false,
            isProcessingAudio: false,
            recordingTime: 0,
            recordingInterval: null,
            mediaRecorder: null,
            audioChunks: [],
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

    beforeUnmount() {
        // Clean up recording if component is unmounted while recording
        this.stopRecording();
        this.clearRecordingInterval();
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
         * Toggle recording state
         */
        async toggleRecording() {
            if (this.isRecording) {
                await this.stopRecording();
            } else {
                await this.startRecording();
            }
        },

        /**
         * Start recording audio
         */
        async startRecording() {
            this.clearError();

            try {
                // Request microphone access
                const stream = await navigator.mediaDevices.getUserMedia({
                    audio: true,
                });

                // Set up media recorder
                this.setupMediaRecorder(stream);

                // Start recording timer
                this.startRecordingTimer();
            } catch (error) {
                this.handleError(
                    "マイクへのアクセスができませんでした。ブラウザの設定を確認してください。",
                    error,
                );
            }
        },

        /**
         * Set up the media recorder with event handlers
         * @param {MediaStream} stream - The audio stream
         */
        setupMediaRecorder(stream) {
            this.audioChunks = [];
            this.mediaRecorder = new MediaRecorder(stream);

            // Event handlers
            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.audioChunks.push(event.data);
                }
            };

            this.mediaRecorder.onstop = async () => {
                await this.processAudio();
            };

            // Start recording
            this.mediaRecorder.start();
            this.isRecording = true;
        },

        /**
         * Start recording timer
         */
        startRecordingTimer() {
            this.recordingTime = 0;
            this.recordingInterval = setInterval(() => {
                this.recordingTime++;

                // Auto-stop after 30 seconds to prevent very large files
                if (this.recordingTime >= 30) {
                    this.stopRecording();
                }
            }, 1000);
        },

        /**
         * Stop recording audio
         */
        async stopRecording() {
            if (
                !this.mediaRecorder ||
                this.mediaRecorder.state === "inactive"
            ) {
                return;
            }

            // Stop the media recorder
            this.mediaRecorder.stop();
            this.clearRecordingInterval();

            // Stop all audio tracks
            if (this.mediaRecorder.stream) {
                this.mediaRecorder.stream
                    .getTracks()
                    .forEach((track) => track.stop());
            }

            this.isRecording = false;
        },

        /**
         * Clear recording interval timer
         */
        clearRecordingInterval() {
            if (this.recordingInterval) {
                clearInterval(this.recordingInterval);
                this.recordingInterval = null;
            }
        },

        /**
         * Process the recorded audio
         */
        async processAudio() {
            if (this.audioChunks.length === 0) {
                return;
            }

            this.isProcessingAudio = true;
            this.clearError();

            try {
                // Create audio blob and form data
                const audioBlob = new Blob(this.audioChunks, {
                    type: "audio/webm",
                });

                const formData = this.createAudioFormData(audioBlob);

                // Send to server for processing
                const response = await this.sendAudioForProcessing(formData);

                // Handle response
                this.handleAudioProcessingResponse(response);
            } catch (error) {
                this.handleError("音声処理中にエラーが発生しました", error);
            } finally {
                this.isProcessingAudio = false;
                this.audioChunks = [];
            }
        },

        /**
         * Create form data for audio upload
         * @param {Blob} audioBlob - The audio blob
         * @returns {FormData} - The form data
         */
        createAudioFormData(audioBlob) {
            const formData = new FormData();
            formData.append("audio", audioBlob, "recording.webm");
            formData.append("_token", this.csrfToken);
            return formData;
        },

        /**
         * Send audio to server for processing
         * @param {FormData} formData - The form data
         * @returns {Promise} - API response
         */
        async sendAudioForProcessing(formData) {
            return axios.post("/api/speech-to-tasks", formData, {
                headers: {
                    "X-CSRF-TOKEN": this.csrfToken,
                    "Content-Type": "multipart/form-data",
                    "X-Requested-With": "XMLHttpRequest",
                },
                withCredentials: true,
            });
        },

        /**
         * Handle audio processing response
         * @param {Object} response - API response
         */
        handleAudioProcessingResponse(response) {
            if (!response.data || !response.data.success) {
                throw new Error(
                    response.data?.message || "音声処理に失敗しました",
                );
            }

            // If tasks were created in background
            if (response.data.background) {
                this.showTemporaryMessage(
                    "音声を処理中です。タスクは自動的に追加されます。",
                );
                return;
            }

            // If tasks were created
            if (response.data.tasks && response.data.tasks.length > 0) {
                this.$emit("multiple-tasks-added", response.data.tasks);
                this.resetForm();
            }
            // If only text was returned
            else if (response.data.text) {
                this.taskTitle = response.data.text;
            }
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
