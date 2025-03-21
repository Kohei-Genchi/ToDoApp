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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // メイン画面の余白を削減するためのスタイルを動的に追加
            const style = document.createElement('style');
            style.textContent = `
                /* ヘッダー部分の余白調整 */
                .bg-white.shadow {
                    padding-top: 6px !important;
                    padding-bottom: 6px !important;
                    padding-left: 48px !important; /* トグルボタン用のスペース */
                }

                /* 日付ナビゲーションの余白調整 */
                .mb-2, .mb-4 {
                    margin-bottom: 4px !important;
                }
            `;
            document.head.appendChild(style);

            // Todo Appタイトル部分にトグルボタンを追加する
            const todoAppTitle = document.querySelector('#todo-app');
            if (todoAppTitle) {
                // しばらく待ってVueコンポーネントがマウントされるのを待つ
                setTimeout(function() {
                    const todoAppHeader = document.querySelector('.bg-white.shadow');
                    if (todoAppHeader) {
                        // トグルボタンを作成
                        const toggleButton = document.createElement('button');
                        toggleButton.id = 'sidebarToggle';
                        toggleButton.className = 'toggle-button';
                        toggleButton.setAttribute('aria-label', 'Toggle Sidebar');
                        toggleButton.innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        `;

                        // クリックイベントを追加
                        toggleButton.addEventListener('click', function() {
                            if (typeof window.toggleSidebar === 'function') {
                                window.toggleSidebar();
                            }
                        });

                        // Todo Appヘッダーの最初の要素として挿入
                        const firstChild = todoAppHeader.firstChild;
                        todoAppHeader.insertBefore(toggleButton, firstChild);

                        // サイドバーの状態に応じたクラスをボディに追加
                        const sidebar = document.getElementById('sidebar');
                        if (sidebar) {
                            document.body.classList.add(
                                sidebar.classList.contains('sidebar-collapsed') ? 'sidebar-closed' : 'sidebar-open'
                            );
                        }
                    }
                }, 300); // 300ms待ってからDOMに追加
            }
        });
    </script>
</x-app-layout>
