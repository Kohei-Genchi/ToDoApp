<x-app-layout>
    <div class="flex flex-col h-screen">
        <!-- Top Nav Bar -->
        <div class="bg-white shadow-sm header-with-toggle">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <h1 class="ml-4 text-xl font-semibold text-gray-800 page-title">Team Kanban Board</h1>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <nav class="flex space-x-4 text-sm kanban-tabs-container">
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

            <!-- Simple Kanban Board Implementation -->
            <div id="simple-kanban" class="p-4">
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Kanban Board</h2>
                        <a href="#" onclick="showAddTaskModal(); return false;" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">新しいタスク</a>
                    </div>

                    <!-- Kanban Columns -->
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                        <!-- To Do Column -->
                        <div class="bg-gray-100 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-medium">To Do</h3>
                                <span class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full">
                                    {{ $pendingTasks->count() }}
                                </span>
                            </div>
                            <div class="space-y-2">
                                @foreach($pendingTasks as $task)
                                <div class="bg-white rounded shadow p-3 cursor-pointer" onclick="editTask({{ $task->id }})">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium text-sm">{{ $task->title }}</h4>
                                        @if($task->category)
                                        <span class="w-2 h-2 rounded-full" style="background-color: {{ $task->category->color }}"></span>
                                        @endif
                                    </div>
                                    @if($task->due_date)
                                    <div class="mt-2 text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($task->due_date)->format('Y/m/d') }}
                                        @if($task->due_time)
                                        {{ \Carbon\Carbon::parse($task->due_time)->format('H:i') }}
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- In Progress Column -->
                        <div class="bg-blue-100 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-medium">In Progress</h3>
                                <span class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full">
                                    {{ $inProgressTasks->count() }}
                                </span>
                            </div>
                            <div class="space-y-2">
                                @foreach($inProgressTasks as $task)
                                <div class="bg-white rounded shadow p-3 cursor-pointer" onclick="editTask({{ $task->id }})">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium text-sm">{{ $task->title }}</h4>
                                        @if($task->category)
                                        <span class="w-2 h-2 rounded-full" style="background-color: {{ $task->category->color }}"></span>
                                        @endif
                                    </div>
                                    @if($task->due_date)
                                    <div class="mt-2 text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($task->due_date)->format('Y/m/d') }}
                                        @if($task->due_time)
                                        {{ \Carbon\Carbon::parse($task->due_time)->format('H:i') }}
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Review Column -->
                        <div class="bg-yellow-100 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-medium">Review</h3>
                                <span class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full">
                                    {{ $reviewTasks->count() }}
                                </span>
                            </div>
                            <div class="space-y-2">
                                @foreach($reviewTasks as $task)
                                <div class="bg-white rounded shadow p-3 cursor-pointer" onclick="editTask({{ $task->id }})">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium text-sm">{{ $task->title }}</h4>
                                        @if($task->category)
                                        <span class="w-2 h-2 rounded-full" style="background-color: {{ $task->category->color }}"></span>
                                        @endif
                                    </div>
                                    @if($task->due_date)
                                    <div class="mt-2 text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($task->due_date)->format('Y/m/d') }}
                                        @if($task->due_time)
                                        {{ \Carbon\Carbon::parse($task->due_time)->format('H:i') }}
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Completed Column -->
                        <div class="bg-green-100 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-medium">Completed</h3>
                                <span class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full">
                                    {{ $completedTasks->count() }}
                                </span>
                            </div>
                            <div class="space-y-2">
                                @foreach($completedTasks as $task)
                                <div class="bg-white rounded shadow p-3 cursor-pointer" onclick="editTask({{ $task->id }})">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium text-sm">{{ $task->title }}</h4>
                                        @if($task->category)
                                        <span class="w-2 h-2 rounded-full" style="background-color: {{ $task->category->color }}"></span>
                                        @endif
                                    </div>
                                    @if($task->due_date)
                                    <div class="mt-2 text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($task->due_date)->format('Y/m/d') }}
                                        @if($task->due_time)
                                        {{ \Carbon\Carbon::parse($task->due_time)->format('H:i') }}
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editTask(taskId) {
            // Use the global editTodo function if it exists
            if (typeof window.editTodo === 'function') {
                window.editTodo(taskId);
            } else {
                // Fallback to redirecting to the task edit page
                window.location.href = `/api/todos/${taskId}`;
            }
        }

        function showAddTaskModal() {
            // If editTodo exists, use it to show the add task modal
            if (typeof window.editTodo === 'function') {
                window.editTodo({
                    title: "",
                    description: "",
                    due_date: new Date().toISOString().split('T')[0],
                    status: "pending"
                });
            } else {
                // Fallback
                alert('Task creation modal is not available. Please use the main task list to create tasks.');
            }
        }

        // Function to update task status
        async function updateTaskStatus(taskId, newStatus) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const response = await fetch(`/api/todos/${taskId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        _method: 'PUT',
                        status: newStatus
                    })
                });

                if (!response.ok) {
                    throw new Error('Failed to update task status');
                }

                // Reload the page to show updated status
                window.location.reload();

            } catch (error) {
                console.error('Error updating task status:', error);
                alert('Error updating task status. Please try again.');
            }
        }
    </script>
</x-app-layout>
