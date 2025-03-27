<x-app-layout>
    <div id="todo-app" class="w-full max-w-full">
        <!-- Todo コンポーネントはここでマウントされます -->
    </div>

    <!-- トグルボタンのスタイルとスクリプト -->
    <style>
        .toggle-button {
            position: fixed;
            top: 18px;
            left: 10px;
            z-index: 40;
            background-color: transparent;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .toggle-button:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .toggle-button svg {
            width: 24px;
            height: 24px;
            color: #374151;
        }

        /* トグルボタンの位置をサイドバーの状態に応じて調整 */
        @media (min-width: 768px) {
            .sidebar-open .toggle-button {
                left: 10px;
            }

            .sidebar-closed .toggle-button {
                left: 10px;
            }
        }

        /* メイン画面の余白を削減 */
        #todo-app {
            padding: 0 !important;
            max-width: 100% !important;
        }

        /* Todo App内の余白を調整 */
        #todo-app .max-w-7xl {
            max-width: 100% !important;
            padding-left: 4px !important;
            padding-right: 4px !important;
        }

        /* カレンダー・タスクリストのコンテナ余白 */
        #todo-app .bg-white.rounded-lg.shadow-sm {
            margin-top: 4px !important;
        }

        /* タスクアイテムの内側余白調整 */
        #todo-app .p-3, #todo-app .p-4 {
            padding: 8px !important;
        }
    </style>


</x-app-layout>
