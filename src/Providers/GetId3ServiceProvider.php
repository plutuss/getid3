<?php

namespace Plutuss\Providers;

use Illuminate\Support\ServiceProvider;
use JamesHeinrich\GetID3\GetID3;
use Plutuss\Services\MediaAnalyzerService;
use Plutuss\Services\MediaAnalyzerServiceInterface;

class GetId3ServiceProvider extends ServiceProvider
{

    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('getid3.media', MediaAnalyzerServiceInterface::class);

        $this->app->singleton(MediaAnalyzerServiceInterface::class, function ($app) {
            return new MediaAnalyzerService(new GetID3);
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
