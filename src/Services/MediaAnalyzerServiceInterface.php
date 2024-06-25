<?php

namespace Plutuss\Services;

use Illuminate\Http\UploadedFile;
use Plutuss\Response\MediaAnalyzerResponseInterface;

interface MediaAnalyzerServiceInterface
{

    /**
     * @param UploadedFile $file
     * @return MediaAnalyzerResponseInterface
     */
    public function uploadFile(UploadedFile $file): MediaAnalyzerResponseInterface;

    /**
     * @param string $path
     * @param string|null $disk
     * @return MediaAnalyzerResponseInterface
     */
    public function fromLocalFile(string $path, string $disk = null): MediaAnalyzerResponseInterface;
}
