<?php
// Fix 1: Update AuthenticatedSessionController.php
// app/Http/Controllers/Auth/AuthenticatedSessionController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Log the login attempt for debugging
        Log::info('Login attempt for email: ' . $request->email);

        try {
            // Attempt authentication
            $request->authenticate();

            // Regenerate the session
            $request->session()->regenerate();

            // Log successful login
            Log::info('User authenticated successfully: ' . Auth::id());

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (\Exception $e) {
            // Log authentication failure
            Log::error('Authentication error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
