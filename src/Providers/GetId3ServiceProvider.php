<?php

namespace Plutuss\Providers;

use Illuminate\Support\ServiceProvider;
use JamesHeinrich\GetID3\GetID3;
use Plutuss\Download\DownloadOptions;
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

        $this->app->bind(DownloadOptions::class, fn () => new DownloadOptions(
            maxBytes: config('getid3.download.max_bytes'),
            timeout: (float) config('getid3.download.timeout', 30),
            readTimeout: (float) config('getid3.download.read_timeout', 30),
            connectTimeout: (float) config('getid3.download.connect_timeout', 10),
            maxRedirects: (int) config('getid3.download.max_redirects', 5),
            allowedHosts: config('getid3.download.allowed_hosts', []),
            allowPrivateNetworks: (bool) config('getid3.download.allow_private_networks', true),
        ));
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
