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
    getTasks(view, date) {
        return axios.get("/api/todos", {
            params: { view, date },
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
    updateTask(id, taskData) {
        validateId(id, "updateTask");

        // Laravel用のPUT操作（POST + _method）
        return axios.post(
            `/api/todos/${id}`,
            {
                ...taskData,
                _method: "PUT",
            },
            {
                headers: getCsrfHeaders(),
            },
        );
    },

    /**
     * タスクの完了状態を切り替え
     * @param {number} id タスクID
     * @returns {Promise} APIレスポンス
     */
    toggleTask(id) {
        validateId(id, "toggleTask");
        return axios.patch(
            `/api/todos/${id}/toggle`,
            {},
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
