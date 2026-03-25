<?php

namespace App\Providers;

use App\Services\TwitchApiService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TwitchApiService::class);
    }

    public function boot(): void
    {
        //
    }
}
