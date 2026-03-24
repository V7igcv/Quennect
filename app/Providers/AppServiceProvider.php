<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Services\Sms\SmsSenderManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsSender::class, function () {
            return (new SmsSenderManager())->driver();
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
