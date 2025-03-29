{{-- resources/views/tasks/team-members.blade.php --}}
<x-app-layout>
    <div class="flex flex-col h-screen">
        <!-- Top Nav Bar -->
        <div class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <button id="sidebarToggle" class="text-gray-500 focus:outline-none">
                            </button>
                            <h1 class="ml-4 text-xl font-semibold text-gray-800">Team Members</h1>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <nav class="flex space-x-4 text-sm">
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
        <div class="flex-1 bg-gray-50 p-6 overflow-auto">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white rounded-lg shadow p-6">


                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-medium text-gray-900">Team Collaboration</h2>
                        <a href="{{ route('categories.index') }}" class="px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                            カテゴリーを管理する
                        </a>
                    </div>

                    <!-- Team Members List -->
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-700 mb-2">現在のチームメンバー</h3>

                        @if($teamMembers->isEmpty())
                            <p class="text-gray-500">カテゴリーを共有したユーザーがここに表示されます。まずはカテゴリーを共有してみましょう。</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="py-2 px-4 border-b text-left">名前</th>
                                            <th class="py-2 px-4 border-b text-left">メールアドレス</th>
                                            <th class="py-2 px-4 border-b text-left">関係</th>
                                            <th class="py-2 px-4 border-b text-left">共有カテゴリー</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($teamMembers as $member)
                                            <tr class="hover:bg-gray-50">
                                                <td class="py-3 px-4 border-b">{{ $member['name'] }}</td>
                                                <td class="py-3 px-4 border-b">{{ $member['email'] }}</td>
                                                <td class="py-3 px-4 border-b">
                                                    @if($member['relationship'] === 'shared_with_me')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                            <svg class="h-3 w-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                            </svg>
                                                            共有者
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                            <svg class="h-3 w-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                            </svg>
                                                            共有先
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4 border-b">
                                                    @foreach($member['categories'] as $category)
                                                        <span class="inline-block px-2 py-1 bg-gray-100 text-xs rounded-full mr-1 mb-1">{{ $category }}</span>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Shared Categories Section -->
                <div class="mt-8 bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">共有カテゴリー</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Categories shared with me -->
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

                        <!-- My categories shared with others -->
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
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
