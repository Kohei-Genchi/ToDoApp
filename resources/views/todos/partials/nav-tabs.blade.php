{{-- resources/views/todos/partials/nav-tabs.blade.php --}}
<div class="mb-6">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-4" aria-label="Tabs">
            <!-- タスク一覧タブ -->
            <a href="{{ route('todos.index') }}"
               class="{{ request()->routeIs('todos.index') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                タスク一覧
            </a>

            <!-- カテゴリータブ -->
            <a href="{{ route('categories.index') }}"
               class="{{ request()->routeIs('categories.index') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                カテゴリー
            </a>

            <!-- 共有タスクタブ - サブスクリプションが必要な場合は条件分岐 -->
            @if(Auth::user()->subscription_id)
                <a href="{{ route('todos.shared') }}"
                   class="{{ request()->routeIs('todos.shared') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                    共有タスク
                </a>
            @else
                <span class="cursor-not-allowed whitespace-nowrap py-3 px-1 border-b-2 border-transparent text-gray-300 text-sm"
                      title="サブスクリプションが必要です">
                    共有タスク
                </span>
            @endif

            <!-- 共有カテゴリータブ - 新機能 -->
            @if(Auth::user()->subscription_id)
                <a href="{{ route('categories.shared') }}"
                   class="{{ request()->routeIs('categories.shared') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                    共有カテゴリー
                </a>
            @else
                <span class="cursor-not-allowed whitespace-nowrap py-3 px-1 border-b-2 border-transparent text-gray-300 text-sm"
                      title="サブスクリプションが必要です">
                    共有カテゴリー
                </span>
            @endif

            <!-- カレンダービュータブ - サブスクリプションが必要な場合 -->
            @if(Auth::user()->subscription_id)
                <a href="{{ route('todos.index', ['view' => 'calendar']) }}"
                   class="{{ request()->input('view') === 'calendar' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                    カレンダー
                </a>
            @else
                <span class="cursor-not-allowed whitespace-nowrap py-3 px-1 border-b-2 border-transparent text-gray-300 text-sm"
                      title="サブスクリプションが必要です">
                    カレンダー
                </span>
            @endif
        </nav>
    </div>
</div>
