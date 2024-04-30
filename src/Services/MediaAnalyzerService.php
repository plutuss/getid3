<?php

namespace Plutuss\Services;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Plutuss\Response\MediaAnalyzerResponse;
use Plutuss\Response\MediaAnalyzerResponseInterface;


class MediaAnalyzerService implements MediaAnalyzerServicesInterface
{

    protected \getID3 $getID3;

    protected string $file;
    protected ?string $original_filename;

    protected int|string|null $filesize;

    protected $fp;

    private Collection|array|null $info = null;

    public function __construct()
    {
        $this->getID3 = new \getID3;
    }


    /**
     * @param string $file
     * @param int|string|null $filesize
     * @param string|null $original_filename
     * @param $fp
     * @return $this
     */
    private function setData(string $file, int|string $filesize = null, ?string $original_filename = '', $fp = null): static
    {
        $this->file = $file;
        $this->filesize = $filesize;
        $this->original_filename = $original_filename;
        $this->fp = $fp;
        return $this;
    }


    /**
     * @return Collection
     */
    private function getAnalyze(): Collection
    {
        $this->info = $this->getID3->analyze($this->file, $this->filesize, $this->original_filename, $this->fp);

        if (!Arr::has($this->info, ['comments', 'tags'])) {
            $this->info = Arr::has($this->info, 'id3v2.comments') ?
                Arr::set($this->info, 'tags.id3v2', Arr::get($this->info, 'id3v2.comments')) : $this->info;
        };


        if (!Arr::has($this->info, ['id3v2', 'id3v1'])) {
            $this->info = Arr::has($this->info, 'id3v1.comments') ?
                Arr::set($this->info, 'tags.id3v1', Arr::get($this->info, 'id3v1.comments')) : $this->info;

        }

        \getid3_lib::CopyTagsToComments($this->info);

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
        $disk = empty($disk) ? config('filesystems.default') : $disk;
        $storage = Storage::disk($disk);

        if (!$storage->exists($path)) {
            throw new \Exception(sprintf('File at path %s not found', $path));
        }
        return new MediaAnalyzerResponse(
            $this->setData(
                file: $path,
                filesize: $storage->size($path),
                fp: $storage->readStream($path)
            )->getAnalyze()
        );
    }


}
