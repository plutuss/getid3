<?php

namespace Plutuss\Services;

use finfo;
use Plutuss\Download\Downloader;
use Plutuss\Download\DownloadOptions;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Plutuss\Exceptions\GetID3Exception;

class MediaManagerService implements MediaManagerServiceInterface
{
    private Filesystem $storage;
    private mixed $file = null;
    private ?string $name = null;
    private ?string $resolvedName = null;
    private string $directory = 'tmp/';
    private string $path = '';
    protected string $url;
    private string $disk;

    private ?DownloadOptions $downloadOptions = null;

    public function __construct(private ?Downloader $downloader = null) {}

    public function setDownloadOptions(DownloadOptions $options): static
    {
        $this->downloadOptions = $options;
        return $this;
    }

    public function handler(): void
    {
        $this->closeBuffer();
        $downloader = $this->downloader ?? app(Downloader::class);
        $this->file = $downloader->download($this->url, $this->downloadOptions ?? app(DownloadOptions::class));

        try {
            rewind($this->file);
            $sample = fread($this->file, 8192);
            $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($sample);
            $extension = explode('/', $mime ?: 'application/octet-stream', 2)[1];
            $extension = preg_replace('/[^a-zA-Z0-9.+-]/', '', $extension);
            $filename = $this->resolvedName = ($this->name ?? bin2hex(random_bytes(16))) . '.' . $extension;
            $directory = rtrim($this->directory, '/');
            $this->path = ($directory === '' ? '' : $directory . '/') . $filename;
            $this->storage = Storage::disk($this->disk);
            rewind($this->file);
            if (!$this->storage->put($this->path, $this->file)) {
                throw new GetID3Exception('Unable to store downloaded media file.');
            }
        } catch (\Throwable $exception) {
            $this->closeBuffer();
            throw $exception;
        }
    }

    public function getName(): string
    {
        return $this->resolvedName ?? $this->name ?? '';
    }

    public function getPath(): string
    {
        return $this->path !== '' ? $this->path : $this->directory;
    }

    public function initFullPath(): string
    {
        $directory = rtrim($this->directory, '/');
        return $this->path = ($directory === '' ? '' : $directory . '/') . $this->getName();
    }

    public function delete(): void
    {
        $this->storage->delete($this->path);
    }

    public function getFile(): mixed
    {
        if (!is_resource($this->file)) {
            return null;
        }

        rewind($this->file);
        return stream_get_contents($this->file);
    }

    public function __destruct()
    {
        $this->closeBuffer();
    }

    private function closeBuffer(): void
    {
        if (is_resource($this->file)) {
            fclose($this->file);
        }
        $this->file = null;
    }

    public function getDisk(): string
    {
        return $this->disk;
    }

    public function setPath(string $path): static
    {
        $this->directory = $path;
        $this->path = '';
        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $this->resolvedName = null;
        return $this;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function setDisk(string $disk): static
    {
        $this->disk = $disk;
        return $this;
    }
}
