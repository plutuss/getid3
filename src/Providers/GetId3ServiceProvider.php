<?php

namespace Plutuss\Providers;

use Illuminate\Support\ServiceProvider;
use Plutuss\Services\MediaAnalyzerService;
use Plutuss\Services\MediaAnalyzerServicesInterface;

class GetId3ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('getid3.media', MediaAnalyzerServicesInterface::class);
        $this->app->singleton(MediaAnalyzerServicesInterface::class, MediaAnalyzerService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
