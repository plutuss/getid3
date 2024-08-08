<?php

namespace Plutuss\Services;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use JamesHeinrich\GetID3\GetID3;
use Plutuss\Response\MediaAnalyzerResponse;
use Plutuss\Response\MediaAnalyzerResponseInterface;


class MediaAnalyzerService implements MediaAnalyzerServiceInterface
{

    protected string $file;
    protected ?string $original_filename;

    protected int|string|null $filesize;

    protected $fp;

    private Collection|array|null $info = null;

    public function __construct(
        protected GetID3 $getID3
    )
    {
    }


    /**
     * @param string $file
     * @param int|string|null $filesize
     * @param string|null $originalFileName
     * @param $fp
     * @return $this
     */
    private function setData(string     $file,
                             int|string $filesize = null,
                             ?string    $originalFileName = '',
                                        $fp = null): static
    {
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
            $this->fp);

        if (!Arr::has($this->info, ['comments', 'tags'])) {
            $this->info = Arr::has($this->info, 'id3v2.comments') ?
                Arr::set($this->info, 'tags.id3v2', Arr::get($this->info, 'id3v2.comments')) : $this->info;
        };


        if (!Arr::has($this->info, ['id3v2', 'id3v1'])) {
            $this->info = Arr::has($this->info, 'id3v1.comments') ?
                Arr::set($this->info, 'tags.id3v1', Arr::get($this->info, 'id3v1.comments')) : $this->info;

        }

        $this->getID3
            ->CopyTagsToComments($this->info);

        return $this->info = collect($this->info);
    }


    /**
     * @param UploadedFile $file
     * @return MediaAnalyzerResponseInterface
     */
    public function uploadFile(UploadedFile $file): MediaAnalyzerResponseInterface
    {
        return new MediaAnalyzerResponse(
            $this->setData($file)->getAnalyze()
        );
    }

    /**
     * @param string $path
     * @param string|null $disk
     * @return MediaAnalyzerResponseInterface
     * @throws \Exception
     */
    public function fromLocalFile(string $path, string $disk = null): MediaAnalyzerResponseInterface
    {

        $storage = Storage::disk($this->getFilesystemsDisk($disk));

        $this->fileExists($path, $storage);

        return new MediaAnalyzerResponse(
            $this->setData(
                file: $path,
                filesize: $storage->size($path),
                fp: $storage->readStream($path)
            )->getAnalyze()
        );
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

    private function getFilesystemsDisk(?string $disk)
    {
        return empty($disk) ? config('filesystems.default') : $disk;
    }


}
