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
     * @param string|null $path
     * @param string|null $disk
     * @return MediaAnalyzerResponseInterface
     */
    public function fromLocalFile(string $path = null, string $disk = null): MediaAnalyzerResponseInterface;

    /**
     * @param string $path
     * @return $this
     */
    public function setPath(string $path): static;

    /**
     * @param string $disk
     * @return $this
     */
    public function setDisk(string $disk): static;
}
