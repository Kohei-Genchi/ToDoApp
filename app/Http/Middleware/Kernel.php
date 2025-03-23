<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];
    protected $middlewareGroups = [
            'web' => [
                \App\Http\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \App\Http\Middleware\VerifyCsrfToken::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
            ],

    protected $middlewareGroups = [
        "api" => [
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ":api",
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];
    public function bootstrap()
        {
            parent::bootstrap();

            $this->app->middleware->alias('subscription', \App\Http\Middleware\SubscriptionRequired::class);
        }
}
