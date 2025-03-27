<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Channels\SlackNotifyChannel;
use App\Services\SlackNotifyService;

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
        // Register the Slack channel
        Notification::extend("slack", function ($app) {
            return new SlackNotifyChannel(
                $app->make(SlackNotifyService::class)
            );
        });
    }
}
