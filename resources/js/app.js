// resources/js/app.js

import "./bootstrap";

import Alpine from "alpinejs";
import { createApp } from "vue";
import TodoApp from "./components/TodoApp.vue";
import SidebarMemosComponent from "./components/SidebarMemosComponent.vue";

window.Alpine = Alpine;
Alpine.start();

// Initialize Vue components
document.addEventListener("DOMContentLoaded", function () {
    // Mount TodoApp component if it exists
    if (document.getElementById("todo-app")) {
        const app = createApp(TodoApp);
        const vm = app.mount("#todo-app");

        // Vueコンポーネント外からタスク編集するためのグローバル関数定義
        window.editTodo = function (taskIdOrData, todoData = null) {
            console.log("Global editTodo called with:", taskIdOrData, todoData);

            // HTMLデータ属性からtodoDataが渡された場合の処理
            if (todoData && typeof todoData === "object") {
                console.log("Using todoData from second parameter:", todoData);

                // Try to directly call the openEditTaskModal function with the todoData object
                if (vm && typeof vm.openEditTaskModal === "function") {
                    if (!todoData.id && taskIdOrData) {
                        todoData.id = Number(taskIdOrData);
                    }
                    vm.openEditTaskModal(todoData);
                    return;
                }

                // Fallback to event dispatch
                const event = new CustomEvent("edit-todo", {
                    detail: { id: Number(taskIdOrData), data: todoData },
                });
                document.getElementById("todo-app").dispatchEvent(event);
                return;
            }

            if (!taskIdOrData) {
                console.error("No task ID or data provided to editTodo");
                return;
            }

            try {
                // Use a direct ID if one is provided
                if (
                    typeof taskIdOrData === "number" ||
                    (typeof taskIdOrData === "string" &&
                        !isNaN(parseInt(taskIdOrData)))
                ) {
                    const id = Number(taskIdOrData);

                    // Try to directly call the fetchAndEditTask function if available
                    if (vm && typeof vm.fetchAndEditTask === "function") {
                        console.log(
                            "Directly calling fetchAndEditTask with ID:",
                            id,
                        );
                        vm.fetchAndEditTask(id);
                        return;
                    }

                    // Fallback to event dispatch
                    console.log("Dispatching edit-todo event with ID:", id);
                    const event = new CustomEvent("edit-todo", {
                        detail: { id, data: null },
                    });
                    document.getElementById("todo-app").dispatchEvent(event);
                    return;
                }

                // Use object data if provided
                if (typeof taskIdOrData === "object" && taskIdOrData !== null) {
                    // openEditTaskModal関数が利用可能な場合の直接呼び出し
                    if (vm && typeof vm.openEditTaskModal === "function") {
                        console.log(
                            "Directly calling openEditTaskModal with object:",
                            taskIdOrData,
                        );
                        vm.openEditTaskModal(taskIdOrData);
                        return;
                    }
                    // イベントディスパッチによるフォールバック処理
                    const detail = taskIdOrData.id
                        ? { id: Number(taskIdOrData.id), data: taskIdOrData }
                        : { id: null, data: taskIdOrData };

                    console.log(
                        "Dispatching edit-todo event with detail:",
                        detail,
                    );
                    const event = new CustomEvent("edit-todo", { detail });
                    document.getElementById("todo-app").dispatchEvent(event);
                    return;
                }

                console.error("Invalid task data format:", taskIdOrData);
            } catch (error) {
                console.error("Error in editTodo function:", error);
                alert("タスクの編集中にエラーが発生しました");
            }
        };
    }

    // Mount SidebarMemosComponent if it exists
    if (document.getElementById("sidebar-memos")) {
        const sidebarApp = createApp(SidebarMemosComponent);
        sidebarApp.mount("#sidebar-memos");
    }
});

// Vueコンポーネント外のボタン用イベントリスナー
document.addEventListener("DOMContentLoaded", function () {
    // 従来のHTML内の編集ボタンクリックハンドラー追加
    const editButtons = document.querySelectorAll(".edit-task-btn");

    if (editButtons.length > 0) {
        console.log(
            "Found",
            editButtons.length,
            "edit buttons to attach handlers to",
        );

        editButtons.forEach((button) => {
            button.addEventListener("click", function (e) {
                e.preventDefault();
                const taskId = this.getAttribute("data-task-id");
                if (taskId) {
                    console.log("Edit button clicked for task ID:", taskId);
                    window.editTodo(Number(taskId));
                } else {
                    console.error("No task ID found on button:", this);
                }
            });
        });
    } else {
        console.log("No edit buttons found on page");
    }
});
