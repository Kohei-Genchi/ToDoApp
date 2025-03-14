// resources/js/components/TaskStats.spec.js
import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import TaskStats from "./TaskStats.vue";

describe("TaskStats.vue", () => {
    it("renders correctly with provided props", () => {
        const wrapper = mount(TaskStats, {
            props: {
                totalCount: 10,
                completedCount: 3,
                pendingCount: 7,
            },
        });

        // Check if the component renders the correct statistics
        expect(wrapper.text()).toContain("全 10 タスク");
        expect(wrapper.text()).toContain("完了: 3");
        expect(wrapper.text()).toContain("未完了: 7");
    });

    it("renders zero counts correctly", () => {
        const wrapper = mount(TaskStats, {
            props: {
                totalCount: 0,
                completedCount: 0,
                pendingCount: 0,
            },
        });

        expect(wrapper.text()).toContain("全 0 タスク");
        expect(wrapper.text()).toContain("完了: 0");
        expect(wrapper.text()).toContain("未完了: 0");
    });
});
