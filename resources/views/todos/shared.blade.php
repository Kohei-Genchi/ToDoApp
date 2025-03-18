<x-app-layout>
    <div class="max-w-6xl mx-auto p-4">
        <div class="mb-4 flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900">共有タスク</h2>
            <a href="{{ route('todos.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                ← タスク一覧に戻る
            </a>
        </div>

        @if($sharedTasks->isEmpty())
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-700 mb-2">
                    共有されたタスクはありません
                </h3>
                <p class="text-sm text-gray-500 mb-4">他のユーザーからタスクが共有されるとここに表示されます</p>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm">
                <ul class="divide-y divide-gray-100">
                    @foreach($sharedTasks as $task)
                        <li class="p-4 hover:bg-gray-50">
                            <div class="flex justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <!-- Checkbox -->
                                        <form action="{{ route('todos.toggle', $task) }}" method="POST" class="mr-3 flex-shrink-0">
                                            @csrf
                                            @method('PATCH')
                                            <input type="checkbox"
                                                onChange="this.form.submit()"
                                                {{ $task->status === 'completed' ? 'checked' : '' }}
                                                {{ $task->pivot->permission === 'view' ? 'disabled' : '' }}
                                                class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500">
                                        </form>

                                        <!-- Task title -->
                                        <p class="font-medium {{ $task->status === 'completed' ? 'line-through text-gray-500' : 'text-gray-900' }}">
                                            {{ $task->title }}
                                        </p>

                                        <!-- Task category -->
                                        @if($task->category)
                                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium"
                                                  style="background-color: {{ hexToRgba($task->category->color, 0.15) }}; color: {{ $task->category->color }};">
                                                {{ $task->category->name }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Task metadata -->
                                    <div class="mt-1 text-sm text-gray-500">
                                        <div class="flex items-center space-x-4">
                                            <!-- Owner -->
                                            <div class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <span>{{ $task->user->name }}</span>
                                            </div>

                                            <!-- Due date -->
                                            @if($task->due_date)
                                                <div class="flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>{{ $task->due_date->format('Y年m月d日') }}</span>
                                                </div>
                                            @endif

                                            <!-- Due time -->
                                            @if($task->due_time)
                                                <div class="flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span>{{ $task->due_time->format('H:i') }}</span>
                                                </div>
                                            @endif

                                            <!-- Permission -->
                                            <div class="flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                                </svg>
                                                <span>{{ $task->pivot->permission === 'edit' ? '編集可能' : '閲覧のみ' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action buttons -->
                                <div class="flex items-start space-x-2">
                                    @if($task->pivot->permission === 'edit')
                                        <button
                                            onclick="editTodo({{ $task->id }}, {{ json_encode([
                                                'title' => $task->title,
                                                'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                                                'due_time' => $task->due_time ? $task->due_time->format('H:i') : null,
                                                'category_id' => $task->category_id,
                                                'recurrence_type' => $task->recurrence_type,
                                                'recurrence_end_date' => $task->recurrence_end_date ? $task->recurrence_end_date->format('Y-m-d') : null,
                                            ]) }})"
                                            class="text-blue-600 hover:text-blue-800"
                                            title="編集"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
