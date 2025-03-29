<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            window.Laravel = {!! json_encode([
                'user' => Auth::check() ? [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'has_subscription' => !empty(Auth::user()->subscription_id),
                            ] : null,
            ]) !!};
        </script>

        <!-- Toggle button styles -->
        <style>
            /* Toggle button styling */
            .toggle-button {
                position: fixed;
                top: 18px;
                left: 10px;
                z-index: 50;
                background-color: white;
                border: 1px solid rgba(0,0,0,0.1);
                box-shadow: 0 1px 2px rgba(0,0,0,0.08);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 7px;
                border-radius: 5px;
                transition: background-color 0.2s;
            }

            .toggle-button:hover {
                background-color: rgba(0, 0, 0, 0.05);
            }

            .toggle-button svg {
                width: 20px;
                height: 20px;
                color: #374151;
            }

            /* Header text spacing for all pages */
            .page-header h1,
            .page-header h2,
            h1.page-title,
            h2.page-title,
            /* Vue component headers */
            .bg-white.shadow h1,
            .bg-white.shadow h2,
            /* Specific page headers */
            #todo-app .bg-white.shadow h1,
            #todo-app h1,
            .kanban-board-header h1 {
                padding-left: 40px !important;
            }

            /* Fix spacing for all page headers */
            .header-with-toggle,
            .bg-white.shadow {
                padding-left: 45px !important;
            }

            /* Fix for page title alignment in all cases */
            .page-title {
                margin-left: 40px !important;
            }

            /* Fix for specific Vue component headers */
            #todo-app .max-w-7xl {
                max-width: 100% !important;
                padding-left: 4px !important;
                padding-right: 4px !important;
            }

            /* Kanban board tabs - adjust padding */
            .kanban-tabs-container {
                padding-left: 44px !important;
            }

            /* Calendar/task list container margin */
            #todo-app .bg-white.rounded-lg.shadow-sm {
                margin-top: 4px !important;
            }

            /* Task item padding */
            #todo-app .p-3, #todo-app .p-4 {
                padding: 8px !important;
            }

            /* Sidebar styles */
            @media (min-width: 768px) {
                .sidebar-collapsed {
                    width: 0 !important;
                    min-width: 0 !important;
                    overflow: hidden;
                }

                .main-expanded {
                    width: 100% !important;
                    max-width: 100% !important;
                }
            }

            /* Custom scrollbar */
            .custom-scrollbar::-webkit-scrollbar {
                width: 4px;
            }
            .custom-scrollbar::-webkit-scrollbar-track {
                background: rgba(55, 65, 81, 0.1);
            }
            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: rgba(156, 163, 175, 0.5);
                border-radius: 10px;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 flex flex-col md:flex-row">
            <!-- Toggle Button - Now universal for all pages -->
            <button id="globalSidebarToggle" class="toggle-button" aria-label="Toggle Sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- サイドバー -->
            <div id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 transform transition-transform duration-300 ease-in-out md:relative md:sticky md:top-0 md:h-screen sidebar-collapsed">
                @include('layouts.navigation')
            </div>

            <!-- メインコンテンツエリア -->
            <main id="mainContent" class="flex-1 transition-all duration-300 px-1 py-1 md:py-2 main-expanded">
                {{ $slot }}
            </main>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.getElementById('mainContent');
                const globalToggleButton = document.getElementById('globalSidebarToggle');

                // サイドバーの状態をローカルストレージから取得
                const sidebarState = localStorage.getItem('sidebarState') || 'expanded';

                // 初期状態を設定
                updateSidebarState(sidebarState === 'collapsed');

                // Add body class for CSS selectors
                document.body.classList.add(
                    sidebar.classList.contains('sidebar-collapsed') ? 'sidebar-closed' : 'sidebar-open'
                );

                // Add left spacing to all page headers
                addHeaderSpacing();

                // Global toggle button event listener
                if (globalToggleButton) {
                    globalToggleButton.addEventListener('click', function() {
                        window.toggleSidebar();
                    });
                }

                // サイドバー外をクリックで閉じる処理
                document.addEventListener('click', function(event) {
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isToggleButton = event.target.closest('#globalSidebarToggle') ||
                                          event.target.closest('#sidebarToggle');

                    // モバイル表示でサイドバー外をクリックした場合
                    if (window.innerWidth < 768 && !isClickInsideSidebar && !isToggleButton) {
                        sidebar.classList.add('-translate-x-full');
                    }
                });

                // 画面サイズ変更時の処理
                window.addEventListener('resize', function() {
                    // 保存された状態を反映
                    updateSidebarState(localStorage.getItem('sidebarState') === 'collapsed');

                    // モバイル表示では常に閉じる
                    if (window.innerWidth < 768) {
                        sidebar.classList.add('-translate-x-full');
                    }
                });

                // グローバル関数としてトグル関数を定義
                window.toggleSidebar = function() {
                    const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
                    updateSidebarState(!isCollapsed);

                    // Update body class
                    document.body.classList.remove('sidebar-open', 'sidebar-closed');
                    document.body.classList.add(!isCollapsed ? 'sidebar-closed' : 'sidebar-open');

                    // 状態をローカルストレージに保存
                    localStorage.setItem('sidebarState', !isCollapsed ? 'collapsed' : 'expanded');
                };

                // サイドバー状態を更新する関数
                function updateSidebarState(collapse) {
                    if (collapse) {
                        // サイドバーを折りたたむ
                        sidebar.classList.add('sidebar-collapsed');
                        sidebar.classList.add('-translate-x-full');
                        sidebar.classList.remove('md:translate-x-0');
                        mainContent.classList.add('main-expanded');
                    } else {
                        // サイドバーを展開
                        sidebar.classList.remove('sidebar-collapsed');
                        sidebar.classList.remove('-translate-x-full');
                        sidebar.classList.add('md:translate-x-0');
                        mainContent.classList.remove('main-expanded');
                    }
                }

                // Add proper spacing to all page headers
                function addHeaderSpacing() {
                    // Find all page headers and add class
                    document.querySelectorAll('h1, h2').forEach(header => {
                        if (isPageHeader(header)) {
                            header.classList.add('page-title');

                            // Add padding to parent container if needed
                            const parentContainer = header.closest('div');
                            if (parentContainer && isHeaderContainer(parentContainer)) {
                                parentContainer.classList.add('header-with-toggle');
                            }
                        }
                    });

                    // Add specific spacing for Vue app headers when they load
                    setTimeout(() => {
                        document.querySelectorAll('.bg-white.shadow').forEach(header => {
                            header.classList.add('header-with-toggle');
                        });
                    }, 500);
                }

                // Check if an element is a page header
                function isPageHeader(element) {
                    const text = element.textContent.trim();
                    const isInMainContent = mainContent.contains(element);
                    return isInMainContent && text.length > 0;
                }

                // Check if an element is a header container
                function isHeaderContainer(element) {
                    const hasChildren = element.children.length > 0;
                    const hasBgWhite = element.classList.contains('bg-white');
                    const hasShadow = element.classList.contains('shadow');
                    return hasChildren && (hasBgWhite || hasShadow);
                }

                // For backward compatibility with existing toggle buttons
                // This makes any element with id="sidebarToggle" work the same as the global toggle
                const legacyToggle = document.getElementById('sidebarToggle');
                if (legacyToggle) {
                    legacyToggle.addEventListener('click', function() {
                        window.toggleSidebar();
                    });
                }

                // Fix for Vue components that might load after DOM is ready
                const observer = new MutationObserver(mutations => {
                    mutations.forEach(mutation => {
                        if (mutation.addedNodes.length) {
                            mutation.addedNodes.forEach(node => {
                                if (node.nodeType === 1) { // Element node
                                    if (node.classList && node.classList.contains('bg-white') && node.classList.contains('shadow')) {
                                        node.classList.add('header-with-toggle');
                                    }

                                    // Also check for headings inside new nodes
                                    const headers = node.querySelectorAll('h1, h2');
                                    headers.forEach(header => {
                                        if (isPageHeader(header)) {
                                            header.classList.add('page-title');
                                        }
                                    });
                                }
                            });
                        }
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        </script>
        <!-- Add this to your app.blade.php before the closing </body> tag -->
        <script>
        // Ensure TaskModal is properly loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Replace this function in resources/views/layouts/app.blade.php
            // Search for "window.editTodo" and replace the entire function with this:

            window.editTodo = function(taskIdOrData, todoData = null) {
                try {
                    console.log("editTodo called with:", taskIdOrData, todoData);

                    // Try to find TodoApp instance
                    let todoApp = document.getElementById('todo-app');
                    if (!todoApp) {
                        // Create a temporary container for TodoApp if not exists
                        todoApp = document.createElement('div');
                        todoApp.id = 'todo-app';
                        todoApp.style.position = 'fixed';
                        todoApp.style.zIndex = '9999';
                        document.body.appendChild(todoApp);

                        // Use existing Vite instance instead of trying to load direct JS file
                        console.log("TodoApp doesn't exist, dispatching event on document");
                        // Instead of loading a script, dispatch an event that the app can listen for
                        const event = new CustomEvent('open-task-modal', {
                            detail: {
                                taskId: typeof taskIdOrData === 'object' ? null : taskIdOrData,
                                taskData: typeof taskIdOrData === 'object' ? taskIdOrData : todoData
                            }
                        });
                        document.dispatchEvent(event);
                        return;
                    }

                    // Create and dispatch custom event
                    const event = new CustomEvent('edit-todo', {
                        detail: {
                            id: typeof taskIdOrData === 'object' ? null : taskIdOrData,
                            data: typeof taskIdOrData === 'object' ? taskIdOrData : todoData
                        }
                    });

                    // Dispatch the event
                    todoApp.dispatchEvent(event);

                    console.log("Event dispatched:", event);
                } catch (error) {
                    console.error("Error in editTodo:", error);
                    alert("タスク編集機能を呼び出せませんでした。");
                }
            };

            // Also listen for the custom event we created
            document.addEventListener('open-task-modal', function(event) {
                const { taskId, taskData } = event.detail;

                if (taskId) {
                    window.editTodo(taskId);
                } else if (taskData) {
                    window.editTodo(taskData);
                }
            });
        });
        </script>
    </body>
</html>
