<?php

namespace Plutuss\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use JamesHeinrich\GetID3\GetID3;
use Plutuss\DTO\MediaAnalyzerDTO;
use Plutuss\DTO\MediaAnalyzerDTOInterface;


class MediaAnalyzerService implements MediaAnalyzerServiceInterface
{

    protected string $file;

    private MediaManagerService $managerService;

    protected bool $saveFileFromUrl = false;

    protected ?string $original_filename;

    protected int|string|null $filesize;

    protected $fp;

    private string $path;

    private string $disk;

    private Collection|array|null $info = null;

    public function __construct(
        protected GetID3 $getID3
    ) {
        $this->managerService = app(MediaManagerServiceInterface::class);
    }


    /**
     * @param string $file
     * @param int|string|null $filesize
     * @param string|null $originalFileName
     * @param $fp
     * @return $this
     */
    private function setData(
        string $file,
        int|string $filesize = null,
        ?string $originalFileName = '',
        $fp = null
    ): static {
        $this->file = $file;
        $this->filesize = $filesize;
        $this->original_filename = $originalFileName;
        $this->fp = $fp;
        return $this;
    }


    /**
     * @return Collection
     */
    private function getAnalyze(): Collection
    {
        $this->info = $this->getID3->analyze(
            $this->file,
            $this->filesize,
            $this->original_filename,
            $this->fp
        );

        $this->infoHasCommentsOrTags();

        $this->infoHasId3V2orId3V1();

        $this->getID3->CopyTagsToComments($this->info);

        return $this->info = collect($this->info);
    }


    /**
     * @param UploadedFile $file
     * @return MediaAnalyzerDTOInterface
     */
    public function uploadFile(UploadedFile $file): MediaAnalyzerDTOInterface
    {
        return new MediaAnalyzerDTO(
            $this->setData($file)->getAnalyze()
        );
    }

    /**
     * @param string $url
     * @return MediaAnalyzerDTOInterface
     * @throws \Exception
     */
    public function fromUrl(string $url): MediaAnalyzerDTOInterface
    {

        $this->managerService
            ->setUrl($url)
            ->setDisk($this->getFilesystemsDisk())
            ->handler();


        $response = $this->fromLocalFile(
            $this->managerService->getPath(),
            $this->getFilesystemsDisk()
        );

        if (!$this->saveFileFromUrl) {
            $this->managerService->delete();
        }

        return $response;
    }

    /**
     * @param string|null $path
     * @param string|null $disk
     * @return MediaAnalyzerDTOInterface
     * @throws \Exception
     */
    public function fromLocalFile(?string $path = null, ?string $disk = null): MediaAnalyzerDTOInterface
    {
        if ($path)
            $this->path = $path;
        if ($disk)
            $this->disk = $disk;

        $storage = Storage::disk(
            $this->getFilesystemsDisk()
        );

        $this->fileExists($this->path, $storage);

        return new MediaAnalyzerDTO(
            $this->setData(
                file: $this->path,
                filesize: $storage->size($this->path),
                fp: $storage->readStream($this->path)
            )->getAnalyze()
        );
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param string $disk
     * @return $this
     */
    public function setDisk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }


    /**
     * @param string $path
     * @param $storage
     * @return void
     * @throws \Exception
     */
    private function fileExists(string $path, $storage): void
    {
        if (!$storage->exists($path)) {
            throw new \Exception(sprintf('File at path %s not found', $path), 404);
        }

    }

    protected function getFilesystemsDisk(): string
    {
        return $this->disk ??= config('filesystems.default');
    }

    /**
     * @return void
     */
    private function infoHasCommentsOrTags(): void
    {
        if (!Arr::has($this->info, ['comments', 'tags'])) {
            $this->info = Arr::has($this->info, 'id3v2.comments') ?
                Arr::set($this->info, 'tags.id3v2', Arr::get($this->info, 'id3v2.comments')) : $this->info;
        }
        ;
    }

    /**
     * @return void
     */
    private function infoHasId3V2orId3V1(): void
    {
        if (!Arr::has($this->info, ['id3v2', 'id3v1'])) {
            $this->info = Arr::has($this->info, 'id3v1.comments') ?
                Arr::set($this->info, 'tags.id3v1', Arr::get($this->info, 'id3v1.comments')) : $this->info;

        }
    }


    /**
     * @param bool $saveFileFromUrl
     * @return static
     */
    public function saveFileFromUrl(bool $saveFileFromUrl = true): static
    {
        $this->saveFileFromUrl = $saveFileFromUrl;

        return $this;
    }

    public function setFileName(string $name): static
    {
        $this->managerService->setName($name);

        return $this;
    }

    public function setFilePath(string $path): static
    {
        $this->managerService->setPath($path);

        return $this;
    }

}
