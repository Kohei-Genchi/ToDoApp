// resources/js/components/TodoList.spec.js
import { describe, it, expect, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import TodoList from "./TodoList.vue";
import {
    MockTaskStats,
    MockEmptyState,
    MockTaskItem,
} from "../test/mocks/components";

describe("TodoList.vue", () => {
    const sampleTodos = [
        { id: 1, title: "Task 1", status: "pending", category_id: 1 },
        { id: 2, title: "Task 2", status: "completed", category_id: 2 },
    ];

    const sampleCategories = [
        { id: 1, name: "Work", color: "#ff5733" },
        { id: 2, name: "Personal", color: "#33ff57" },
    ];

    let wrapper;

    beforeEach(() => {
        // Mount with stubbed child components
        wrapper = mount(TodoList, {
            props: {
                todos: sampleTodos,
                categories: sampleCategories,
            },
            global: {
                stubs: {
                    TaskStats: MockTaskStats,
                    EmptyState: MockEmptyState,
                    TaskItem: MockTaskItem,
                },
            },
        });
    });

    it("computes correct statistics", () => {
        // Check if TaskStats is rendered with correct props
        const taskStats = wrapper.findComponent({ name: "TaskStats" });
        expect(taskStats.props()).toEqual({
            totalCount: 2,
            completedCount: 1,
            pendingCount: 1,
        });
    });

    it("displays EmptyState when no todos are provided", async () => {
        // Update props to have no todos
        await wrapper.setProps({ todos: [] });

        // Check if EmptyState is rendered
        expect(wrapper.findComponent({ name: "EmptyState" }).exists()).toBe(
            true,
        );
        expect(wrapper.findComponent({ name: "TaskItem" }).exists()).toBe(
            false,
        );
    });

    it("renders TaskItem for each todo", () => {
        // Should render 2 TaskItem components for our sample data
        const taskItems = wrapper.findAllComponents({ name: "TaskItem" });
        expect(taskItems.length).toBe(2);
    });

    it("passes correct category to TaskItem", () => {
        // Get all TaskItem components
        const taskItems = wrapper.findAllComponents({ name: "TaskItem" });

        // Check if the first TaskItem has the correct category prop
        expect(taskItems[0].props().category).toEqual(sampleCategories[0]);

        // Check if the second TaskItem has the correct category prop
        expect(taskItems[1].props().category).toEqual(sampleCategories[1]);
    });

    it("emits toggle-task event when TaskItem emits toggle", async () => {
        // Find the first TaskItem
        const taskItem = wrapper.findComponent({ name: "TaskItem" });

        // Emit toggle event from TaskItem
        await taskItem.vm.$emit("toggle", sampleTodos[0]);

        // Check if TodoList emitted toggle-task with the correct todo
        expect(wrapper.emitted()["toggle-task"]).toBeTruthy();
        expect(wrapper.emitted()["toggle-task"][0][0]).toEqual(sampleTodos[0]);
    });

    it("emits edit-task event when TaskItem emits edit", async () => {
        // Find the first TaskItem
        const taskItem = wrapper.findComponent({ name: "TaskItem" });

        // Emit edit event from TaskItem
        await taskItem.vm.$emit("edit", sampleTodos[0]);

        // Check if TodoList emitted edit-task with the correct todo
        expect(wrapper.emitted()["edit-task"]).toBeTruthy();
        expect(wrapper.emitted()["edit-task"][0][0]).toEqual(sampleTodos[0]);
    });

    it("emits delete-task event when TaskItem emits delete", async () => {
        // Find the first TaskItem
        const taskItem = wrapper.findComponent({ name: "TaskItem" });

        // Emit delete event from TaskItem
        await taskItem.vm.$emit("delete");

        // Check if TodoList emitted delete-task with the correct todo
        expect(wrapper.emitted()["delete-task"]).toBeTruthy();
    });
});
