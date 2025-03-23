<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Your existing policies
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define gate for subscription features
        Gate::define("access-subscription-features", function ($user) {
            return $user && $user->subscription_id !== null;
        });
    }
}
