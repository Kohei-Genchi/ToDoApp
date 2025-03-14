// resources/js/api/todo.spec.js
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import axios from "axios";
import TodoApi from "./todo";

// Mock axios module
vi.mock("axios");

describe("TodoApi", () => {
    beforeEach(() => {
        // Reset axios mocks before each test
        vi.resetAllMocks();

        // Mock the CSRF token
        document.querySelector = vi.fn().mockImplementation((selector) => {
            if (selector === 'meta[name="csrf-token"]') {
                return { getAttribute: () => "test-csrf-token" };
            }
            return null;
        });
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe("getTasks", () => {
        it("calls the correct API endpoint with params", async () => {
            // Mock axios get implementation
            axios.get.mockResolvedValue({ data: [] });

            // Call the API function
            await TodoApi.getTasks("today", "2025-03-14");

            // Verify axios was called with the correct parameters
            expect(axios.get).toHaveBeenCalledTimes(1);
            expect(axios.get).toHaveBeenCalledWith("/api/todos", {
                params: { view: "today", date: "2025-03-14" },
                headers: expect.objectContaining({
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                }),
            });
        });

        it("returns the data from the API response", async () => {
            // Sample API response
            const sampleTodos = [
                { id: 1, title: "Task 1", status: "pending" },
                { id: 2, title: "Task 2", status: "completed" },
            ];

            // Mock axios get to return sample data
            axios.get.mockResolvedValue({ data: sampleTodos });

            // Call the API function
            const result = await TodoApi.getTasks("today");

            // Verify we get back the mocked data
            expect(result.data).toEqual(sampleTodos);
        });
    });

    describe("toggleTask", () => {
        it("makes a PATCH request to the correct endpoint", async () => {
            // Mock axios patch implementation
            axios.patch.mockResolvedValue({
                data: {
                    message: "Task status updated successfully",
                    todo: { id: 1, title: "Task 1", status: "completed" },
                },
            });

            // Call the API function
            await TodoApi.toggleTask(1);

            // Verify axios was called correctly
            expect(axios.patch).toHaveBeenCalledTimes(1);
            expect(axios.patch).toHaveBeenCalledWith(
                "/api/todos/1/toggle",
                {},
                expect.objectContaining({
                    headers: expect.objectContaining({
                        "X-CSRF-TOKEN": "test-csrf-token",
                    }),
                }),
            );
        });
    });

    describe("createTask", () => {
        it("makes a POST request with the task data", async () => {
            // Sample task data
            const taskData = {
                title: "New Task",
                description: "Task description",
                due_date: "2025-03-15",
                category_id: 1,
            };

            // Mock axios post implementation
            axios.post.mockResolvedValue({
                data: {
                    message: "Task created successfully",
                    todo: { id: 3, ...taskData },
                },
            });

            // Call the API function
            await TodoApi.createTask(taskData);

            // Verify axios was called correctly
            expect(axios.post).toHaveBeenCalledTimes(1);
            expect(axios.post).toHaveBeenCalledWith(
                "/api/todos",
                taskData,
                expect.objectContaining({
                    headers: expect.objectContaining({
                        "X-CSRF-TOKEN": "test-csrf-token",
                        "Content-Type": "application/json",
                    }),
                }),
            );
        });
    });
});
