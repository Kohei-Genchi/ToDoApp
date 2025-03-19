<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            window.Laravel = {!! json_encode([
                'user' => Auth::check() ? [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                ] : null,
            ]) !!};
        </script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 flex flex-col md:flex-row">
            <!-- Mobile menu toggle button (only visible on small screens) -->
            <div class="md:hidden fixed top-0 left-0 z-50 p-4">
                <button id="sidebarToggle" class="text-gray-500 hover:text-gray-900 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Sidebar -->
            <div id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out md:relative md:sticky md:top-0 md:h-screen">
                @include('layouts.navigation')
            </div>

            <!-- Page Content -->
            <main class="flex-1 w-full transition-all duration-300 p-4 md:p-6">
                {{ $slot }}
            </main>
        </div>

        <script>
            // Sidebar toggle functionality
            document.addEventListener('DOMContentLoaded', function() {
                const sidebar = document.getElementById('sidebar');
                const sidebarToggle = document.getElementById('sidebarToggle');

                if (sidebarToggle && sidebar) {
                    sidebarToggle.addEventListener('click', function() {
                        sidebar.classList.toggle('-translate-x-full');
                    });

                    // Close sidebar when clicking outside on mobile
                    document.addEventListener('click', function(event) {
                        const isClickInsideSidebar = sidebar.contains(event.target);
                        const isClickOnToggle = sidebarToggle.contains(event.target);

                        // Only handle clicks outside sidebar and toggle on mobile
                        if (window.innerWidth < 768 && !isClickInsideSidebar && !isClickOnToggle) {
                            sidebar.classList.add('-translate-x-full');
                        }
                    });

                    // Handle resize events
                    window.addEventListener('resize', function() {
                        if (window.innerWidth >= 768) {
                            sidebar.classList.remove('-translate-x-full');
                        } else {
                            sidebar.classList.add('-translate-x-full');
                        }
                    });
                }
            });
        </script>
    </body>
</html>
