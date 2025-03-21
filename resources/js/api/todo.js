/**
 * Todo API クライアント
 *
 * タスク管理に関するすべてのAPI操作を処理する
 */
import axios from "axios";

/**
 * 共通のリクエストヘッダーを生成
 * @returns {Object} 共通ヘッダー
 */
const getCommonHeaders = () => ({
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
});

/**
 * CSRF保護されたリクエストヘッダーを生成
 * @returns {Object} CSRF保護されたヘッダー
 */
const getCsrfHeaders = () => {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    if (!csrfToken) {
        console.warn("CSRF tokenが見つかりません");
    }

    return {
        ...getCommonHeaders(),
        "X-CSRF-TOKEN": csrfToken,
        "Content-Type": "application/json",
    };
};

/**
 * IDの有効性を検証
 * @param {number|string} id 検証するID
 * @param {string} methodName 呼び出し元メソッド名（エラーメッセージ用）
 * @returns {boolean} IDが有効かどうか
 */
const validateId = (id, methodName) => {
    if (id === undefined || id === null) {
        console.error(`Error: ${methodName} called without an ID`);
        throw new Error("タスクIDが指定されていません");
    }
    return true;
};

export default {
    /**
     * ビューと日付に基づいてタスク一覧を取得
     * @param {string} view ビュータイプ（today, scheduled, inbox, calendar, date）
     * @param {string} date 日付（YYYY-MM-DD形式）
     * @returns {Promise} APIレスポンス
     */
    getTasks(view, date, additionalParams = {}) {
        // パラメータを処理 - オブジェクトと直接パラメータの両方のケースを処理
        let params = {};

        if (typeof view === "object" && view !== null) {
            // 最初の引数がオブジェクトの場合、それをパラメータとして使用
            params = { ...view };
        } else {
            // パラメータが直接渡された場合
            params = {
                view: view || "today",
                date: date || new Date().toISOString().split("T")[0],
            };

            // 追加パラメータがある場合はマージ
            if (additionalParams && typeof additionalParams === "object") {
                params = { ...params, ...additionalParams };
            }
        }

        // user_id パラメータが存在する場合、確実に数値/文字列として送信
        if (params.user_id !== undefined) {
            // 明示的に文字列に変換（数値の場合も文字列になる）
            params.user_id = String(params.user_id);
        }

        console.log("API Request params:", params);

        return axios.get("/api/todos", {
            params: params,
            headers: getCommonHeaders(),
        });
    },
    /**
     * 特定のタスクをIDで取得
     * @param {number} id タスクID
     * @returns {Promise} APIレスポンス
     */
    getTaskById(id) {
        validateId(id, "getTaskById");
        return axios.get(`/api/todos/${id}`, {
            headers: getCommonHeaders(),
        });
    },

    /**
     * 新しいタスクを作成
     * @param {Object} taskData タスクデータ
     * @returns {Promise} APIレスポンス
     */
    createTask(taskData) {
        return axios.post("/api/todos", taskData, {
            headers: getCsrfHeaders(),
        });
    },

    /**
     * 既存のタスクを更新
     * @param {number} id タスクID
     * @param {Object} taskData 更新するタスクデータ
     * @returns {Promise} APIレスポンス
     */

    //Networkタブで /api/todos/{id} へのリクエストを確認
    // In resources/js/api/todo.js
    // Find the updateTask method and replace it with this improved version:

    /**
     * Update existing task
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

        console.log("Sending task update with data:", data);

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
     * タスクを完全に削除
     * @param {number} id タスクID
     * @param {boolean} deleteRecurring 繰り返しタスクも削除するかどうか
     * @returns {Promise} APIレスポンス
     */
    deleteTask(id, deleteRecurring = false) {
        validateId(id, "deleteTask");
        return axios.delete(`/api/todos/${id}`, {
            headers: getCsrfHeaders(),
            params: { delete_recurring: deleteRecurring ? 1 : 0 },
        });
    },
};
