import { defineConfig } from "vitest/config";
import vue from "@vitejs/plugin-vue";
import { resolve } from "path";

export default defineConfig({
    plugins: [vue()],
    test: {
        globals: true,
        environment: "happy-dom",
        include: [
            "resources/js/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}",
        ],
        exclude: ["node_modules", "vendor"],
    },
    resolve: {
        alias: {
            "@": resolve(__dirname, "./resources/js"),
            // Add an alias for the components directory
            "@components": resolve(__dirname, "./resources/js/components"),
            // Alias for Vue
            vue: "vue/dist/vue.esm-bundler.js",
        },
    },
});
