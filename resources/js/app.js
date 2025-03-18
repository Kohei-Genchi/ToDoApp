import "./bootstrap";
import Alpine from "alpinejs";
import { createApp } from "vue";
import TodoApp from "./components/TodoApp.vue";
import SidebarMemosComponent from "./components/SidebarMemosComponent.vue";

window.Alpine = Alpine;
Alpine.start();

document.addEventListener("DOMContentLoaded", function () {
    // TodoApp component initialization
    let vm = null;
    if (document.getElementById("todo-app")) {
        vm = createApp(TodoApp).mount("#todo-app");

        // Global edit task function
        window.editTodo = function (taskIdOrData, todoData = null) {
            try {
                // Case 1: Data object provided as second parameter
                if (todoData && typeof todoData === "object") {
                    if (!todoData.id && taskIdOrData) {
                        todoData.id = Number(taskIdOrData);
                    }
                    callEditFunction(todoData);
                    return;
                }

                // Case 2: No valid input
                if (!taskIdOrData) {
                    throw new Error("No task ID or data provided");
                }

                // Case 3: ID provided
                if (
                    typeof taskIdOrData === "number" ||
                    (typeof taskIdOrData === "string" &&
                        !isNaN(parseInt(taskIdOrData)))
                ) {
                    const id = Number(taskIdOrData);
                    if (vm?.fetchAndEditTask) {
                        vm.fetchAndEditTask(id);
                    } else {
                        dispatchEditEvent({ id, data: null });
                    }
                    return;
                }

                // Case 4: Object data provided directly
                if (typeof taskIdOrData === "object") {
                    callEditFunction(taskIdOrData);
                    return;
                }

                throw new Error("Invalid task data format");
            } catch (error) {
                console.error("Edit task error:", error);
                alert("タスクの編集中にエラーが発生しました");
            }
        };

        // Helper functions
        function callEditFunction(data) {
            if (vm?.openEditTaskModal) {
                vm.openEditTaskModal(data);
            } else {
                const detail = data.id
                    ? { id: Number(data.id), data }
                    : { id: null, data };
                dispatchEditEvent(detail);
            }
        }

        function dispatchEditEvent(detail) {
            document
                .getElementById("todo-app")
                .dispatchEvent(new CustomEvent("edit-todo", { detail }));
        }
    }

    // SidebarMemos component initialization
    if (document.getElementById("sidebar-memos")) {
        createApp(SidebarMemosComponent).mount("#sidebar-memos");
    }

    // Add event listeners to traditional edit buttons
    document.querySelectorAll(".edit-task-btn").forEach((button) => {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            const taskId = this.getAttribute("data-task-id");
            if (taskId) {
                window.editTodo(Number(taskId));
            }
        });
    });
});
