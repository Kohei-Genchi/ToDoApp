// resources/js/test/mocks/components.js
import { vi } from "vitest";

// Mock for TaskItem component
export const MockTaskItem = {
    name: "TaskItem",
    props: ["todo", "category"],
    template: '<div class="mock-task-item">{{ todo.title }}</div>',
    emits: ["toggle", "edit", "delete"],
};

// Mock for TaskModal component
export const MockTaskModal = {
    name: "TaskModal",
    props: [
        "mode",
        "todoId",
        "todoData",
        "categories",
        "currentDate",
        "currentView",
    ],
    template: '<div class="mock-task-modal"></div>',
    emits: ["close", "submit", "delete", "category-created"],
};

// Mock for DateNavigation component
export const MockDateNavigation = {
    name: "DateNavigation",
    props: ["formattedDate"],
    template: '<div class="mock-date-navigation">{{ formattedDate }}</div>',
    emits: ["previous-day", "next-day"],
};

// Mock for MonthNavigation component
export const MockMonthNavigation = {
    name: "MonthNavigation",
    props: ["formattedMonth"],
    template: '<div class="mock-month-navigation">{{ formattedMonth }}</div>',
    emits: ["previous-month", "next-month"],
};

// Mock for EmptyState component
export const MockEmptyState = {
    name: "EmptyState",
    template: '<div class="mock-empty-state">No tasks</div>',
};

// Mock for TaskStats component
export const MockTaskStats = {
    name: "TaskStats",
    props: ["totalCount", "completedCount", "pendingCount"],
    template: '<div class="mock-task-stats">Total: {{ totalCount }}</div>',
};

// Mock for NotificationComponent
export const MockNotificationComponent = {
    name: "NotificationComponent",
    template: '<div class="mock-notification"></div>',
    setup() {
        return {
            show: vi.fn(),
        };
    },
};
