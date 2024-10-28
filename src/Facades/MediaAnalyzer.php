<?php

namespace Plutuss\Facades;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Plutuss\Response\MediaAnalyzerResponseInterface;
use Plutuss\Services\MediaAnalyzerServiceInterface;

/**
 * @method static MediaAnalyzerResponseInterface uploadFile(UploadedFile $file)
 * @method static MediaAnalyzerResponseInterface fromLocalFile(string $path, string $disk = null)
 * @method static MediaAnalyzerServiceInterface setDisk(string $disk)
 * @method static MediaAnalyzerServiceInterface setPath(string $path)
 * @method static MediaAnalyzerServiceInterface saveFileFromUrl(bool $saveFileFromUrl)
 * @method static MediaAnalyzerResponseInterface fromUrl(string $url = true)
 * @method static MediaAnalyzerResponseInterface setFileName(string $name)
 * @method static MediaAnalyzerResponseInterface setFilePath(string $path)
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
