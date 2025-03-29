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

            <!-- Simple Kanban Board Implementation with vanilla JavaScript -->
            <div id="simple-kanban" class="p-4">
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Kanban Board</h2>
                        <button onclick="showAddTaskModal('pending')" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">新しいタスク</button>
                    </div>

                    <!-- Kanban Columns -->
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                        <!-- To Do Column -->
                        <div class="bg-gray-100 rounded-lg p-4" id="column-pending" ondragover="allowDrop(event)" ondrop="dropTask(event, 'pending')">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-medium">To Do</h3>
                                <div class="flex items-center">
                                    <span class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full mr-2" id="pending-count">
                                        {{ $pendingTasks->count() }}
                                    </span>
                                    <button
                                        onclick="showAddTaskModal('pending')"
                                        class="bg-white p-1 rounded-full shadow hover:bg-gray-100"
                                    >
                                        <svg class="w-4 h-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="task-container space-y-2">
                                @foreach($pendingTasks as $task)
                                <div class="bg-white rounded shadow p-3 cursor-pointer task-card"
                                     draggable="true"
                                     ondragstart="dragTask(event, {{ $task->id }})"
                                     data-task-id="{{ $task->id }}"
                                     onclick="editTask(event, {{ $task->id }})">
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
                        <div class="bg-blue-100 rounded-lg p-4" id="column-in_progress" ondragover="allowDrop(event)" ondrop="dropTask(event, 'in_progress')">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-medium">In Progress</h3>
                                <div class="flex items-center">
                                    <span class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full mr-2" id="in_progress-count">
                                        {{ $inProgressTasks->count() }}
                                    </span>
                                    <button
                                        onclick="showAddTaskModal('in_progress')"
                                        class="bg-white p-1 rounded-full shadow hover:bg-gray-100"
                                    >
                                        <svg class="w-4 h-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="task-container space-y-2">
                                @foreach($inProgressTasks as $task)
                                <div class="bg-white rounded shadow p-3 cursor-pointer task-card"
                                     draggable="true"
                                     ondragstart="dragTask(event, {{ $task->id }})"
                                     data-task-id="{{ $task->id }}"
                                     onclick="editTask(event, {{ $task->id }})">
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
                        <div class="bg-yellow-100 rounded-lg p-4" id="column-review" ondragover="allowDrop(event)" ondrop="dropTask(event, 'review')">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-medium">Review</h3>
                                <div class="flex items-center">
                                    <span class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full mr-2" id="review-count">
                                        {{ $reviewTasks->count() }}
                                    </span>
                                    <button
                                        onclick="showAddTaskModal('review')"
                                        class="bg-white p-1 rounded-full shadow hover:bg-gray-100"
                                    >
                                        <svg class="w-4 h-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="task-container space-y-2">
                                @foreach($reviewTasks as $task)
                                <div class="bg-white rounded shadow p-3 cursor-pointer task-card"
                                     draggable="true"
                                     ondragstart="dragTask(event, {{ $task->id }})"
                                     data-task-id="{{ $task->id }}"
                                     onclick="editTask(event, {{ $task->id }})">
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
                        <div class="bg-green-100 rounded-lg p-4" id="column-completed" ondragover="allowDrop(event)" ondrop="dropTask(event, 'completed')">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-medium">Completed</h3>
                                <div class="flex items-center">
                                    <span class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full mr-2" id="completed-count">
                                        {{ $completedTasks->count() }}
                                    </span>
                                    <button
                                        onclick="showAddTaskModal('completed')"
                                        class="bg-white p-1 rounded-full shadow hover:bg-gray-100"
                                    >
                                        <svg class="w-4 h-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="task-container space-y-2">
                                @foreach($completedTasks as $task)
                                <div class="bg-white rounded shadow p-3 cursor-pointer task-card"
                                     draggable="true"
                                     ondragstart="dragTask(event, {{ $task->id }})"
                                     data-task-id="{{ $task->id }}"
                                     onclick="editTask(event, {{ $task->id }})">
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
    // Drag and drop functionality
    let draggedTaskId = null;

    function dragTask(event, taskId) {
        draggedTaskId = taskId;
        event.dataTransfer.setData("text/plain", taskId);
        setTimeout(() => {
            event.target.classList.add("opacity-50");
        }, 0);
    }

    function allowDrop(event) {
        event.preventDefault();
    }

    function dropTask(event, newStatus) {
        event.preventDefault();

        // Remove opacity from all dragged items
        document.querySelectorAll('.opacity-50').forEach(el => {
            el.classList.remove('opacity-50');
        });

        const taskId = draggedTaskId;
        if (!taskId) return;

        // Get the task element
        const taskElement = document.querySelector(`.task-card[data-task-id="${taskId}"]`);
        if (!taskElement) return;

        // Move task element to new column
        const targetColumn = document.getElementById(`column-${newStatus}`);
        if (!targetColumn) return;

        const taskContainer = targetColumn.querySelector('.task-container');
        if (!taskContainer) return;

        // Remove from current column and add to new column
        taskElement.parentNode.removeChild(taskElement);
        taskContainer.appendChild(taskElement);

        // Update count displays
        updateColumnCounts();

        // Send API request to update status
        updateTaskStatus(taskId, newStatus);
    }

    function updateColumnCounts() {
        // Update the count for each column
        const columns = ['pending', 'in_progress', 'review', 'completed'];

        columns.forEach(status => {
            const taskCount = document.querySelectorAll(`#column-${status} .task-card`).length;
            const countElement = document.getElementById(`${status}-count`);
            if (countElement) {
                countElement.textContent = taskCount;
            }
        });
    }

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

            console.log(`Successfully updated task ${taskId} status to ${newStatus}`);

        } catch (error) {
            console.error("Error updating task status:", error);
            alert("タスクのステータス更新に失敗しました。ページを更新します。");
            // Reload the page if status update fails to ensure UI is in sync
            window.location.reload();
        }
    }

    // Edit task using the global editTodo function
    function editTask(event, taskId) {
        // Prevent default click behavior
        event.preventDefault();
        event.stopPropagation();

        // Use the global editTodo function if it exists
        if (typeof window.editTodo === 'function') {
            window.editTodo(taskId);
        } else {
            // Fallback to using the task edit page
            alert("タスク編集機能を読み込めませんでした。ページを更新してください。");
        }
    }

    // Show add task modal with the specified status
    function showAddTaskModal(status = 'pending') {
        // Use the global editTodo function to add a new task
        if (typeof window.editTodo === 'function') {
            window.editTodo({
                title: "",
                description: "",
                due_date: new Date().toISOString().split('T')[0],
                status: status
            });
        } else {
            // Fallback
            alert("タスク作成機能を読み込めませんでした。ページを更新してください。");
        }
    }

    // Add dragged task indicator styles
    document.addEventListener("DOMContentLoaded", function() {
        const style = document.createElement('style');
        style.innerHTML = `
            .task-card { transition: opacity 0.2s ease-in-out; }
            .opacity-50 { opacity: 0.5; }
        `;
        document.head.appendChild(style);
    });
    </script>
</x-app-layout>
