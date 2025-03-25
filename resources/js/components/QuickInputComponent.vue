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
            // QuickInputComponent.vue のdata関数内
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
        clearInterval(this.recordingInterval);
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
                    "/api/memos",
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
                this.errorMessage = "タスクの追加に失敗しました";
                setTimeout(() => {
                    this.errorMessage = "";
                }, 3000);
            } finally {
                this.isSubmitting = false;
            }
        },

        // Toggle recording state
        async toggleRecording() {
            if (this.isRecording) {
                await this.stopRecording();
            } else {
                await this.startRecording();
            }
        },

        // Start recording audio
        async startRecording() {
            this.errorMessage = "";

            try {
                // Request microphone access
                const stream = await navigator.mediaDevices.getUserMedia({
                    audio: true,
                });

                // Set up media recorder
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
                this.recordingTime = 0;

                // Start recording timer
                this.recordingInterval = setInterval(() => {
                    this.recordingTime++;

                    // Auto-stop after 30 seconds to prevent very large files
                    if (this.recordingTime >= 30) {
                        this.stopRecording();
                    }
                }, 1000);
            } catch (error) {
                console.error("Error starting recording:", error);
                this.errorMessage =
                    "マイクへのアクセスができませんでした。ブラウザの設定を確認してください。";
            }
        },

        // Stop recording audio
        async stopRecording() {
            if (
                !this.mediaRecorder ||
                this.mediaRecorder.state === "inactive"
            ) {
                return;
            }

            // Stop the media recorder
            this.mediaRecorder.stop();
            clearInterval(this.recordingInterval);

            // Stop all audio tracks
            this.mediaRecorder.stream
                .getTracks()
                .forEach((track) => track.stop());

            this.isRecording = false;
        },

        // Process the recorded audio
        async processAudio() {
            if (this.audioChunks.length === 0) {
                return;
            }

            this.isProcessingAudio = true;

            try {
                // Create audio blob
                const audioBlob = new Blob(this.audioChunks, {
                    type: "audio/webm",
                });

                // Create form data
                const formData = new FormData();
                formData.append("audio", audioBlob, "recording.webm");

                // Add CSRF token to form data as well
                formData.append("_token", this.csrfToken);

                console.log("Sending audio data, token:", this.csrfToken);

                // Send to server for processing with explicit CSRF and credentials
                const response = await axios.post(
                    "/api/speech-to-tasks",
                    formData,
                    {
                        headers: {
                            "X-CSRF-TOKEN": this.csrfToken,
                            "Content-Type": "multipart/form-data",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        withCredentials: true,
                    },
                );

                // Handle response
                if (response.data && response.data.success) {
                    // If tasks were created
                    if (response.data.tasks && response.data.tasks.length > 0) {
                        this.$emit("multiple-tasks-added", response.data.tasks);
                        this.taskTitle = ""; // Clear input field
                    }
                    // If only text was returned
                    else if (response.data.text) {
                        this.taskTitle = response.data.text;
                    }
                } else {
                    throw new Error(
                        response.data.message || "音声処理に失敗しました",
                    );
                }
            } catch (error) {
                console.error("Error processing audio:", error);
                this.errorMessage =
                    error.response?.data?.message ||
                    "音声処理中にエラーが発生しました";
                setTimeout(() => {
                    this.errorMessage = "";
                }, 5000);
            } finally {
                this.isProcessingAudio = false;
                this.audioChunks = [];
            }
        },

        // Compress audio using Web Audio API
        async compressAudio(audioBlob) {
            return new Promise(async (resolve, reject) => {
                try {
                    // Convert blob to array buffer
                    const arrayBuffer = await audioBlob.arrayBuffer();
                    const audioContext = new (window.AudioContext ||
                        window.webkitAudioContext)();

                    // Decode audio
                    const audioBuffer =
                        await audioContext.decodeAudioData(arrayBuffer);

                    // Create offline context for processing
                    const offlineContext = new OfflineAudioContext(
                        1, // mono
                        audioBuffer.length,
                        22050, // Lower sample rate for compression
                    );

                    // Create buffer source
                    const source = offlineContext.createBufferSource();
                    source.buffer = audioBuffer;

                    // Add compression node
                    const compressor =
                        offlineContext.createDynamicsCompressor();
                    compressor.threshold.value = -50;
                    compressor.knee.value = 40;
                    compressor.ratio.value = 12;
                    compressor.attack.value = 0;
                    compressor.release.value = 0.25;

                    // Connect nodes
                    source.connect(compressor);
                    compressor.connect(offlineContext.destination);

                    // Start source and render
                    source.start(0);
                    const renderedBuffer =
                        await offlineContext.startRendering();

                    // Convert buffer to wav
                    const wavBlob = this.bufferToWav(renderedBuffer);
                    resolve(wavBlob);
                } catch (error) {
                    console.error("Audio compression error:", error);
                    // If compression fails, return original blob
                    resolve(audioBlob);
                }
            });
        },

        // Convert audio buffer to WAV format
        bufferToWav(buffer) {
            const numOfChannels = buffer.numberOfChannels;
            const length = buffer.length * numOfChannels * 2;
            const sampleRate = buffer.sampleRate;

            // Create buffer with WAV header
            const arrayBuffer = new ArrayBuffer(44 + length);
            const view = new DataView(arrayBuffer);

            // WAV header - 44 bytes
            // "RIFF" chunk descriptor
            this.writeString(view, 0, "RIFF");
            view.setUint32(4, 36 + length, true);
            this.writeString(view, 8, "WAVE");

            // "fmt " sub-chunk
            this.writeString(view, 12, "fmt ");
            view.setUint32(16, 16, true); // subchunk1size
            view.setUint16(20, 1, true); // audio format (PCM)
            view.setUint16(22, numOfChannels, true);
            view.setUint32(24, sampleRate, true);
            view.setUint32(28, sampleRate * numOfChannels * 2, true); // byte rate
            view.setUint16(32, numOfChannels * 2, true); // block align
            view.setUint16(34, 16, true); // bits per sample

            // "data" sub-chunk
            this.writeString(view, 36, "data");
            view.setUint32(40, length, true);

            // Write the PCM samples
            const dataOffset = 44;
            let offset = 0;

            for (let i = 0; i < buffer.length; i++) {
                for (let channel = 0; channel < numOfChannels; channel++) {
                    const sample = Math.max(
                        -1,
                        Math.min(1, buffer.getChannelData(channel)[i]),
                    );
                    const val = sample < 0 ? sample * 0x8000 : sample * 0x7fff;
                    view.setInt16(dataOffset + offset, val, true);
                    offset += 2;
                }
            }

            return new Blob([view], { type: "audio/wav" });
        },

        // Helper for writing strings to DataView
        writeString(view, offset, string) {
            for (let i = 0; i < string.length; i++) {
                view.setUint8(offset + i, string.charCodeAt(i));
            }
        },
    },
};
</script>
