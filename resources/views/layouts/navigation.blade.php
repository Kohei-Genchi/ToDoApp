<!-- resources/views/layouts/navigation.blade.php -->
<nav class="bg-gray-800 text-white h-full w-full overflow-y-auto">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="pt-6 px-4 pb-4">
        @auth
            <div class="flex justify-start items-center pl-20 mb-12 cursor-pointer" onclick="toggleUserDropdown()">
                <div class="flex items-center">
                    <!-- User icon before username -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="font-bold truncate">{{ Auth::user()->name }}</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            <!-- Dropdown Menu - Hidden by default -->
            <div id="userDropdown" class="relative">
                <div class="absolute z-10 left-0 mt-2 w-48 bg-gray-700 rounded-md shadow-lg hidden border border-gray-600">
                    <div class="py-1">
                        <a href="{{ route('home') }}" class="flex items-center px-4 py-2 text-sm text-gray-200 hover:bg-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Home
                        </a>
                        <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-200 hover:bg-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile
                        </a>
                        <a href="{{ route('stripe.subscription') }}" class="flex items-center px-4 py-2 text-sm text-gray-200 hover:bg-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9a2 2 0 10-4 0v5a2 2 0 104 0V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h6m-6 6h6" />
                            </svg>
                            Subscription
                        </a>
                        <button onclick="confirmLogout()" class="flex items-center w-full text-left px-4 py-2 text-sm text-gray-200 hover:bg-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </button>
                        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        @else
            <div class="flex justify-between items-center ml-8 mb-6">
                <span class="font-bold">ゲスト</span>
                <div class="flex space-x-3">
                    <a href="{{ route('login') }}" class="text-sm text-gray-400 hover:text-white" title="ログイン">
                        🔑
                    </a>
                    <a href="{{ route('register') }}" class="text-sm text-gray-400 hover:text-white" title="新規登録">
                        📝
                    </a>
                </div>
            </div>
        @endauth

        @auth
            @if(!Request::is('profile*') && !Request::is('stripe/subscription*'))
                <!-- Mount Vue.js component for quick input and memo list -->
                <div id="sidebar-memos" class="mt-4">
                    <!-- Vue.js SidebarMemosComponent will be mounted here -->
                </div>
            @endif
        @else
            <div class="mt-6 p-3 bg-gray-700 rounded text-xs text-gray-300">
                <p>タスク管理機能を使用するには、ログインまたは新規登録してください。</p>
            </div>
        @endauth
    </div>
    <script>
        // Toggle user dropdown visibility
        function toggleUserDropdown() {
            const dropdown = document.querySelector('#userDropdown > div');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.flex.justify-start.items-center.cursor-pointer');
            const dropdown = document.querySelector('#userDropdown > div');

            if (dropdown && userMenu &&
                !dropdown.contains(event.target) &&
                !userMenu.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Function to confirm logout
        function confirmLogout() {
            if (confirm('ログアウトしてもよろしいですか？')) {
                document.getElementById('logout-form').submit();
            }
        }
    </script>
</nav>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(55, 65, 81, 0.5);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.5);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(156, 163, 175, 0.8);
}
</style>
