<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Channels\LineNotifyChannel;
use App\Services\LineNotifyService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the Line Notify channel
        Notification::extend("line", function ($app) {
            return new LineNotifyChannel($app->make(LineNotifyService::class));
        });
    }
}
