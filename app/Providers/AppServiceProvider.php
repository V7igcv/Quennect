<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Services\Sms\NullSmsSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsSender::class, function () {
            return match (config('sms.driver', 'null')) {
                'null' => new NullSmsSender(),
                default => new NullSmsSender(),
            };
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
