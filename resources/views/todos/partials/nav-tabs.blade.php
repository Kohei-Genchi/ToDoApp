{{-- resources/views/todos/partials/nav-tabs.blade.php --}}
<div class="mb-6">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-4" aria-label="Tabs">
            <!-- タスク一覧タブ -->
            <a href="{{ route('todos.index') }}"
               class="{{ request()->routeIs('todos.index') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                タスク一覧
            </a>

            <!-- 共有タスクタブ -->
            <a href="{{ route('todos.shared') }}"
               class="{{ request()->routeIs('todos.shared') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                共有タスク
            </a>

            <!-- 共有カテゴリータブ -->
            <a href="{{ route('categories.shared') }}"
               class="{{ request()->routeIs('categories.shared') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                共有カテゴリー
            </a>
        </nav>
    </div>
</div>
