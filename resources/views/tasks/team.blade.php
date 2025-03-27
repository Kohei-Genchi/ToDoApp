<x-app-layout>
    <div class="flex flex-col h-screen">
        <!-- Top Nav Bar -->
        <div class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <button id="sidebarToggle" class="text-gray-500 focus:outline-none">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
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
                                    <!-- Usage Guide -->
                                    <div class="bg-blue-50 p-4 mb-6 rounded-lg border border-blue-200">
                                        <h3 class="text-lg font-semibold mb-2 text-blue-800">チームコラボレーションの使い方</h3>
                                        <ul class="list-disc ml-5 space-y-1 text-blue-700">
                                            <li>チームメンバーを招待してタスクを共有できます</li>
                                            <li>メンバーとはカテゴリーを共有することでタスクを共同管理できます</li>
                                            <li>まずはカテゴリーページで共有したいカテゴリーを作成してください</li>
                                            <li>次に下部の「共有カテゴリー」セクションを使用して共有を設定できます</li>
                                        </ul>
                                    </div>

                                    <div class="flex justify-between items-center mb-6">
                                        <h2 class="text-lg font-medium text-gray-900">Team Collaboration</h2>
                                        <a href="{{ route('categories.index') }}" class="px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                                            カテゴリーを管理する
                                        </a>
                                    </div>

                                    <div class="space-y-4">
                                        <!-- Team Members Description -->
                                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                            <div class="flex items-start">
                                                <svg class="w-6 h-6 text-yellow-500 mr-2 mt-1 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <div>
                                                    <h4 class="font-medium text-yellow-800">チームメンバー招待について</h4>
                                                    <p class="text-yellow-700 mt-1">メンバー招待は「カテゴリー」ページから行います。カテゴリーページでカテゴリーを選択し、「共有」ボタンをクリックしてユーザーを招待してください。</p>
                                                    <a href="{{ route('categories.index') }}" class="inline-block mt-2 text-sm text-blue-600 hover:text-blue-800">カテゴリーページへ移動</a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Team Members List -->
                                        <div class="overflow-x-auto">
                                            <div id="team-members-app">
                                                <div class="bg-white border rounded-lg p-4">
                                                    <h3 class="font-medium text-gray-700 mb-2">現在のチームメンバー</h3>
                                                    <p class="text-gray-500">カテゴリーを共有したユーザーがここに表示されます。まずはカテゴリーを共有してみましょう。</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                <!-- Category Sharing Section -->
                <div class="mt-8 bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Shared Categories</h2>

                    <div id="shared-categories-container">
                        <!-- SharedCategoriesView Vue Component will mount here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invite Modal -->
    <div id="inviteModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-30" id="inviteModalOverlay"></div>

            <div class="bg-white rounded-lg shadow-xl w-full max-w-md z-10 relative">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Invite Team Member</h3>

                    <form id="inviteForm">
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>

                        <div class="mb-6">
                            <label for="permission" class="block text-sm font-medium text-gray-700 mb-1">Permission</label>
                            <select id="permission" name="permission" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="view">View Only</option>
                                <option value="edit">Edit</option>
                            </select>
                        </div>

                        <div class="flex items-center mb-4">
                            <input type="checkbox" id="lineAuthRequired" name="lineAuthRequired" class="h-4 w-4 text-blue-600 border-gray-300 rounded" checked disabled>
                            <label for="lineAuthRequired" class="ml-2 block text-sm text-gray-900">
                                LINE Authentication Required (Mandatory)
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" id="cancelInvite" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Send Invitation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal handlers
            const inviteButton = document.getElementById('inviteButton');
            const inviteModal = document.getElementById('inviteModal');
            const inviteModalOverlay = document.getElementById('inviteModalOverlay');
            const cancelInviteButton = document.getElementById('cancelInvite');
            const inviteForm = document.getElementById('inviteForm');

            function openInviteModal() {
                inviteModal.classList.remove('hidden');
            }

            function closeInviteModal() {
                inviteModal.classList.add('hidden');
            }

            inviteButton.addEventListener('click', openInviteModal);
            inviteModalOverlay.addEventListener('click', closeInviteModal);
            cancelInviteButton.addEventListener('click', closeInviteModal);

            inviteForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const email = document.getElementById('email').value;
                const permission = document.getElementById('permission').value;

                // This is just a placeholder for now
                // We'll implement the actual invitation logic with a proper category selection
                alert(`Invitation would be sent to ${email} with ${permission} permissions.`);
                closeInviteModal();
            });

            // Mount the shared categories component
            const sharedCategoriesContainer = document.getElementById('shared-categories-container');
            if (sharedCategoriesContainer) {
                // Dynamically import the SharedCategoriesView component
                import('../js/components/SharedCategoriesView.vue').then(module => {
                    createApp(module.default).mount('#shared-categories-container');
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
