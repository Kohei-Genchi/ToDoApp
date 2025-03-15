<x-app-layout>
    <div class="max-w-6xl mx-auto p-4">

        @include('todos.partials.nav-tabs')


        <div class="mb-6 bg-white p-6 rounded-lg shadow-sm">
            <h2 class="text-lg font-medium mb-4">新しいカテゴリー</h2>
            <form action="{{ route('categories.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">カテゴリー名</label>
                    <input type="text" name="name" id="name" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700">カラー</label>
                    <div class="mt-1 flex items-center">
                        <input type="color" name="color" id="color" value="#3B82F6"
                               class="h-8 w-12 border border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-500">カテゴリーの色を選択</span>
                    </div>
                </div>
                <div>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        カテゴリーを追加
                    </button>
                </div>
            </form>
        </div>


        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h2 class="text-lg font-medium mb-4">カテゴリー一覧</h2>
            @if($categories->isEmpty())
                <p class="text-gray-500 text-center py-4">カテゴリーはありません</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach($categories as $category)
                        <li class="py-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="w-4 h-4 rounded-full mr-3" style="background-color: {{ $category->color }}"></span>
                                    <span class="font-medium">{{ $category->name }}</span>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="openEditModal({{ $category->id }}, '{{ $category->name }}', '{{ $category->color }}')"
                                            class="text-sm text-blue-500 hover:text-blue-700">
                                        編集
                                    </button>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST"
                                          onsubmit="return confirm('このカテゴリーを削除しますか？関連するタスクのカテゴリーも削除されます。')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-500 hover:text-red-700">
                                            削除
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>


        <div id="edit-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
            <div class="absolute inset-0 bg-black bg-opacity-30" onclick="closeEditModal()"></div>
            <div class="bg-white rounded-lg shadow-md w-full max-w-md relative z-10 p-6">
                <h3 class="text-lg font-medium mb-4">カテゴリーの編集</h3>
                <form id="edit-form" action="" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="edit-name" class="block text-sm font-medium text-gray-700">カテゴリー名</label>
                        <input type="text" name="name" id="edit-name" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    </div>
                    <div>
                        <label for="edit-color" class="block text-sm font-medium text-gray-700">カラー</label>
                        <div class="mt-1 flex items-center">
                            <input type="color" name="color" id="edit-color"
                                   class="h-8 w-12 border border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-500">カテゴリーの色を選択</span>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeEditModal()"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                            キャンセル
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            更新
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openEditModal(id, name, color) {
                document.getElementById('edit-form').action = `/categories/${id}`;
                document.getElementById('edit-name').value = name;
                document.getElementById('edit-color').value = color;
                document.getElementById('edit-modal').classList.remove('hidden');
            }

            function closeEditModal() {
                document.getElementById('edit-modal').classList.add('hidden');
            }

            // ESCキーでモーダルを閉じる
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeEditModal();
                }
            });
        </script>
    </div>
</x-app-layout>
