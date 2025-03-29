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
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-hidden">


            <!-- Simple Kanban Board Implementation with vanilla JavaScript -->
            <div id="simple-kanban" class="p-4">
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Kanban Board</h2>
                        <button onclick="addNewTask()" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">新しいタスク</button>
                    </div>

                    <!-- Kanban Columns -->
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                        <!-- To Do Column -->
                        <div class="bg-gray-100 rounded-lg p-4" id="column-pending" ondragover="allowDrop(event)" ondrop="dropTask(event, 'pending')">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-medium">To Do</h3>
                                <div class="flex items-center">
                                    <span class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full" id="pending-count">
                                        {{ $pendingTasks->count() }}
                                    </span>
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
                                    <span class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full" id="in_progress-count">
                                        {{ $inProgressTasks->count() }}
                                    </span>
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

                        <!-- Paused Column (Previously Review) -->
                        <div class="bg-yellow-100 rounded-lg p-4" id="column-paused" ondragover="allowDrop(event)" ondrop="dropTask(event, 'paused')">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-medium">Paused</h3>
                                <div class="flex items-center">
                                    <span class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full" id="paused-count">
                                        {{ $reviewTasks->count() }}
                                    </span>
                                </div>
                            </div>
                            <div class="task-container space-y-2">
                                @foreach($reviewTasks as $task)
                                <div class="bg-white rounded shadow p-3 cursor-pointer task-card"
                                     draggable="true"
                                     ondragstart="dragTask(event, {{ $task->id }})"
                                     data-task-id="{{ $task->id }}"
                                     data-status="paused"
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
                                    <span class="bg-white text-gray-600 text-xs px-2 py-1 rounded-full" id="completed-count">
                                        {{ $completedTasks->count() }}
                                    </span>
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
    <!-- Add this task modal HTML right before your script tag in kanban.blade.php -->
    <!-- Task Modal -->
    <div id="taskModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="absolute inset-0 bg-black bg-opacity-40" onclick="closeTaskModal()"></div>
        <div class="bg-white rounded-lg shadow-lg w-full max-w-xl relative z-10 flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h3 id="modal-title" class="text-lg font-medium text-gray-800">タスクを編集</h3>
                <button type="button" onclick="closeTaskModal()" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-4 overflow-y-auto">
                <!-- Task Form - Using redirects to prevent unwanted image display -->
                <form id="taskForm" method="POST" target="_self">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    <input type="hidden" id="taskId" name="id" value="">

                    <div class="space-y-4">
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">
                                タイトル<span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="taskTitle" name="title" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Date -->
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700">期限日</label>
                            <input type="date" id="taskDueDate" name="due_date"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Time -->
                        <div>
                            <label for="due_time" class="block text-sm font-medium text-gray-700">時間</label>
                            <input type="time" id="taskDueTime" name="due_time"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">ステータス</label>
                            <select id="taskStatus" name="status"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="pending">To Do</option>
                                <option value="in_progress">In Progress</option>
                                <option value="paused">Paused</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">説明</label>
                            <textarea id="taskDescription" name="description" rows="3"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                    </div>
                </form>

                <!-- Delete Form -->
                <form id="deleteForm" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 flex justify-between">
                <div>
                    <button type="button" id="deleteTaskBtn" onclick="confirmDeleteTask()"
                        class="inline-flex items-center px-3 py-1.5 border border-red-300 text-sm font-medium rounded text-red-700 bg-white hover:bg-red-50">
                        削除
                    </button>
                </div>
                <div class="flex space-x-2">
                    <button type="button" onclick="closeTaskModal()"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                        キャンセル
                    </button>
                    <button type="button" onclick="saveTaskWithAjax()"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded text-white bg-blue-600 hover:bg-blue-700">
                        保存
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables to store task data
        var draggedTaskId = null;
        let currentTaskId = null;
        let isNewTask = false;

        // Drag and drop functionality
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
            const columns = ['pending', 'in_progress', 'paused', 'completed'];

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

                // Log the request for debugging
                console.log(`Updating task ${taskId} to status ${newStatus}`);

                // Use a custom endpoint specifically for status updates
                const response = await fetch(`/tasks/status/${taskId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        status: newStatus
                    })
                });

                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                }

                console.log(`Successfully updated task ${taskId} status to ${newStatus}`);

            } catch (error) {
                console.error("Error updating task status:", error);
                alert("タスクのステータス更新に失敗しました。ページを更新します。");
                // Reload the page if status update fails to ensure UI is in sync
                window.location.reload();
            }
        }

        // Modal functions
        function openTaskModal(taskId = null) {
            const modal = document.getElementById('taskModal');
            const form = document.getElementById('taskForm');
            const modalTitle = document.getElementById('modal-title');
            const deleteButton = document.getElementById('deleteTaskBtn');
            const formMethodInput = document.getElementById('formMethod');

            if (taskId) {
                // Edit existing task
                currentTaskId = taskId;
                isNewTask = false;
                modalTitle.textContent = 'タスクを編集';
                deleteButton.classList.remove('hidden');

                // For Ajax submission later
                form.action = `/api/todos/${taskId}`;
                formMethodInput.value = 'PUT';

                // Fetch task data and populate form
                fetchTaskData(taskId);
            } else {
                // Create new task
                currentTaskId = null;
                isNewTask = true;
                modalTitle.textContent = '新しいタスク';
                deleteButton.classList.add('hidden');

                // For Ajax submission later
                form.action = '/api/todos';
                formMethodInput.value = 'POST';

                // Remove task ID field for new tasks
                document.getElementById('taskId').value = '';

                // Set default values for new task
                resetTaskForm();
            }

            modal.classList.remove('hidden');
        }

        function closeTaskModal() {
            const modal = document.getElementById('taskModal');
            modal.classList.add('hidden');
        }

        async function fetchTaskData(taskId) {
            try {
                const response = await fetch(`/api/todos/${taskId}?no_extras=1`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Failed to fetch task: ${response.status}`);
                }

                const task = await response.json();
                console.log("Fetched task data:", task);

                // Populate form fields
                document.getElementById('taskId').value = task.id;
                document.getElementById('taskTitle').value = task.title || '';
                document.getElementById('taskDescription').value = task.description || '';
                document.getElementById('taskStatus').value = task.status || 'pending';

                // Format date (YYYY-MM-DD)
                if (task.due_date) {
                    let formattedDate = task.due_date;
                    // If it contains time component, extract just the date
                    if (task.due_date.includes('T')) {
                        formattedDate = task.due_date.split('T')[0];
                    }
                    document.getElementById('taskDueDate').value = formattedDate;
                } else {
                    document.getElementById('taskDueDate').value = '';
                }

                // Format time (HH:MM)
                if (task.due_time) {
                    let timeStr = task.due_time;
                    if (timeStr.includes('T')) {
                        // Extract time part from ISO datetime
                        timeStr = timeStr.split('T')[1].substring(0, 5);
                    } else if (timeStr.includes(':')) {
                        // Format HH:MM:SS to HH:MM
                        timeStr = timeStr.substring(0, 5);
                    }
                    document.getElementById('taskDueTime').value = timeStr;
                } else {
                    document.getElementById('taskDueTime').value = '';
                }

            } catch (error) {
                console.error('Error fetching task data:', error);
                alert('タスクデータの取得に失敗しました。');
                closeTaskModal();
            }
        }

        function resetTaskForm() {
            document.getElementById('taskForm').reset();

            // Set today's date as default for new tasks
            const today = new Date();
            const formattedDate = today.toISOString().split('T')[0];
            document.getElementById('taskDueDate').value = formattedDate;

            // Set default status from parameter or use 'pending'
            const urlParams = new URLSearchParams(window.location.search);
            const defaultStatus = urlParams.get('status') || 'pending';
            document.getElementById('taskStatus').value = defaultStatus;

            // Clear description
            document.getElementById('taskDescription').value = '';
        }

        // Save task using Ajax to prevent page redirect and image display
        async function saveTaskWithAjax() {
            try {
                const form = document.getElementById('taskForm');
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // Create FormData and convert to object
                const formData = new FormData(form);
                const taskData = {};
                formData.forEach((value, key) => {
                    if (key !== '_token' && (key !== 'id' || value)) { // Exclude CSRF token and empty ID
                        taskData[key] = value;
                    }
                });

                // Add method for Laravel
                if (!isNewTask) {
                    taskData._method = 'PUT';
                }

                console.log('Sending task data:', taskData);

                // Use the correct URL based on whether it's a new task or updating
                const url = isNewTask ? '/api/todos' : `/api/todos/${currentTaskId}`;

                // Make the request
                const response = await fetch(url, {
                    method: 'POST', // Always POST, method spoofing handled by _method
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(taskData)
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || `Server returned ${response.status}: ${response.statusText}`);
                }

                console.log(`Task ${isNewTask ? 'created' : 'updated'} successfully`);

                // Close modal first
                closeTaskModal();

                // Then reload page to show updated tasks
                window.location.reload();

            } catch (error) {
                console.error('Error saving task:', error);
                alert(`タスクの${isNewTask ? '作成' : '更新'}に失敗しました。: ${error.message}`);
            }
        }

        function confirmDeleteTask() {
            if (confirm('このタスクを削除してもよろしいですか？この操作は元に戻せません。')) {
                deleteTask();
            }
        }

        async function deleteTask() {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const response = await fetch(`/api/todos/${currentTaskId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                }

                console.log('Task deleted successfully');

                // Close the modal and reload the page
                closeTaskModal();
                window.location.reload();

            } catch (error) {
                console.error('Error deleting task:', error);
                alert('タスクの削除に失敗しました。');
            }
        }

        // Override the existing editTask function to use our modal
        function editTask(event, taskId) {
            // Prevent default click behavior
            event.preventDefault();
            event.stopPropagation();

            // Open our custom modal
            openTaskModal(taskId);
        }

        // Open task modal for creating a new task
        function addNewTask() {
            openTaskModal(null);
        }

        // Function to create a new task with a specific status
        function addNewTaskWithStatus(status) {
            // Set the URL parameter for the default status
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('status', status);
            window.history.replaceState({}, '', `${window.location.pathname}?${urlParams.toString()}`);

            // Open the modal for a new task
            openTaskModal(null);
        }
    </script>
</x-app-layout>
