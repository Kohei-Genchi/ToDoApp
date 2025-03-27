<x-app-layout>
    <div class="flex flex-col h-screen">
        <!-- Top Nav Bar -->
        <div class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <button id="sidebarToggle" class="text-gray-500 focus:outline-none">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            <h1 class="ml-4 text-xl font-semibold text-gray-800">Team Kanban Board</h1>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <nav class="flex space-x-4 text-sm">
                            <a href="{{ route('tasks.kanban') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('tasks.kanban') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                                Kanban Board
                            </a>
                            <a href="{{ route('tasks.team') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('tasks.team') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                                Team Members
                            </a>
                            <a href="{{ route('tasks.analytics') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('tasks.analytics') ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                                Analytics
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
        </div>


        <!-- Main Content -->
                <div class="flex-1 overflow-hidden">
                    <!-- Usage Guide -->
                    <div class="bg-blue-50 p-4 m-4 rounded-lg border border-blue-200">
                        <h3 class="text-lg font-semibold mb-2 text-blue-800">カンバンボードの使い方</h3>
                        <ul class="list-disc ml-5 space-y-1 text-blue-700">
                            <li>「To Do」「In Progress」「Review」「Completed」の4つの列でタスクを管理できます</li>
                            <li>タスクをドラッグ＆ドロップして進捗状況を更新できます</li>
                            <li>各列の「＋」ボタンをクリックして新しいタスクを追加できます</li>
                            <li>タスクをクリックすると詳細を確認・編集できます</li>
                            <li>画面上部のフィルターを使ってタスクを絞り込めます</li>
                        </ul>
                    </div>

                    <div id="kanban-app" class="h-full"></div>
                </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mount the kanban app when DOM is ready
            const kanbanApp = document.getElementById('kanban-app');
            if (kanbanApp) {
                // Dynamically import the KanbanBoard component
                import('../js/components/KanbanBoard.vue').then(module => {
                    createApp(module.default).mount('#kanban-app');
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
