// resources/js/components/EmptyState.spec.js
import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import EmptyState from "./EmptyState.vue";

describe("EmptyState.vue", () => {
    it("renders the empty state message correctly", () => {
        const wrapper = mount(EmptyState);

        // Check if component renders the correct messages
        expect(wrapper.find("h3").text()).toBe("タスクはありません");
        expect(wrapper.find("p").text()).toBe("新しいタスクを追加しましょう");

        // Check if the SVG icon is present
        expect(wrapper.find("svg").exists()).toBe(true);
    });
});
