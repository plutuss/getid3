<?php

namespace Plutuss\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Plutuss\DTO\MediaMetadataInterface;
use Plutuss\Services\MediaAnalyzerServiceInterface;

/**
 * @method static MediaMetadataInterface uploadFile(UploadedFile $file)
 * @method static MediaMetadataInterface fromLocalFile(?string $path = null, ?string $disk = null)
 * @method static MediaAnalyzerServiceInterface setDisk(string $disk)
 * @method static MediaAnalyzerServiceInterface setPath(string $path)
 * @method static MediaAnalyzerServiceInterface saveFileFromUrl(bool $saveFileFromUrl = true)
 * @method static MediaMetadataInterface fromUrl(string $url)
 * @method static MediaAnalyzerServiceInterface setFileName(string $name)
 * @method static MediaAnalyzerServiceInterface setFilePath(string $path)
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
