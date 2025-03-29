// resources/js/app.js
import "./bootstrap";
import Alpine from "alpinejs";
import * as Vue from "vue";

// Make Vue available globally for component mounting
window.Vue = Vue;

// Alpine.js initialization
window.Alpine = Alpine;
Alpine.start();

document.addEventListener("DOMContentLoaded", function () {
    // Main TodoApp component loading
    let vm = null;
    if (document.getElementById("todo-app")) {
        // Import the TodoApp component dynamically
        import("./components/TodoApp.vue")
            .then((module) => {
                vm = Vue.createApp(module.default).mount("#todo-app");

                // Global task editing function
                window.editTodo = function (taskIdOrData, todoData = null) {
                    try {
                        if (todoData && typeof todoData === "object") {
                            if (!todoData.id && taskIdOrData) {
                                todoData.id = Number(taskIdOrData);
                            }
                            if (vm?.openEditTaskModal) {
                                vm.openEditTaskModal(todoData);
                            } else {
                                dispatchEditEvent({
                                    id: todoData.id,
                                    data: todoData,
                                });
                            }
                            return;
                        }

                        if (!taskIdOrData) {
                            throw new Error("タスクIDまたはデータがありません");
                        }

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

                        if (typeof taskIdOrData === "object") {
                            if (vm?.openEditTaskModal) {
                                vm.openEditTaskModal(taskIdOrData);
                            } else {
                                const detail = taskIdOrData.id
                                    ? {
                                          id: Number(taskIdOrData.id),
                                          data: taskIdOrData,
                                      }
                                    : { id: null, data: taskIdOrData };
                                dispatchEditEvent(detail);
                            }
                            return;
                        }

                        throw new Error("無効なタスクデータ形式");
                    } catch (error) {
                        console.error("タスク編集エラー:", error);
                        alert("タスクの編集中にエラーが発生しました");
                    }
                };

                function dispatchEditEvent(detail) {
                    document
                        .getElementById("todo-app")
                        .dispatchEvent(
                            new CustomEvent("edit-todo", { detail }),
                        );
                }
            })
            .catch((error) => {
                console.error("Error loading TodoApp component:", error);
            });
    }

    // Sidebar memos component loading
    if (document.getElementById("sidebar-memos")) {
        // Dynamic import - load only when needed
        import("./components/SidebarMemosComponent.vue")
            .then((module) => {
                Vue.createApp(module.default).mount("#sidebar-memos");
            })
            .catch((error) => {
                console.error("Error loading SidebarMemosComponent:", error);
            });
    }

    // KanbanBoard component loading
    if (document.getElementById("kanban-board")) {
        // Dynamic import - load only when needed
        import("./components/KanbanBoard.vue")
            .then((module) => {
                Vue.createApp(module.default).mount("#kanban-board");
            })
            .catch((error) => {
                console.error("Error loading KanbanBoard component:", error);
                document.getElementById("kanban-board").innerHTML =
                    '<div class="bg-red-100 p-4 rounded text-red-700">' +
                    "Error loading Kanban Board component. Please refresh the page or try again later." +
                    "</div>";
            });
    }

    // For existing edit buttons
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
