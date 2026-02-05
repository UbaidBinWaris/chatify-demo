<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Override Chatify Messenger with our custom implementation
        $this->app->singleton(\Chatify\ChatifyMessenger::class, function ($app) {
            return new \App\Services\ChatifyMessengerOverride();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
