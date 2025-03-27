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
                            <h1 class="ml-4 text-xl font-semibold text-gray-800">Analytics Dashboard</h1>
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
                <!-- Dashboard Overview -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4 mb-6">
                    <!-- Total Tasks Card -->
                    <div class="bg-white rounded-lg shadow p-5">
                        <div class="flex justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Total Tasks</h3>
                                <p class="text-2xl font-semibold text-gray-900">--</p>
                            </div>
                            <div class="bg-blue-50 p-3 rounded-full">
                                <svg class="h-6 w-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Completed Tasks Card -->
                    <div class="bg-white rounded-lg shadow p-5">
                        <div class="flex justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Completed</h3>
                                <p class="text-2xl font-semibold text-gray-900">--</p>
                            </div>
                            <div class="bg-green-50 p-3 rounded-full">
                                <svg class="h-6 w-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- In Progress Tasks Card -->
                    <div class="bg-white rounded-lg shadow p-5">
                        <div class="flex justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">In Progress</h3>
                                <p class="text-2xl font-semibold text-gray-900">--</p>
                            </div>
                            <div class="bg-yellow-50 p-3 rounded-full">
                                <svg class="h-6 w-6 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Team Members Card -->
                    <div class="bg-white rounded-lg shadow p-5">
                        <div class="flex justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Team Members</h3>
                                <p class="text-2xl font-semibold text-gray-900">--</p>
                            </div>
                            <div class="bg-purple-50 p-3 rounded-full">
                                <svg class="h-6 w-6 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Task Completion Chart -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Task Completion Rate</h2>
                        <div class="h-64" id="completion-chart">
                            <div class="flex items-center justify-center h-full">
                                <p class="text-gray-500">Loading chart...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks by Category Chart -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Tasks by Category</h2>
                        <div class="h-64" id="category-chart">
                            <div class="flex items-center justify-center h-full">
                                <p class="text-gray-500">Loading chart...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow mt-6 p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h2>
                    <div id="recent-activity">
                        <div class="text-center py-12">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-gray-500 text-lg font-medium">Activity will appear here</h3>
                            <p class="mt-2 text-gray-500">When team members complete tasks, you'll see it here</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Placeholder for chart components
            // We will implement these later with dedicated Vue components

            // For now, we'll just show an "under development" message
            const chartPlaceholders = document.querySelectorAll('#completion-chart, #category-chart');
            chartPlaceholders.forEach(placeholder => {
                placeholder.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full">
                        <svg class="w-12 h-12 text-gray-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                        <p class="text-gray-500 font-medium">Analytics Under Development</p>
                        <p class="text-sm text-gray-400 mt-1">Dashboard visualizations coming soon</p>
                    </div>
                `;
            });
        });
    </script>
    @endpush
</x-app-layout>
