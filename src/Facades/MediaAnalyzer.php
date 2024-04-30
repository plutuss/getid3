<?php

namespace Plutuss\Facades;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Plutuss\Response\MediaAnalyzerResponseInterface;

/**
 * @method static MediaAnalyzerResponseInterface uploadFile(UploadedFile $file)
 * @method static MediaAnalyzerResponseInterface fromLocalFile(string $path, string $disk = null)
 *
 *
 * @see \Plutuss\Services\MediaAnalyzerServicesInterface
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
