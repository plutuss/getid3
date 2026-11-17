<?php

namespace Plutuss\Services;

use Illuminate\Http\UploadedFile;
use Plutuss\Cache\CacheStrategy;
use Plutuss\Download\DownloadOptionsAware;
use Plutuss\DTO\MediaMetadataInterface;

/** Optional 2.1 capabilities; existing 2.0 implementations remain valid. */
interface ConfigurableMediaAnalyzerInterface extends MediaAnalyzerServiceInterface, DownloadOptionsAware
{
    public function uploadFile(UploadedFile $file): MediaMetadataInterface;
    public function fromUrl(string $url): MediaMetadataInterface;
    public function fromLocalFile(?string $path = null, ?string $disk = null, ?string $version = null): MediaMetadataInterface;
    public function setCacheStrategy(CacheStrategy|string $strategy): static;
}
