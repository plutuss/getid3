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
 * @method static MediaAnalyzerServiceInterface fromUrl(string $url)
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
        return 'getid3.media';
    }
}
