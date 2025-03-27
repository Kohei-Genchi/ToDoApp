// resources/js/app.js
import "./bootstrap";
import Alpine from "alpinejs";
import { createApp } from "vue";
import TodoApp from "./components/TodoApp.vue";

// Alpine.js の初期化
window.Alpine = Alpine;
Alpine.start();

document.addEventListener("DOMContentLoaded", function () {
    // メインのTodoAppコンポーネントはすぐに読み込む
    let vm = null;
    if (document.getElementById("todo-app")) {
        vm = createApp(TodoApp).mount("#todo-app");

        // グローバルのタスク編集機能
        window.editTodo = function (taskIdOrData, todoData = null) {
            try {
                if (todoData && typeof todoData === "object") {
                    if (!todoData.id && taskIdOrData) {
                        todoData.id = Number(taskIdOrData);
                    }
                    if (vm?.openEditTaskModal) {
                        vm.openEditTaskModal(todoData);
                    } else {
                        dispatchEditEvent({ id: todoData.id, data: todoData });
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
                .dispatchEvent(new CustomEvent("edit-todo", { detail }));
        }
    }

    // サイドバーメモコンポーネントは必要なときだけ遅延読み込み
    if (document.getElementById("sidebar-memos")) {
        // 動的インポート - コンポーネントが必要なときだけ読み込む
        import("./components/SidebarMemosComponent.vue").then((module) => {
            createApp(module.default).mount("#sidebar-memos");
        });
    }

    // 共有タスク表示コンポーネントも遅延読み込み
    if (document.getElementById("shared-tasks-view")) {
        import("./components/SharedTasksCalendarView.vue").then((module) => {
            createApp(module.default).mount("#shared-tasks-view");
        });
    }

    // 既存の編集ボタンにイベントリスナーを追加
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
