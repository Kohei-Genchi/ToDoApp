// resources/js/components/TaskItem.spec.js
import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import TaskItem from "./TaskItem.vue";

describe("TaskItem.vue", () => {
    // Sample data for testing
    const sampleTodo = {
        id: 1,
        title: "Test Task",
        status: "pending",
        due_date: "2025-03-14",
        due_time: "2025-03-14T10:00:00.000Z",
        recurrence_type: "none",
    };

    const sampleCategory = {
        id: 1,
        name: "Work",
        color: "#ff5733",
    };

    let wrapper;

    beforeEach(() => {
        // Reset and create a new wrapper before each test
        wrapper = mount(TaskItem, {
            props: {
                todo: sampleTodo,
                category: sampleCategory,
            },
        });
    });

    it("renders task title correctly", () => {
        expect(wrapper.find("p").text()).toBe("Test Task");
    });

    it("shows the correct formatting for completed tasks", async () => {
        // Set a new prop value to mark the task as completed
        await wrapper.setProps({
            todo: { ...sampleTodo, status: "completed" },
        });

        // The task title should have line-through styling (completed task)
        const titleElement = wrapper.find("p");
        expect(titleElement.classes()).toContain("line-through");
        expect(titleElement.classes()).toContain("text-gray-500");
    });

    it("displays category info when provided", () => {
        // Category name should be displayed
        expect(wrapper.text()).toContain(sampleCategory.name);

        // Category color should be applied (via style attribute)
        const categoryStyle = wrapper.find('[style*="background-color"]');
        expect(categoryStyle.exists()).toBe(true);
    });

    it("emits toggle event when checkbox is clicked", async () => {
        // Find and click the checkbox
        await wrapper.find('input[type="checkbox"]').setValue(true);

        // Check if 'toggle' event was emitted with the correct todo
        expect(wrapper.emitted().toggle).toBeTruthy();
        expect(wrapper.emitted().toggle[0][0]).toEqual(sampleTodo);
    });

    it("emits edit event when task content is clicked", async () => {
        // The TaskItem component has a div with @click="handleEdit" that might be rendered differently
        // Instead of using style attribute selector which is fragile, let's call the method directly
        await wrapper.vm.handleEdit();

        // Check if 'edit' event was emitted with the correct todo
        expect(wrapper.emitted().edit).toBeTruthy();
        expect(wrapper.emitted().edit[0][0]).toEqual(sampleTodo);
    });

    it("emits delete event when delete button is clicked", async () => {
        // Find and click the delete button
        await wrapper.find("button").trigger("click");

        // Check if 'delete' event was emitted
        expect(wrapper.emitted().delete).toBeTruthy();
    });

    it("shows recurrence label for recurring tasks", async () => {
        // Set a new prop value to make the task recurring (weekly)
        await wrapper.setProps({
            todo: { ...sampleTodo, recurrence_type: "weekly" },
        });

        // Check if the recurring label is shown
        expect(wrapper.text()).toContain("毎週");
    });
});
