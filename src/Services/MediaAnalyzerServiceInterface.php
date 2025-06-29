<?php

namespace Plutuss\Services;

use Illuminate\Http\UploadedFile;
use Plutuss\DTO\MediaAnalyzerDTOInterface;

interface MediaAnalyzerServiceInterface
{

    /**
     * @param UploadedFile $file
     * @return MediaAnalyzerDTOInterface
     */
    public function uploadFile(UploadedFile $file): MediaAnalyzerDTOInterface;

    /**
     * @param string|null $path
     * @param string|null $disk
     * @return MediaAnalyzerDTOInterface
     */
    public function fromLocalFile(string $path = null, string $disk = null): MediaAnalyzerDTOInterface;

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

    /**
     * @param string $url
     * @return MediaAnalyzerDTOInterface
     */
    public function fromUrl(string $url): MediaAnalyzerDTOInterface;


    /**
     * @param bool $saveFileFromUrl
     * @return static
     */
    public function saveFileFromUrl(bool $saveFileFromUrl = true): static;

    /**
     * @param string $name
     * @return $this
     */
    public function setFileName(string $name): static;

    /**
     * @param string $path
     * @return $this
     */
    public function setFilePath(string $path): static;
}
