<?php

namespace Plutuss\Providers;

use Illuminate\Support\ServiceProvider;
use JamesHeinrich\GetID3\GetID3;
use Plutuss\Services\MediaAnalyzerService;
use Plutuss\Services\MediaAnalyzerServiceInterface;
use Plutuss\Services\MediaManagerService;
use Plutuss\Services\MediaManagerServiceInterface;

class GetId3ServiceProvider extends ServiceProvider
{

    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/getid3.php', 'getid3');

        $this->app->bind(MediaManagerServiceInterface::class, MediaManagerService::class);

        $this->app->bind(MediaAnalyzerServiceInterface::class, function ($app): MediaAnalyzerServiceInterface {
            return new MediaAnalyzerService(new GetID3(), $app->make(MediaManagerServiceInterface::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/getid3.php' => config_path('getid3.php'),
        ]);
    }
}
