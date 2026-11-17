<?php

namespace Plutuss\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Plutuss\DTO\MediaMetadataInterface;
use Plutuss\Services\MediaAnalyzerServiceInterface;

/**
 * @method static MediaMetadataInterface uploadFile(UploadedFile $file)
 * @method static MediaMetadataInterface fromLocalFile(?string $path = null, ?string $disk = null, ?string $version = null)
 * @method static \Plutuss\Services\MediaAnalyzerService setCacheStrategy(\Plutuss\Cache\CacheStrategy|string $strategy)
 * @method static \Plutuss\Services\MediaAnalyzerService setDownloadOptions(\Plutuss\Download\DownloadOptions $options)
 * @method static \Plutuss\Services\ConfigurableMediaAnalyzerInterface setDisk(string $disk)
 * @method static \Plutuss\Services\ConfigurableMediaAnalyzerInterface setPath(string $path)
 * @method static \Plutuss\Services\ConfigurableMediaAnalyzerInterface saveFileFromUrl(bool $saveFileFromUrl = true)
 * @method static MediaMetadataInterface fromUrl(string $url)
 * @method static \Plutuss\Services\ConfigurableMediaAnalyzerInterface setFileName(string $name)
 * @method static \Plutuss\Services\ConfigurableMediaAnalyzerInterface setFilePath(string $path)
 *
 *
 * @see \Plutuss\Services\MediaAnalyzerServiceInterface
 */
class MediaAnalyzer extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return MediaAnalyzerServiceInterface::class;
    }
}
