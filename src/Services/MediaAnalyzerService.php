<?php

namespace Plutuss\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Plutuss\Download\DownloadOptions;
use Plutuss\Download\DownloadOptionsAware;
use Plutuss\Cache\CacheStrategy;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use JamesHeinrich\GetID3\GetID3;
use Plutuss\DTO\MediaAnalyzerDTO;
use Plutuss\DTO\MediaMetadataInterface;
use Plutuss\Exceptions\GetID3Exception;

class MediaAnalyzerService implements ConfigurableMediaAnalyzerInterface
{
    protected string $file;

    private MediaManagerServiceInterface $managerService;

    protected bool $saveFileFromUrl = false;

    protected ?string $original_filename = null;

    protected int|string|null $filesize = null;

    protected mixed $fp = null;

    private string $path;

    private string $disk;

    private ?CacheStrategy $cacheStrategy = null;

    private string $sourceVersion = '';

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
        $strategy = $this->effectiveCacheStrategy();
        if ($strategy === CacheStrategy::None) {
            return $this->analyzeRaw();
        }
        $fingerprint = $this->sourceVersion;
        if ($strategy === CacheStrategy::Content) {
            $context = hash_init('sha256');
            if (is_resource($this->fp)) {
                if (!rewind($this->fp) || hash_update_stream($context, $this->fp) === false || !rewind($this->fp)) {
                    throw new GetID3Exception('Unable to fingerprint media stream.');
                }
            } elseif (!hash_update_file($context, $this->file)) {
                throw new GetID3Exception('Unable to fingerprint media file.');
            }
            $fingerprint = hash_final($context);
        }

        return Cache::remember($this->cacheKey($fingerprint), config('getid3.cache_time'), fn () => $this->analyzeRaw());
    }

    public function setCacheStrategy(CacheStrategy|string $strategy): static
    {
        $this->cacheStrategy = is_string($strategy) ? CacheStrategy::from($strategy) : $strategy;
        return $this;
    }

    private function effectiveCacheStrategy(): CacheStrategy
    {
        return config('getid3.cache')
            ? ($this->cacheStrategy ?? CacheStrategy::from(config('getid3.cache_strategy', 'content')))
            : CacheStrategy::None;
    }

    private function cacheKey(string $fingerprint): string
    {
        return config('getid3.key_prefix') . '_' . hash('sha256', json_encode([
            '2.1', $this->effectiveCacheStrategy()->value, $this->disk ?? '',
            $this->file, $this->original_filename, $this->filesize, $fingerprint,
        ], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE));
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
        $this->sourceVersion = (string) $file->getMTime();
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
    public function fromLocalFile(?string $path = null, ?string $disk = null, ?string $version = null): MediaMetadataInterface
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

        $this->setData($this->path, $storage->size($this->path), basename($this->path));
        if ($this->effectiveCacheStrategy() === CacheStrategy::Metadata) {
            $this->sourceVersion = $version ?? (string) $storage->lastModified($this->path);
            return new MediaAnalyzerDTO(Cache::remember(
                $this->cacheKey($this->sourceVersion),
                config('getid3.cache_time'),
                fn () => $this->analyzeStoredFile($storage, false)
            ));
        }

        return new MediaAnalyzerDTO($this->analyzeStoredFile($storage));
    }

    private function analyzeStoredFile(Filesystem $storage, bool $cache = true): Collection
    {
        $source = $storage->readStream($this->path);
        if (!is_resource($source)) {
            throw new GetID3Exception('Unable to read media file.');
        }
        $stream = $source;
        try {
            if (!(stream_get_meta_data($source)['seekable'] ?? false)) {
                $stream = tmpfile();
                if ($stream === false || stream_copy_to_stream($source, $stream) === false || !rewind($stream)) {
                    throw new GetID3Exception('Unable to buffer non-seekable media stream.');
                }
            }
            $this->fp = $stream;
            return $cache ? $this->getAnalyze() : $this->analyzeRaw();
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if ($source !== $stream && is_resource($source)) {
                fclose($source);
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

    public function setDownloadOptions(DownloadOptions $options): static
    {
        if (!$this->managerService instanceof DownloadOptionsAware) {
            throw new GetID3Exception('The configured media manager does not support download options.');
        }
        $this->managerService->setDownloadOptions($options);
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
