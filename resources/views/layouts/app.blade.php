<!-- resources/views/layouts/app.blade.php -->
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
                ] : null,
            ]) !!};
        </script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 flex flex-col md:flex-row">
            <!-- サイドバー -->
            <div id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 transform transition-transform duration-300 ease-in-out md:relative md:sticky md:top-0 md:h-screen sidebar-collapsed">
                @include('layouts.navigation')
            </div>

            <!-- メインコンテンツエリア -->
            <main id="mainContent" class="flex-1 transition-all duration-300 px-1 py-1 md:py-2 main-expanded">
                <!-- Todo App ヘッダー部分にトグルボタンを配置 -->
                {{ $slot }}
            </main>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.getElementById('mainContent');

                // サイドバーの状態をローカルストレージから取得
                const sidebarState = localStorage.getItem('sidebarState') || 'expanded';

                // 初期状態を設定
                updateSidebarState(sidebarState === 'collapsed');

                // サイドバー外をクリックで閉じる処理
                document.addEventListener('click', function(event) {
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isToggleButton = event.target.closest('#sidebarToggle');

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
            });
        </script>

        <style>
            /* サイドバー折りたたみ時のスタイル */
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

            /* カスタムスクロールバー */
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
    </body>
</html>
