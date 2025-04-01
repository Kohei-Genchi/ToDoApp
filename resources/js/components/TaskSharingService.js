import axios from "axios";

/**
 * Service for handling task sharing operations
 */
export default {
    /**
     * Share pending tasks (simplified method)
     * @param {string} email - Email of the recipient
     * @param {string} permission - Permission level (view, edit)
     * @param {boolean} slackAuthRequired - Whether Slack authentication is required
     * @returns {Promise} - API response
     */
    async shareTasks(email, permission, slackAuthRequired = true) {
        try {
            // 1. Fetch pending tasks
            const tasksResponse = await axios.get("/api/todos", {
                params: {
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
            const categoryName = `共有タスク (${dateStr})`;

            const categoryResponse = await axios.post("/api/categories", {
                name: categoryName,
                color: "#3182CE", // Blue
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
            console.error("Error sharing tasks:", error);
            throw error;
        }
    },
};
