/**
 * Task Sharing API Client
 *
 * Handles all API operations related to task sharing
 */
import axios from "axios";

/**
 * Common headers for all requests
 */
const getCommonHeaders = () => ({
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
});

/**
 * CSRF protected headers for POST/PUT/DELETE requests
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

export default {
    /**
     * Get users with whom a task is shared
     * @param {number} taskId - The task ID
     * @returns {Promise} - API response
     */
    getSharedUsers(taskId) {
        return axios.get(`/api/tasks/${taskId}/shares`, {
            headers: getCommonHeaders(),
        });
    },

    /**
     * Request to share a task with a user
     * @param {number} taskId - The task ID
     * @param {string} email - The email of the user to share with
     * @param {string} permission - The permission level ('view' or 'edit')
     * @returns {Promise} - API response
     */
    shareTask(taskId, email, permission = "view") {
        return axios.post(
            `/api/tasks/${taskId}/shares`,
            {
                email,
                permission,
            },
            {
                headers: getCsrfHeaders(),
            },
        );
    },

    /**
     * Update sharing permission for a user
     * @param {number} taskId - The task ID
     * @param {number} userId - The user ID
     * @param {string} permission - The permission level ('view' or 'edit')
     * @returns {Promise} - API response
     */
    updatePermission(taskId, userId, permission) {
        return axios.put(
            `/api/tasks/${taskId}/shares/${userId}`,
            {
                permission,
            },
            {
                headers: getCsrfHeaders(),
            },
        );
    },

    /**
     * Stop sharing a task with a user
     * @param {number} taskId - The task ID
     * @param {number} userId - The user ID
     * @returns {Promise} - API response
     */
    unshareTask(taskId, userId) {
        return axios.delete(`/api/tasks/${taskId}/shares/${userId}`, {
            headers: getCsrfHeaders(),
        });
    },

    /**
     * Get tasks shared with the authenticated user
     * @returns {Promise} - API response
     */
    getSharedWithMe() {
        return axios.get("/api/shared-with-me", {
            headers: getCommonHeaders(),
        });
    },

    /**
     * Get pending share requests (sent and received)
     * @returns {Promise} - API response
     */
    getShareRequests() {
        return axios.get("/api/share-requests", {
            headers: getCommonHeaders(),
        });
    },

    /**
     * Cancel a pending share request
     * @param {number} requestId - The share request ID
     * @returns {Promise} - API response
     */
    cancelShareRequest(requestId) {
        return axios.delete(`/api/share-requests/${requestId}`, {
            headers: getCsrfHeaders(),
        });
    },

    /**
     * Approve a share request
     * @param {string} token - The share request token
     * @returns {Promise} - API response
     */
    approveShareRequest(token) {
        return axios.get(`/api/share-requests/${token}/approve`, {
            headers: getCommonHeaders(),
        });
    },

    /**
     * Reject a share request
     * @param {string} token - The share request token
     * @returns {Promise} - API response
     */
    rejectShareRequest(token) {
        return axios.get(`/api/share-requests/${token}/reject`, {
            headers: getCommonHeaders(),
        });
    },
};
