<x-app-layout>
    <div class="max-w-6xl mx-auto p-4">
        <div class="mb-4 flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900">タスク共有設定</h2>
            <a href="{{ route('todos.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                ← タスク一覧に戻る
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium mb-4">{{ $todo->title }}</h3>

            <!-- Current shares -->
            @if($sharedUsers->isNotEmpty())
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">共有済みユーザー</h4>
                    <ul class="space-y-3">
                        @foreach($sharedUsers as $user)
                            <li class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <div>
                                    <p class="text-sm font-medium">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <form action="{{ route('tasks.updateSharing', [$todo, $user]) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <select
                                            name="permission"
                                            onchange="this.form.submit()"
                                            class="text-xs border rounded p-1"
                                        >
                                            <option value="view" {{ $user->pivot->permission === 'view' ? 'selected' : '' }}>閲覧のみ</option>
                                            <option value="edit" {{ $user->pivot->permission === 'edit' ? 'selected' : '' }}>編集可能</option>
                                        </select>
                                    </form>

                                    <form action="{{ route('tasks.destroySharing', [$todo, $user]) }}" method="POST" class="inline"
                                          onsubmit="return confirm('{{ $user->name }} との共有を解除してもよろしいですか？')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="text-red-500 hover:text-red-700"
                                            title="共有解除"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Share limit notice -->
            @if($sharedUsers->count() >= 5)
                <div class="mb-6 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                    最大共有数 (5人) に達しています。新しく共有するには、既存の共有を解除してください。
                </div>
            @endif

            <!-- Share form -->
            @if($sharedUsers->count() < 5)
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">新しいユーザーと共有</h4>
                    <form action="{{ route('tasks.storeSharing', $todo) }}" method="POST">
                        @csrf
                        <div class="flex space-x-2">
                            <input
                                type="email"
                                name="email"
                                required
                                placeholder="メールアドレスを入力"
                                class="flex-1 border rounded p-2 text-sm"
                            />
                            <select name="permission" class="border rounded p-2 text-sm">
                                <option value="view">閲覧のみ</option>
                                <option value="edit">編集可能</option>
                            </select>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-500 text-white rounded text-sm hover:bg-blue-600 transition"
                            >
                                共有
                            </button>
                        </div>
                        @error('email')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
