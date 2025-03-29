<x-app-layout>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="mb-4 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">共有カテゴリー</h2>
                    <a href="{{ route('categories.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-500 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                        カテゴリー管理へ戻る
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 自分と共有されているカテゴリー -->
                    <div>
                        <h3 class="font-medium text-gray-700 mb-3">自分と共有されているカテゴリー</h3>

                        @if($sharedWithMe->isEmpty())
                            <p class="text-gray-500">他のユーザーからの共有カテゴリーはありません。</p>
                        @else
                            <ul class="space-y-2">
                                @foreach($sharedWithMe as $category)
                                    <li class="p-3 bg-gray-50 rounded-md border border-gray-200 flex justify-between items-center">
                                        <div class="flex items-center">
                                            <span class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $category->color }}"></span>
                                            <span class="font-medium">{{ $category->name }}</span>
                                            <span class="ml-2 text-xs text-gray-500">{{ $category->user->name }} から</span>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded-full {{ $category->pivot->permission === 'edit' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ $category->pivot->permission === 'edit' ? '編集可能' : '閲覧のみ' }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <!-- 自分が共有しているカテゴリー -->
                    <div>
                        <h3 class="font-medium text-gray-700 mb-3">自分が共有しているカテゴリー</h3>

                        @if($mySharedCategories->isEmpty())
                            <p class="text-gray-500">まだカテゴリーを共有していません。カテゴリーページから共有設定ができます。</p>
                        @else
                            <ul class="space-y-2">
                                @foreach($mySharedCategories as $category)
                                    <li class="p-3 bg-gray-50 rounded-md border border-gray-200">
                                        <div class="flex items-center mb-2">
                                            <span class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $category->color }}"></span>
                                            <span class="font-medium">{{ $category->name }}</span>
                                        </div>

                                        <div class="pl-5">
                                            <p class="text-xs text-gray-500 mb-1">共有先:</p>
                                            @foreach($category->sharedWith as $user)
                                                <div class="flex justify-between items-center py-1">
                                                    <span class="text-sm">{{ $user->name }}</span>
                                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $user->pivot->permission === 'edit' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                                        {{ $user->pivot->permission === 'edit' ? '編集可能' : '閲覧のみ' }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                <div class="mt-8">
                    <h3 class="font-medium text-gray-700 mb-3">共有リクエスト</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- 送信中のリクエスト -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-600 mb-2">送信中のリクエスト</h4>

                            @if($outgoingRequests->isEmpty())
                                <p class="text-gray-500 text-sm">送信中のリクエストはありません。</p>
                            @else
                                <ul class="space-y-2">
                                    @foreach($outgoingRequests as $request)
                                        <li class="p-2 bg-gray-50 rounded border border-gray-200 text-sm">
                                            <div class="flex justify-between">
                                                <span>{{ $request->recipient_email }}</span>
                                                <span class="text-xs text-gray-500">{{ $request->created_at->format('Y/m/d') }}</span>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-600">
                                                {{ $request->item_name }} ({{ $request->permission === 'edit' ? '編集可能' : '閲覧のみ' }})
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <!-- 受信中のリクエスト -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-600 mb-2">受信中のリクエスト</h4>

                            @if($incomingRequests->isEmpty())
                                <p class="text-gray-500 text-sm">受信中のリクエストはありません。</p>
                            @else
                                <ul class="space-y-2">
                                    @foreach($incomingRequests as $request)
                                        <li class="p-2 bg-gray-50 rounded border border-gray-200 text-sm">
                                            <div class="flex justify-between">
                                                <span>{{ $request->requester_name }}</span>
                                                <span class="text-xs text-gray-500">{{ $request->created_at->format('Y/m/d') }}</span>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-600">
                                                {{ $request->item_name }} ({{ $request->permission === 'edit' ? '編集可能' : '閲覧のみ' }})
                                            </div>
                                            <div class="mt-2 flex space-x-2">
                                                <a href="{{ route('share-requests.web.approve', ['token' => $request->token]) }}" class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded hover:bg-green-200">
                                                    承認
                                                </a>
                                                <a href="{{ route('share-requests.web.reject', ['token' => $request->token]) }}" class="text-xs px-2 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200">
                                                    拒否
                                                </a>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
