<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Channels\CustomSlackChannel;
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

    public function boot(): void
    {
        // Register custom Slack channel with SlackNotifyService
        Notification::extend("custom-slack", function ($app) {
            return new CustomSlackChannel(
                $app->make(SlackNotifyService::class)
            );
        });
    }
}
