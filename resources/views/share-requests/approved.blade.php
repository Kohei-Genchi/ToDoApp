{{-- resources/views/share-requests/approved.blade.php --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center h-24 w-24 rounded-full bg-green-100 mb-6">
                            <svg class="h-12 w-12 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <h2 class="text-xl font-semibold text-gray-800 mb-4">共有リクエストを承認しました</h2>
                        <p class="mb-4 text-gray-600">{{ $requesterName }}さんからの{{ $shareType }}共有を承認しました。</p>

                        <div class="mt-8">
                            <a href="{{ route('todos.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                タスク一覧に移動
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

{{-- resources/views/share-requests/rejected.blade.php --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center h-24 w-24 rounded-full bg-red-100 mb-6">
                            <svg class="h-12 w-12 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>

                        <h2 class="text-xl font-semibold text-gray-800 mb-4">共有リクエストを拒否しました</h2>
                        <p class="mb-4 text-gray-600">{{ $requesterName }}さんからの{{ $shareType }}共有を拒否しました。</p>

                        <div class="mt-8">
                            <a href="{{ route('todos.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                タスク一覧に移動
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

{{-- resources/views/share-requests/error.blade.php --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center h-24 w-24 rounded-full bg-red-100 mb-6">
                            <svg class="h-12 w-12 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ $title ?? 'エラーが発生しました' }}</h2>
                        <p class="mb-4 text-gray-600">{{ $message }}</p>

                        <div class="mt-8">
                            <a href="{{ route('todos.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                タスク一覧に移動
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

{{-- resources/views/share-requests/index.blade.php --}}
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">共有リクエスト</h2>

                    <!-- Outgoing Requests -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-700 mb-4">送信済みリクエスト</h3>

                        @if($outgoingRequests->isEmpty())
                            <div class="bg-gray-50 p-4 rounded-md text-gray-500 text-center">
                                送信済みの共有リクエストはありません
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($outgoingRequests as $request)
                                    <div class="bg-blue-50 border border-blue-100 rounded-md p-4">
                                        <div class="flex justify-between">
                                            <div>
                                                <p class="font-medium">
                                                    @if($request->share_type === 'task')
                                                        タスク: {{ $request->todo->title }}
                                                    @else
                                                        全てのタスク
                                                    @endif
                                                </p>
                                                <p class="text-sm text-gray-600">受信者: {{ $request->recipient_email }}</p>
                                                <p class="text-sm text-gray-600">
                                                    権限: {{ $request->permission === 'edit' ? '編集可能' : '閲覧のみ' }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ $request->created_at->format('Y-m-d H:i') }} 送信
                                                    (期限: {{ $request->expires_at->format('Y-m-d H:i') }})
                                                </p>
                                            </div>
                                            <form action="{{ route('api.share-requests.cancel', $request->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm text-red-500 hover:text-red-700"
                                                        onclick="return confirm('このリクエストをキャンセルしますか？')">
                                                    キャンセル
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Incoming Requests -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-700 mb-4">受信したリクエスト</h3>

                        @if($incomingRequests->isEmpty())
                            <div class="bg-gray-50 p-4 rounded-md text-gray-500 text-center">
                                受信した共有リクエストはありません
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($incomingRequests as $request)
                                    <div class="bg-green-50 border border-green-100 rounded-md p-4">
                                        <div>
                                            <p class="font-medium">
                                                @if($request->share_type === 'task')
                                                    タスク: {{ $request->todo ? $request->todo->title : '[削除されました]' }}
                                                @else
                                                    全てのタスク
                                                @endif
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                送信者: {{ $request->requester->name }} ({{ $request->requester->email }})
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                権限: {{ $request->permission === 'edit' ? '編集可能' : '閲覧のみ' }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ $request->created_at->format('Y-m-d H:i') }} 受信
                                                (期限: {{ $request->expires_at->format('Y-m-d H:i') }})
                                            </p>
                                        </div>
                                        <div class="mt-3 flex space-x-2">
                                            <a href="{{ URL::signedRoute('share-requests.web.approve', ['token' => $request->token]) }}"
                                               class="inline-flex items-center px-3 py-1 bg-green-500 border border-transparent rounded-md text-xs text-white hover:bg-green-600">
                                                承認
                                            </a>
                                            <a href="{{ URL::signedRoute('share-requests.web.reject', ['token' => $request->token]) }}"
                                               class="inline-flex items-center px-3 py-1 bg-gray-500 border border-transparent rounded-md text-xs text-white hover:bg-gray-600">
                                                拒否
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
