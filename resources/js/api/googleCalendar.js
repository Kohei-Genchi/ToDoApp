/**
 * Google Calendar API クライアント
 *
 * GoogleカレンダーとのAPI連携を処理するためのJavaScriptクライアント
 */
// resources/js/api/googleCalendar.js

/**
 * Google Calendar API クライアント
 *
 * GoogleカレンダーとのAPI連携を処理するためのJavaScriptクライアント
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

export default {
    /**
     * 認証状態を取得
     * @returns {Promise} APIレスポンス
     */
    getAuthStatus() {
        return axios.get("/api/google-calendar/auth-status", {
            headers: getCommonHeaders(),
        });
    },

    /**
     * Google認証URLを取得
     * @returns {Promise} APIレスポンス
     */
    getAuthUrl() {
        return axios.get("/api/google-calendar/auth-url", {
            headers: getCommonHeaders(),
        });
    },

    /**
     * カレンダー一覧を取得
     * @returns {Promise} APIレスポンス
     */
    getCalendars() {
        return axios.get("/api/google-calendar/calendars", {
            headers: getCommonHeaders(),
        });
    },

    /**
     * カレンダーとタスクを同期
     * @param {Object} syncOptions 同期オプション
     * @returns {Promise} APIレスポンス
     */
    syncCalendar(syncOptions) {
        return axios.post("/api/google-calendar/sync", syncOptions, {
            headers: getCsrfHeaders(),
        });
    },

    /**
     * Google連携を解除
     * @returns {Promise} APIレスポンス
     */
    disconnect() {
        return axios.post(
            "/api/google-calendar/disconnect",
            {},
            {
                headers: getCsrfHeaders(),
            },
        );
    },

    /**
     * Google認証ポップアップを開く
     * @returns {Promise} 認証結果のPromise
     */
    authenticateWithPopup() {
        return new Promise(async (resolve, reject) => {
            try {
                // 認証URLを取得
                const response = await this.getAuthUrl();
                const authUrl = response.data.auth_url;

                if (!authUrl) {
                    reject(new Error("認証URLの取得に失敗しました"));
                    return;
                }

                // ポップアップウィンドウを開く
                const width = 500;
                const height = 600;
                const left = (window.innerWidth - width) / 2;
                const top = (window.innerHeight - height) / 2;

                const popup = window.open(
                    authUrl,
                    "google-auth-popup",
                    `width=${width},height=${height},left=${left},top=${top}`,
                );

                if (
                    !popup ||
                    popup.closed ||
                    typeof popup.closed === "undefined"
                ) {
                    reject(
                        new Error("ポップアップウィンドウがブロックされました"),
                    );
                    return;
                }

                // ポップアップの状態をチェック
                const checkPopup = setInterval(() => {
                    if (popup.closed) {
                        clearInterval(checkPopup);

                        // 認証状態を確認
                        this.getAuthStatus()
                            .then((response) => {
                                if (response.data.is_authenticated) {
                                    resolve(response.data);
                                } else {
                                    reject(new Error("認証に失敗しました"));
                                }
                            })
                            .catch((error) => reject(error));
                    }
                }, 500);

                // タイムアウト（2分後）
                setTimeout(() => {
                    if (!popup.closed) {
                        popup.close();
                        clearInterval(checkPopup);
                        reject(new Error("認証がタイムアウトしました"));
                    }
                }, 120000);
            } catch (error) {
                reject(error);
            }
        });
    },
};
