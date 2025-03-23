<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionRequired
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Check if user exists and has a subscription_id
        if (!$user || !$user->subscription_id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'このサービスを利用するにはサブスクリプションが必要です。'
                ], 403);
            }

            return redirect()->route('stripe.subscription')
                ->with('error', 'このサービスを利用するにはサブスクリプションが必要です。');
        }

        return $next($request);
    }
}
