// In resources/js/bootstrap.js

import axios from "axios";

window.axios = axios;
axios.defaults.withCredentials = true;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

axios.interceptors.request.use(
    (config) => {
        // Get the CSRF token from the meta tag
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");
        if (token) {
            config.headers["X-CSRF-TOKEN"] = token;
        }
        return config;
    },
    (error) => Promise.reject(error),
);
