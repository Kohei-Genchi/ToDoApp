{{-- resources/views/categories/index.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto p-4">
        {{-- Header is removed as requested --}}

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
                                            {{-- 共有ボタン --}}
                                            <button onclick="openShareModal({{ $category->id }}, '{{ $category->name }}', '{{ $category->color }}')"
                                                    class="flex items-center px-2 py-1 text-sm text-white bg-blue-500 hover:bg-blue-600 rounded">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                                </svg>
                                                共有
                                            </button>
                                            <button onclick="openEditModal({{ $category->id }}, '{{ $category->name }}', '{{ $category->color }}')"
                                                    class="px-2 py-1 text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 rounded">
                                                編集
                                            </button>
                                            <form action="{{ route('categories.destroy', $category) }}" method="POST"
                                                  onsubmit="return confirm('このカテゴリーを削除しますか？関連するタスクのカテゴリーも削除されます。')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2 py-1 text-sm text-red-600 bg-red-50 hover:bg-red-100 rounded">
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

        <!-- カテゴリー編集モーダル -->
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

        <!-- カテゴリー共有モーダル -->
        <div id="share-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
            <div class="absolute inset-0 bg-black bg-opacity-30" onclick="closeShareModal()"></div>
            <div class="bg-white rounded-lg shadow-md w-full max-w-md relative z-10 p-6">
                <h3 class="text-lg font-medium mb-4">カテゴリーを共有</h3>
                <div id="share-category-name" class="flex items-center mb-4">
                    <span id="share-color-dot" class="w-4 h-4 rounded-full mr-3"></span>
                    <span id="share-name" class="font-medium"></span>
                </div>

                <form id="share-form" class="space-y-4">
                    <div>
                        <label for="share-email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
                        <input type="email" id="share-email" required placeholder="共有する相手のメールアドレス"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label for="share-permission" class="block text-sm font-medium text-gray-700">権限</label>
                        <select id="share-permission"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <option value="view">閲覧のみ</option>
                            <option value="edit">編集可能</option>
                        </select>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="line-auth-required" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" checked>
                        <label for="line-auth-required" class="ml-2 block text-sm text-gray-900">
                            LINE認証を必須にする
                        </label>
                    </div>

                    <div id="share-error" class="text-red-500 text-sm hidden"></div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeShareModal()"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                            キャンセル
                        </button>
                        <button type="button" onclick="shareCategory()"
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            共有する
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            let currentCategoryId = null;

            // 編集モーダル関連の関数
            function openEditModal(id, name, color) {
                document.getElementById('edit-form').action = `/categories/${id}`;
                document.getElementById('edit-name').value = name;
                document.getElementById('edit-color').value = color;
                document.getElementById('edit-modal').classList.remove('hidden');
            }

            function closeEditModal() {
                document.getElementById('edit-modal').classList.add('hidden');
            }

            // 共有モーダル関連の関数
            function openShareModal(id, name, color) {
                currentCategoryId = id;
                document.getElementById('share-name').textContent = name;
                document.getElementById('share-color-dot').style.backgroundColor = color;
                document.getElementById('share-modal').classList.remove('hidden');
                document.getElementById('share-error').classList.add('hidden');
                document.getElementById('share-email').value = '';
            }

            function closeShareModal() {
                document.getElementById('share-modal').classList.add('hidden');
            }

            function shareCategory() {
                const email = document.getElementById('share-email').value;
                const permission = document.getElementById('share-permission').value;
                const lineAuthRequired = document.getElementById('line-auth-required').checked;

                if (!email) {
                    showShareError('メールアドレスを入力してください');
                    return;
                }

                // CSRFトークンを取得
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // APIリクエストを送信
                fetch(`/api/categories/${currentCategoryId}/shares`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        email: email,
                        permission: permission,
                        line_auth_required: lineAuthRequired
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(lineAuthRequired
                            ? 'カテゴリー共有リクエストが送信されました。相手はLINEで承認する必要があります。'
                            : 'カテゴリーが共有されました');
                        closeShareModal();
                    } else {
                        showShareError(data.error || '共有に失敗しました');
                    }
                })
                .catch(error => {
                    console.error('Error sharing category:', error);
                    showShareError('共有処理中にエラーが発生しました');
                });
            }

            function showShareError(message) {
                const errorElement = document.getElementById('share-error');
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
            }

            // ESCキーでモーダルを閉じる
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeEditModal();
                    closeShareModal();
                }
            });
        </script>
    </div>
</x-app-layout>
