/**
 * Global Share API Client
 *
 * Handles all API operations related to global task sharing
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
     * Get users with whom the authenticated user has globally shared tasks
     * @returns {Promise} - API response
     */
    getGlobalShares() {
        return axios.get(`/api/global-shares`, {
            headers: getCommonHeaders(),
        });
    },

    /**
     * Share all tasks globally with a user
     * @param {string} email - The email of the user to share with
     * @param {string} permission - The permission level ('view' or 'edit')
     * @returns {Promise} - API response
     */
    addGlobalShare(email, permission = "view") {
        return axios.post(
            `/api/global-shares`,
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
     * Update global sharing permission for a user
     * @param {number} globalShareId - The global share ID
     * @param {string} permission - The permission level ('view' or 'edit')
     * @returns {Promise} - API response
     */
    updateGlobalSharePermission(globalShareId, permission) {
        return axios.put(
            `/api/global-shares/${globalShareId}`,
            {
                permission,
            },
            {
                headers: getCsrfHeaders(),
            },
        );
    },

    /**
     * Stop sharing globally with a user
     * @param {number} globalShareId - The global share ID
     * @returns {Promise} - API response
     */
    removeGlobalShare(globalShareId) {
        return axios.delete(`/api/global-shares/${globalShareId}`, {
            headers: getCsrfHeaders(),
        });
    },

    /**
     * Get all tasks shared with the authenticated user via global sharing
     * @returns {Promise} - API response
     */
    getGloballySharedWithMe() {
        return axios.get("/api/global-shared-with-me", {
            headers: getCommonHeaders(),
        });
    },
};
