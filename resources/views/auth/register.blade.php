<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-gray-800">{{ config('app.name', 'TodoList') }}</h1>
        <p class="text-gray-600 mt-1">Create your account</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="mb-4">
            <x-input-label for="name" value="Name" class="text-sm font-medium text-gray-700 mb-1" />
            <x-text-input id="name" class="block mt-1 w-full rounded-md" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mb-4">
            <x-input-label for="email" value="Email" class="text-sm font-medium text-gray-700 mb-1" />
            <x-text-input id="email" class="block mt-1 w-full rounded-md" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mb-4">
            <x-input-label for="password" value="Password" class="text-sm font-medium text-gray-700 mb-1" />
            <x-text-input id="password" class="block mt-1 w-full rounded-md" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <x-input-label for="password_confirmation" value="Confirm Password" class="text-sm font-medium text-gray-700 mb-1" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full rounded-md" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4 bg-blue-600 hover:bg-blue-700">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>

    <!-- Alternative Register Methods -->
    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">Or continue with</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-4">
            <a href="{{ url('/auth/google') }}" class="flex items-center justify-center py-2 px-4 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                <img src="https://developers.google.com/identity/images/g-logo.png" class="w-5 h-5 mr-2" alt="Google logo">
                Google
            </a>
            <a href="{{ route('guest.login') }}" class="flex items-center justify-center py-2 px-4 bg-green-500 border border-green-400 rounded-md shadow-sm text-sm font-medium text-white hover:bg-green-600">
                Quick Login
            </a>
        </div>
    </div>
</x-guest-layout>
