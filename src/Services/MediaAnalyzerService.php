<?php

namespace Plutuss\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use JamesHeinrich\GetID3\GetID3;
use Plutuss\DTO\MediaAnalyzerDTO;
use Plutuss\DTO\MediaMetadataInterface;
use Plutuss\Exceptions\GetID3Exception;

class MediaAnalyzerService implements MediaAnalyzerServiceInterface
{
    protected string $file;

    private MediaManagerServiceInterface $managerService;

    protected bool $saveFileFromUrl = false;

    protected ?string $original_filename = null;

    protected int|string|null $filesize = null;

    protected mixed $fp = null;

    private string $path;

    private string $disk;

    public function __construct(
        protected GetID3 $getID3,
        ?MediaManagerServiceInterface $managerService = null
    ) {
        $this->managerService = $managerService ?? app(MediaManagerServiceInterface::class);
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
        int|string|null $filesize = null,
        ?string $originalFileName = '',
        mixed $fp = null
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
        if (!config('getid3.cache')) {
            return $this->analyzeRaw();
        }

        $keyPrefix = config('getid3.key_prefix');

        // Fingerprint the bytes, including streams supplied by remote disks.
        $context = hash_init('sha256');
        if (is_resource($this->fp)) {
            rewind($this->fp);
            hash_update_stream($context, $this->fp);
            rewind($this->fp);
        } elseif (is_file($this->file)) {
            hash_update_file($context, $this->file);
        }
        $key = $keyPrefix . '_' . hash('sha256', implode('|', [
            $this->disk ?? '', $this->file, (string) $this->original_filename,
            hash_final($context),
        ]));

        return Cache::remember($key, config('getid3.cache_time'), fn () => $this->analyzeRaw());
    }

    /**
     * @return Collection
     */
    private function analyzeRaw(): Collection
    {
        $info = $this->getID3->analyze(
            $this->file,
            $this->filesize,
            $this->original_filename,
            $this->fp
        );

        foreach (['id3v2', 'id3v1'] as $version) {
            if (!Arr::has($info, 'tags.' . $version) && Arr::has($info, $version . '.comments')) {
                Arr::set($info, 'tags.' . $version, Arr::get($info, $version . '.comments'));
            }
        }

        $this->getID3->CopyTagsToComments($info);

        return collect($info);
    }

    /**
     * @param UploadedFile $file
     * @return MediaAnalyzerDTOInterface
     */
    public function uploadFile(UploadedFile $file): MediaMetadataInterface
    {
        return new MediaAnalyzerDTO(
            $this->setData($file->getPathname(), $file->getSize(), $file->getClientOriginalName())->getAnalyze()
        );
    }

    /**
     * @param string $url
     * @return MediaAnalyzerDTOInterface
     * @throws GetID3Exception
     */
    public function fromUrl(string $url): MediaMetadataInterface
    {
        $this->managerService
            ->setUrl($url)
            ->setDisk($this->getFilesystemsDisk())
            ->handler();

        try {
            return $this->fromLocalFile(
                $this->managerService->getPath(),
                $this->getFilesystemsDisk()
            );
        } finally {
            if (!$this->saveFileFromUrl) {
                $this->managerService->delete();
            }
        }
    }

    /**
     * @param string|null $path
     * @param string|null $disk
     * @return MediaAnalyzerDTOInterface
     * @throws GetID3Exception
     */
    public function fromLocalFile(?string $path = null, ?string $disk = null): MediaMetadataInterface
    {
        if ($path !== null) {
            $this->path = $path;
        }
        if ($disk !== null) {
            $this->disk = $disk;
        }

        if (!isset($this->path)) {
            throw new GetID3Exception('A media file path is required.');
        }

        $storage = Storage::disk(
            $this->getFilesystemsDisk()
        );

        $this->fileExists($this->path, $storage);

        $stream = $storage->readStream($this->path);
        if (!is_resource($stream)) {
            throw new GetID3Exception('Unable to read media file.');
        }

        try {
            return new MediaAnalyzerDTO(
                $this->setData(
                    file: $this->path,
                    filesize: $storage->size($this->path),
                    originalFileName: basename($this->path),
                    fp: $stream
                )->getAnalyze()
            );
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            $this->fp = null;
        }
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
     * @throws GetID3Exception
     */
    private function fileExists(string $path, Filesystem $storage): void
    {
        if (!$storage->exists($path)) {
            throw new GetID3Exception(sprintf('File at path %s not found', $path), 404);
        }
    }

    protected function getFilesystemsDisk(): string
    {
        return $this->disk ??= config('filesystems.default');
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
