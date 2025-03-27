/**
 * カテゴリAPI クライアント
 *
 * カテゴリ管理に関するすべてのAPI操作を処理する
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
    if (!id) {
        console.error(`Error: ${methodName} called without an ID`);
        throw new Error("カテゴリIDが指定されていません");
    }
    return true;
};
// In resources/js/api/category.js
export default {
    getCategories() {
        return axios.get("/api/categories", {
            headers: getCommonHeaders(),
        });
    },

    createCategory(categoryData) {
        return axios.post("/api/categories", categoryData, {
            headers: getCsrfHeaders(),
        });
    },

    updateCategory(id, categoryData) {
        validateId(id, "updateCategory");
        return axios.put(`/api/categories/${id}`, categoryData, {
            headers: getCsrfHeaders(),
        });
    },

    deleteCategory(id) {
        validateId(id, "deleteCategory");
        return axios.delete(`/api/categories/${id}`, {
            headers: getCsrfHeaders(),
        });
    },
};
