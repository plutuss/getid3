<?php

namespace Plutuss\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Plutuss\DTO\MediaAnalyzerDTOInterface;
use Plutuss\Services\MediaAnalyzerServiceInterface;

/**
 * @method static MediaAnalyzerDTOInterface uploadFile(UploadedFile $file)
 * @method static MediaAnalyzerDTOInterface fromLocalFile(?string $path = null, ?string $disk = null)
 * @method static MediaAnalyzerServiceInterface setDisk(string $disk)
 * @method static MediaAnalyzerServiceInterface setPath(string $path)
 * @method static MediaAnalyzerServiceInterface saveFileFromUrl(bool $saveFileFromUrl = true)
 * @method static MediaAnalyzerDTOInterface fromUrl(string $url)
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
