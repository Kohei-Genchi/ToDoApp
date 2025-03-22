import axios from "axios";

//すべてのリクエスト用の共通ヘッダー
const getCommonHeaders = () => ({
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
});

//POST/PUT/DELETE リクエスト用のCSRF保護ヘッダー
const getCsrfToken = () => {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || null
    );
};
//CSRF保護付きのリクエストヘッダーを取得
const getCsrfHeaders = () => {
    const csrfToken = getCsrfToken();

    if (!csrfToken) {
        console.warn("CSRFトークンが見つかりません");
    }

    return {
        ...getCommonHeaders(),
        ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
        "Content-Type": "application/json",
    };
};

const fetchWithErrorHandling = async (
    endpoint,
    options = {},
    errorPrefix = "API呼び出し中のエラー",
) => {
    try {
        const response = await axios.get(endpoint, {
            headers: getCommonHeaders(),
            // キャッシュを防ぐためのタイムスタンプパラメータを追加
            params: { _t: new Date().getTime(), ...options.params },
            ...options,
        });

        // レスポンスが空でないことを確認
        if (!response.data) {
            console.warn(`空のレスポンス: ${endpoint}`);
            return { data: [] };
        }
        return response;
    } catch (error) {
        console.error(`${errorPrefix}: ${error}`);
        // エラー時には空の配列を返す
        return { data: [] };
    }
};

export default {
    // 自分が共有した相手一覧の取得
    getGlobalShares() {
        return fetchWithErrorHandling(
            "/api/global-shares",
            {},
            "グローバル共有一覧取得中のエラー",
        );
    },
    //新しいユーザーとの共有
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

    // 共有権限の変更（閲覧のみ/編集可能）
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

    //共有の解除
    removeGlobalShare(globalShareId) {
        return axios.delete(`/api/global-shares/${globalShareId}`, {
            headers: getCsrfHeaders(),
        });
    },

    //自分に共有されているタスク一覧の取得
    getGloballySharedWithMe() {
        return fetchWithErrorHandling(
            "/api/global-shared-with-me",
            {},
            "グローバル共有タスク取得中のエラー",
        );
    },
};
