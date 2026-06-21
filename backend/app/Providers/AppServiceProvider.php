<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\AiAnalysisService::class);
        $this->app->singleton(\App\Services\ContactMailService::class);
        $this->app->singleton(\App\Services\ContactService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('contact', function (Request $request) {
            return Limit::perMinute(
                (int) env('CONTACT_RATE_LIMIT', 10)
            )->by($request->ip());
        });
    }
}
