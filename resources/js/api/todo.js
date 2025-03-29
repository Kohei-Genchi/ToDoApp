// resources/js/api/todo.js
// APIクライアントの修正 - ステータス更新処理を明示的に定義

import axios from "axios";

/**
 * Generate common request headers
 * @returns {Object} Common headers
 */
const getCommonHeaders = () => ({
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
});

/**
 * Generate CSRF-protected request headers
 * @returns {Object} CSRF-protected headers
 */
const getCsrfHeaders = () => {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    if (!csrfToken) {
        console.warn("CSRF token not found");
    }

    return {
        ...getCommonHeaders(),
        "X-CSRF-TOKEN": csrfToken,
        "Content-Type": "application/json",
    };
};

/**
 * Validate ID
 * @param {number|string} id ID to validate
 * @param {string} methodName Method name (for error message)
 * @returns {boolean} Whether ID is valid
 */
const validateId = (id, methodName) => {
    if (id === undefined || id === null) {
        console.error(`Error: ${methodName} called without an ID`);
        throw new Error("Task ID is missing");
    }
    return true;
};

export default {
    /**
     * Get tasks based on view and date
     * @param {string|Object} view View type or params object
     * @param {string} date Date in YYYY-MM-DD format
     * @param {Object} additionalParams Additional parameters
     * @returns {Promise} API response
     */
    getTasks(view, date, additionalParams = {}) {
        // Handle parameters - both object and direct parameter cases
        let params = {};

        if (typeof view === "object" && view !== null) {
            // If first argument is an object, use it as parameters
            params = { ...view };
        } else {
            // If parameters are passed directly
            params = {
                view: view || "today",
                date: date || new Date().toISOString().split("T")[0],
            };

            // Merge additional parameters if provided
            if (additionalParams && typeof additionalParams === "object") {
                params = { ...params, ...additionalParams };
            }
        }

        // Ensure user_id is sent as a string if present
        if (params.user_id !== undefined) {
            params.user_id = String(params.user_id);
        }

        console.log("API Request params:", params);

        return axios.get("/api/todos", {
            params: params,
            headers: getCommonHeaders(),
        });
    },

    /**
     * Get a specific task by ID
     * @param {number} id Task ID
     * @returns {Promise} API response
     */
    getTaskById(id) {
        validateId(id, "getTaskById");
        return axios.get(`/api/todos/${id}`, {
            headers: getCommonHeaders(),
        });
    },

    /**
     * Create a new task
     * @param {Object} taskData Task data
     * @returns {Promise} API response
     */
    createTask(taskData) {
        // Clone data to avoid modifying the original
        const data = { ...taskData };

        console.log("Creating task with data:", data);

        return axios.post("/api/todos", data, {
            headers: getCsrfHeaders(),
        });
    },

    /**
     * Update an existing task
     * @param {number} id Task ID
     * @param {Object} taskData Update data
     * @returns {Promise} API response
     */
    updateTask(id, taskData) {
        validateId(id, "updateTask");

        // Create a copy of the data to avoid modifying the original
        const data = { ...taskData };

        // Format dates properly to prevent timezone issues
        if (data.due_date && typeof data.due_date === "string") {
            // Ensure we're using YYYY-MM-DD format without any timezone adjustment
            if (data.due_date.includes("T")) {
                // If it's an ISO string, extract just the date part
                data.due_date = data.due_date.split("T")[0];
            }
        }

        // Handle due_time separately to prevent timezone issues
        if (data.due_time) {
            // If due_time is a full datetime, just keep the time portion
            if (
                typeof data.due_time === "string" &&
                data.due_time.includes("T")
            ) {
                const timePart = data.due_time.split("T")[1].substring(0, 5); // Get HH:MM
                data.due_time = timePart;
            }
        }

        console.log("Updating task with data:", data);

        // Use POST with _method param for Laravel's PUT handling
        return axios.post(
            `/api/todos/${id}`,
            {
                ...data,
                _method: "PUT",
            },
            {
                headers: getCsrfHeaders(),
            },
        );
    },

    /**
     * Update only task status
     * @param {number} id Task ID
     * @param {string} status New status
     * @returns {Promise} API response
     */
    updateTaskStatus(id, status) {
        validateId(id, "updateTaskStatus");

        console.log(`Updating task ${id} status to ${status}`);

        return axios.post(
            `/api/todos/${id}`,
            {
                _method: "PUT",
                status: status,
            },
            {
                headers: getCsrfHeaders(),
            },
        );
    },

    /**
     * Toggle task status between completed and pending
     * @param {number} id Task ID
     * @returns {Promise} API response
     */
    toggleTask(id) {
        validateId(id, "toggleTask");

        console.log("Toggling task status:", id);

        return axios.patch(
            `/api/todos/${id}/toggle`,
            {},
            {
                headers: getCsrfHeaders(),
            },
        );
    },

    /**
     * Delete a task
     * @param {number} id Task ID
     * @param {boolean} deleteRecurring Whether to delete recurring tasks
     * @returns {Promise} API response
     */
    deleteTask(id, deleteRecurring = false) {
        validateId(id, "deleteTask");

        console.log("Deleting task:", id, "Delete recurring:", deleteRecurring);

        return axios.delete(`/api/todos/${id}`, {
            headers: getCsrfHeaders(),
            params: { delete_recurring: deleteRecurring ? 1 : 0 },
        });
    },
    getTasksWithPermissions(view, date, additionalParams = {}) {
        let params = {
            view: view || "today",
            date: date || new Date().toISOString().split("T")[0],
            include_permissions: true, // 権限情報を含める
        };

        // Merge additional parameters if provided
        if (additionalParams && typeof additionalParams === "object") {
            params = { ...params, ...additionalParams };
        }

        return axios.get("/api/todos", {
            params: params,
            headers: getCommonHeaders(),
        });
    },

    /**
     * Get all shared tasks
     * @returns {Promise} API response
     */
    getSharedTasks() {
        return axios.get("/api/shared/categories/tasks", {
            headers: getCommonHeaders(),
        });
    },
};
