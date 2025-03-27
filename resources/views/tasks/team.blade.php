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
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-medium text-gray-900">Team Collaboration</h2>
                        <button id="inviteButton" class="px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                            Invite Team Member
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Team Members Table -->
                        <div class="overflow-x-auto">
                            <div id="team-members-app">
                                <!-- Team Members Vue Component will mount here -->
                                <!-- This is a placeholder until we build the Vue component -->
                                <div class="text-center py-12">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <h3 class="text-gray-500 text-lg font-medium">Team members will appear here</h3>
                                    <p class="mt-2 text-gray-500">You can invite team members to collaborate on tasks</p>
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
