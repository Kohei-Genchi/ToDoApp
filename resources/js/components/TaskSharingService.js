import axios from "axios";

/**
 * Service for handling task sharing operations
 */
export default {
    /**
     * Share tasks by location
     * @param {string} location - The location to share (INBOX, TODAY, SCHEDULED)
     * @param {string} email - Email of the recipient
     * @param {string} permission - Permission level (view, edit)
     * @param {boolean} slackAuthRequired - Whether Slack authentication is required
     * @returns {Promise} - API response
     */
    async shareTasksByLocation(
        location,
        email,
        permission,
        slackAuthRequired = true,
    ) {
        try {
            // 1. Fetch tasks by location
            const tasksResponse = await axios.get("/api/todos", {
                params: {
                    location: location,
                    status: "pending",
                },
            });

            const tasks = tasksResponse.data || [];

            if (tasks.length === 0) {
                throw new Error("共有するタスクがありません");
            }

            // 2. Create a category for these tasks
            const now = new Date();
            const dateStr = `${now.getFullYear()}/${(now.getMonth() + 1).toString().padStart(2, "0")}/${now.getDate().toString().padStart(2, "0")}`;
            const locationName = this.getLocationDisplayName(location);
            const categoryName = `共有: ${locationName} (${dateStr})`;

            const categoryResponse = await axios.post("/api/categories", {
                name: categoryName,
                color: this.getColorForLocation(location),
            });

            const categoryId = categoryResponse.data.id;

            // 3. Add tasks to this category
            const updatePromises = tasks.map((task) =>
                axios.put(`/api/todos/${task.id}`, {
                    category_id: categoryId,
                }),
            );

            await Promise.all(updatePromises);

            // 4. Share the category
            const shareResponse = await axios.post(
                `/api/categories/${categoryId}/shares`,
                {
                    email: email,
                    permission: permission,
                    slack_auth_required: slackAuthRequired,
                },
            );

            return {
                success: true,
                taskCount: tasks.length,
                message: `${tasks.length}件のタスクを共有しました`,
                data: shareResponse.data,
            };
        } catch (error) {
            console.error("Error sharing tasks by location:", error);
            throw error;
        }
    },

    /**
     * Get a friendly display name for a location
     * @param {string} location - The location code (INBOX, TODAY, SCHEDULED)
     * @returns {string} - Friendly display name
     */
    getLocationDisplayName(location) {
        switch (location) {
            case "INBOX":
                return "未分類";
            case "TODAY":
                return "今日";
            case "SCHEDULED":
                return "予定";
            default:
                return location;
        }
    },

    /**
     * Get a color code for a location
     * @param {string} location - The location code (INBOX, TODAY, SCHEDULED)
     * @returns {string} - HEX color code
     */
    getColorForLocation(location) {
        switch (location) {
            case "INBOX":
                return "#4A5568"; // Gray
            case "TODAY":
                return "#3182CE"; // Blue
            case "SCHEDULED":
                return "#805AD5"; // Purple
            default:
                return "#3182CE"; // Default blue
        }
    },
};
