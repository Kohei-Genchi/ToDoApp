<template>
    <div
        class="fixed inset-0 flex items-center justify-center z-10"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div
            class="absolute inset-0 bg-black bg-opacity-40"
            @click="$emit('close')"
        ></div>

        <!-- Modal Content -->
        <div
            class="bg-white rounded-lg shadow-lg w-full max-w-xl relative z-10 flex flex-col max-h-[90vh]"
        >
            <!-- Header -->
            <div
                class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center"
            >
                <h3 id="modal-title" class="text-lg font-medium text-gray-800">
                    {{ mode === "add" ? "新しいタスク" : "タスクを編集" }}
                </h3>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-500"
                >
                    <svg
                        class="h-5 w-5"
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

            <!-- Body -->
            <div class="p-4 overflow-y-auto">
                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Title -->
                    <div>
                        <label
                            for="title"
                            class="block text-sm font-medium text-gray-700"
                        >
                            タイトル<span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="title"
                            v-model="form.title"
                            required
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <!-- Date -->
                    <div>
                        <label
                            for="due_date"
                            class="block text-sm font-medium text-gray-700"
                            >期限日</label
                        >
                        <input
                            type="date"
                            id="due_date"
                            v-model="form.due_date"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <!-- Time Range -->
                    <div>
                        <div class="flex items-center mb-2">
                            <div class="w-6 h-6 mr-2">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-gray-500"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="flex items-center space-x-2 flex-1">
                                <input
                                    type="time"
                                    v-model="form.start_time"
                                    class="block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    @focus="setCurrentTimeIfEmpty('start_time')"
                                    @change="ensureEndTimeIsAfterStart"
                                />
                                <span class="text-gray-500">→</span>
                                <input
                                    type="time"
                                    v-model="form.end_time"
                                    class="block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    @change="ensureEndTimeIsAfterStart"
                                />
                                <button
                                    type="button"
                                    @click="clearTimes"
                                    class="text-gray-500 hover:text-gray-700"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Duration Buttons -->
                        <div class="flex justify-between space-x-2 ml-8">
                            <button
                                type="button"
                                @click="setDuration(15)"
                                class="px-3 py-1 text-xs rounded-full border"
                                :class="
                                    activeDuration === 15
                                        ? 'bg-pink-200 border-pink-300 text-pink-800'
                                        : 'bg-gray-100 border-gray-200 text-gray-800 hover:bg-gray-200'
                                "
                            >
                                15分
                            </button>
                            <button
                                type="button"
                                @click="setDuration(30)"
                                class="px-3 py-1 text-xs rounded-full border"
                                :class="
                                    activeDuration === 30
                                        ? 'bg-pink-200 border-pink-300 text-pink-800'
                                        : 'bg-gray-100 border-gray-200 text-gray-800 hover:bg-gray-200'
                                "
                            >
                                30分
                            </button>
                            <button
                                type="button"
                                @click="setDuration(60)"
                                class="px-3 py-1 text-xs rounded-full border"
                                :class="
                                    activeDuration === 60
                                        ? 'bg-pink-200 border-pink-300 text-pink-800'
                                        : 'bg-gray-100 border-gray-200 text-gray-800 hover:bg-gray-200'
                                "
                            >
                                1時間
                            </button>
                            <button
                                type="button"
                                @click="setDuration(120)"
                                class="px-3 py-1 text-xs rounded-full border"
                                :class="
                                    activeDuration === 120
                                        ? 'bg-pink-200 border-pink-300 text-pink-800'
                                        : 'bg-gray-100 border-gray-200 text-gray-800 hover:bg-gray-200'
                                "
                            >
                                2時間
                            </button>
                        </div>
                    </div>

                    <!-- Category Selection -->
                    <div>
                        <div class="flex justify-between items-center">
                            <label
                                for="category_id"
                                class="block text-sm font-medium text-gray-700"
                                >カテゴリー</label
                            >
                            <button
                                type="button"
                                @click="showCategoryInput = !showCategoryInput"
                                class="text-xs text-blue-600 hover:text-blue-800"
                            >
                                {{
                                    showCategoryInput
                                        ? "戻る"
                                        : "新規カテゴリー"
                                }}
                            </button>
                        </div>
                    </div>

                    <!-- Category Select -->
                    <div v-if="!showCategoryInput" class="mt-1">
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">カテゴリーなし</option>
                            <option
                                v-for="category in categoriesArray"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>

                        <!-- Selected Category Display -->
                        <div
                            v-if="form.category_id && getSelectedCategory"
                            class="mt-1 p-1 bg-gray-50 rounded-md text-xs flex items-center"
                        >
                            <div
                                class="w-3 h-3 rounded-full mr-1"
                                :style="{
                                    backgroundColor: getSelectedCategory.color,
                                }"
                            ></div>
                            <span>{{ getSelectedCategory.name }}</span>
                        </div>
                    </div>

                    <!-- New Category Form -->
                    <div
                        v-if="showCategoryInput"
                        class="mt-3 overflow-hidden transition-all duration-300 transform"
                    >
                        <div
                            class="p-3 rounded-xl bg-white shadow-sm border border-gray-100"
                        >
                            <!-- Input area with floating design -->
                            <div class="flex items-center gap-2 p-2">
                                <!-- Color selector with pill design -->
                                <div class="relative">
                                    <div
                                        class="w-10 h-10 rounded-full flex items-center justify-center cursor-pointer shadow-sm transition-transform duration-200 hover:scale-105 active:scale-95"
                                        :style="{
                                            backgroundColor: newCategory.color,
                                        }"
                                    >
                                        <input
                                            type="color"
                                            v-model="newCategory.color"
                                            class="w-10 h-10 rounded-full border-0 overflow-hidden cursor-pointer p-0"
                                        />
                                    </div>
                                </div>

                                <!-- Text input with minimalist design -->
                                <div class="relative flex-grow">
                                    <input
                                        type="text"
                                        v-model="newCategory.name"
                                        placeholder="カテゴリー名"
                                        class="flex-grow px-3 py-2 bg-gray-50 rounded-lg focus:outline-none"
                                    />
                                    <div
                                        class="absolute bottom-0 left-0 w-full h-1 transition-all duration-300 transform scale-x-0 origin-left"
                                        :class="{
                                            'scale-x-100':
                                                newCategory.name.length > 0,
                                        }"
                                        :style="{
                                            backgroundColor: newCategory.color,
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Action buttons with subtle hover effects -->
                            <div class="flex justify-end items-center gap-2">
                                <button
                                    type="button"
                                    @click="showCategoryInput = false"
                                    class="px-4 py-2 text-sm text-gray-500 rounded-lg hover:bg-gray-100 transition-all duration-200"
                                >
                                    キャンセル
                                </button>
                                <button
                                    type="button"
                                    @click="createCategory"
                                    :disabled="!newCategory.name"
                                    class="px-3 py-2 rounded-lg text-white"
                                    :style="{
                                        backgroundColor:
                                            newCategory.color || '#4F46E5',
                                    }"
                                >
                                    追加
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Recurrence Type -->
                    <div>
                        <label
                            for="recurrence_type"
                            class="block text-sm font-medium text-gray-700"
                            >繰り返し</label
                        >
                        <select
                            id="recurrence_type"
                            v-model="form.recurrence_type"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="none">繰り返しなし</option>
                            <option value="daily">毎日</option>
                            <option value="weekly">毎週</option>
                            <option value="monthly">毎月</option>
                        </select>
                    </div>

                    <!-- Recurrence End Date -->
                    <div
                        v-if="
                            form.recurrence_type &&
                            form.recurrence_type !== 'none'
                        "
                        class="pb-1"
                    >
                        <label
                            for="recurrence_end_date"
                            class="block text-sm font-medium text-gray-700"
                            >繰り返し終了日</label
                        >
                        <input
                            type="date"
                            id="recurrence_end_date"
                            v-model="form.recurrence_end_date"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        />
                        <p class="mt-0.5 text-xs text-gray-500">
                            ※指定しない場合は1ヶ月間繰り返されます
                        </p>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-4 py-2 border-t border-gray-200 mt-auto">
                <div class="flex justify-between items-center">
                    <!-- Delete Button (edit mode only) -->
                    <div v-if="mode === 'edit'">
                        <button
                            type="button"
                            @click="deleteTask"
                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded border border-red-300 text-red-700 bg-white hover:bg-red-50"
                        >
                            削除
                        </button>
                    </div>
                    <div v-else class="flex-1"></div>

                    <!-- Cancel/Save Buttons -->
                    <div class="flex gap-2">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50"
                        >
                            キャンセル
                        </button>
                        <button
                            type="button"
                            @click="submitForm"
                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded border border-transparent text-white bg-blue-600 hover:bg-blue-700"
                        >
                            {{ mode === "add" ? "追加" : "保存" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, watch, onMounted, nextTick, computed } from "vue";

export default {
    props: {
        mode: {
            type: String,
            default: "add",
            validator: (value) => ["add", "edit"].includes(value),
        },
        todoId: {
            type: Number,
            default: null,
        },
        todoData: {
            type: Object,
            default: () => ({
                title: "",
                description: "",
                due_date: "",
                due_time: "",
                category_id: "",
                recurrence_type: "none",
                recurrence_end_date: "",
            }),
        },
        categories: {
            type: Array,
            default: () => [],
        },
        currentDate: {
            type: String,
            default: "",
        },
        currentView: {
            type: String,
            default: "today",
        },
    },

    emits: ["close", "submit", "delete", "category-created"],

    setup(props, { emit }) {
        // ===============================
        // State
        // ===============================
        // Track active duration button
        const activeDuration = ref(null);

        // Form data
        const form = reactive({
            title: "",
            description: "",
            due_date: "",
            start_time: "",
            end_time: "",
            category_id: "",
            recurrence_type: "none",
            recurrence_end_date: "",
        });

        // New category form
        const showCategoryInput = ref(false);
        const newCategory = reactive({
            name: "",
            color: "#3b82f6",
        });

        // ===============================
        // Computed Properties
        // ===============================

        // Format categories for select dropdown
        const categoriesArray = computed(() => {
            if (!props.categories) return [];

            if (Array.isArray(props.categories)) {
                return props.categories.map((cat) => ({
                    ...cat,
                    id: String(cat.id),
                }));
            }

            return [];
        });

        // Get selected category
        const getSelectedCategory = computed(() => {
            if (!form.category_id) return null;

            return (
                categoriesArray.value.find(
                    (cat) => String(cat.id) === String(form.category_id),
                ) || null
            );
        });

        // ===============================
        // Methods
        // ===============================

        // Format date string to YYYY-MM-DD
        function formatDateString(dateStr) {
            if (!dateStr) return "";

            try {
                // Check if already in YYYY-MM-DD format
                if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
                    return dateStr;
                }

                // Convert ISO date string to local date format
                const date = new Date(dateStr);
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
            } catch (e) {
                return "";
            }
        }

        // Set current time when field is empty and focused
        function setCurrentTimeIfEmpty(field) {
            if (!form[field]) {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, "0");
                const minutes = String(now.getMinutes()).padStart(2, "0");
                form[field] = `${hours}:${minutes}`;

                // If setting start_time, also set end_time 30 min later
                if (field === "start_time" && !form.end_time) {
                    setDuration(30);
                }
            }
        }

        // Clear both time fields and active duration
        function clearTimes() {
            form.start_time = "";
            form.end_time = "";
            activeDuration.value = null;
        }

        // Set duration and calculate end time
        function setDuration(minutes) {
            activeDuration.value = minutes;

            // If start time is not set, set it to current time
            if (!form.start_time) {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, "0");
                const mins = String(now.getMinutes()).padStart(2, "0");
                form.start_time = `${hours}:${mins}`;
            }

            // Calculate end time based on start time + duration
            if (form.start_time) {
                const [hours, mins] = form.start_time.split(":").map(Number);
                const endTime = new Date();
                endTime.setHours(hours, mins + minutes);
                form.end_time = `${String(endTime.getHours()).padStart(2, "0")}:${String(endTime.getMinutes()).padStart(2, "0")}`;
            }
        }

        // Ensure end time is after start time
        function ensureEndTimeIsAfterStart() {
            if (form.start_time && form.end_time) {
                const [startHours, startMins] = form.start_time
                    .split(":")
                    .map(Number);
                const [endHours, endMins] = form.end_time
                    .split(":")
                    .map(Number);

                const startDate = new Date();
                startDate.setHours(startHours, startMins, 0);

                const endDate = new Date();
                endDate.setHours(endHours, endMins, 0);

                // If end time is before or equal to start time, set end time to start time + 30 minutes
                if (endDate <= startDate) {
                    endDate.setHours(startHours, startMins + 30);
                    form.end_time = `${String(endDate.getHours()).padStart(2, "0")}:${String(endDate.getMinutes()).padStart(2, "0")}`;
                    activeDuration.value = 30;
                } else {
                    // Calculate the difference in minutes to determine the active duration
                    const diffMs = endDate - startDate;
                    const diffMins = Math.round(diffMs / 60000);

                    if (diffMins === 15) activeDuration.value = 15;
                    else if (diffMins === 30) activeDuration.value = 30;
                    else if (diffMins === 60) activeDuration.value = 60;
                    else if (diffMins === 120) activeDuration.value = 120;
                    else activeDuration.value = null;
                }
            }
        }

        // Initialize form data based on todoData prop
        function initializeForm() {
            if (!props.todoData) {
                // Set defaults for new task
                if (props.mode === "add") {
                    form.title = "";
                    form.description = "";
                    form.due_date = props.currentDate || "";
                    form.start_time = "";
                    form.end_time = "";
                    form.category_id = "";
                    form.recurrence_type = "none";
                    form.recurrence_end_date = "";
                    activeDuration.value = null;
                }
                return;
            }

            // Set the title and description
            form.title = props.todoData.title || "";
            form.description = props.todoData.description || "";

            // Format dates properly for form inputs
            form.due_date = props.todoData.due_date
                ? formatDateString(props.todoData.due_date)
                : "";

            // Handle time from ISO string or existing due_time
            if (props.todoData.due_time) {
                if (
                    typeof props.todoData.due_time === "string" &&
                    props.todoData.due_time.includes("T")
                ) {
                    const timeDate = new Date(props.todoData.due_time);
                    form.start_time = `${String(timeDate.getHours()).padStart(2, "0")}:${String(timeDate.getMinutes()).padStart(2, "0")}`;
                } else {
                    // Try to extract just time part if it's in HH:MM:SS format
                    const timeParts = props.todoData.due_time.split(":");
                    if (timeParts.length >= 2) {
                        form.start_time = `${timeParts[0]}:${timeParts[1]}`;
                    } else {
                        form.start_time = props.todoData.due_time;
                    }
                }

                // Set end_time 30 minutes after start_time if available
                if (form.start_time) {
                    setDuration(30);
                }
            } else {
                form.start_time = "";
                form.end_time = "";
                activeDuration.value = null;
            }

            // Make sure category_id is properly handled (as a string for select element)
            if (
                props.todoData.category_id !== null &&
                props.todoData.category_id !== undefined
            ) {
                form.category_id = String(props.todoData.category_id);
            } else {
                form.category_id = "";
            }

            form.recurrence_type = props.todoData.recurrence_type || "none";
            form.recurrence_end_date = props.todoData.recurrence_end_date
                ? formatDateString(props.todoData.recurrence_end_date)
                : "";
            // console.log("モーダルにデータをロード:", props.todoData);
        }

        // Submit form
        function submitForm() {
            if (!form.title.trim()) {
                alert("タイトルを入力してください");
                return;
            }

            // データ準備
            const formData = { ...form };

            // Set due_time from start_time for compatibility
            if (formData.start_time) {
                formData.due_time = formData.start_time;
            } else {
                formData.due_time = null;
            }

            if (formData.category_id !== "" && formData.category_id !== null) {
                formData.category_id = Number(formData.category_id);
            } else {
                formData.category_id = null;
            }

            // Include task ID in the submission for edit mode
            if (props.mode === "edit" && props.todoId) {
                formData.id = props.todoId;
            }

            // Find the complete category object for this category_id
            const categoryObject =
                formData.category_id !== null
                    ? categoriesArray.value.find(
                          (c) => Number(c.id) === formData.category_id,
                      )
                    : null;

            // Update existing todo in the list with category data
            const updatedTodo = {
                ...formData,
                category: categoryObject,
            };

            console.log("フォーム送信データ:", formData);

            emit("submit", updatedTodo);
            emit("close");
        }

        async function createCategory() {
            if (!newCategory.name.trim()) {
                alert("カテゴリー名を入力してください");
                return;
            }

            try {
                const response = await axios.post("/api/categories", {
                    name: newCategory.name.trim(),
                    color: newCategory.color,
                });

                // レスポンスデータに直接アクセス可能
                const data = response.data;

                // 新しいカテゴリを選択
                form.category_id = String(data.id);

                // カテゴリフォームを非表示
                showCategoryInput.value = false;

                // カテゴリリストを更新
                emit("category-created");

                // フォームをリセット
                newCategory.name = "";
                newCategory.color = "#3b82f6";

                // 成功メッセージ
                alert("カテゴリーが作成されました");
            } catch (error) {
                // エラーハンドリングも簡潔に
                console.error("カテゴリー作成エラー:", error);
                alert(
                    "カテゴリーの作成に失敗しました: " +
                        (error.response?.data?.message || error.message),
                );
            }
        }

        // Delete task
        function deleteTask() {
            if (!props.todoId) {
                return;
            }

            // Use confirm dialog if task has recurrence
            const shouldDeleteAllRecurring =
                form.recurrence_type && form.recurrence_type !== "none";

            emit("delete", props.todoId, shouldDeleteAllRecurring);
        }

        // ===============================
        // Watchers & Lifecycle Hooks
        // ===============================

        // Watch for prop changes and initialize form
        watch(
            () => props.todoData,
            () => {
                initializeForm();
            },
            { immediate: true, deep: true },
        );

        // Watch for mode changes
        watch(
            () => props.mode,
            () => {
                initializeForm();
            },
            { immediate: true },
        );

        // Set default date based on current view
        watch(
            () => props.currentDate,
            (newDate) => {
                if (props.mode === "add" && !form.due_date && newDate) {
                    form.due_date = formatDateString(newDate);
                }
            },
            { immediate: true },
        );

        // Initialize when component mounts
        onMounted(() => {
            // If it's a new task, set current time as default
            if (props.mode === "add" && !form.start_time) {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, "0");
                const minutes = String(now.getMinutes()).padStart(2, "0");
                form.start_time = `${hours}:${minutes}`;

                // Set default duration to 30 minutes
                setDuration(30);
            }
        });

        return {
            form,
            showCategoryInput,
            newCategory,
            categoriesArray,
            getSelectedCategory,
            activeDuration,
            setDuration,
            submitForm,
            createCategory,
            deleteTask,
            setCurrentTimeIfEmpty,
            clearTimes,
            ensureEndTimeIsAfterStart,
        };
    },
};
</script>
