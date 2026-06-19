<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\AiAnalysisService::class);
        $this->app->singleton(\App\Services\ContactMailService::class);
        $this->app->singleton(\App\Services\ContactService::class);
    }
}
