<?php

namespace Plutuss\Services;

use finfo;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class MediaManagerService implements MediaManagerServiceInterface
{
    private Filesystem $storage;
    private mixed $file;
    private string $name;

    private string $path = 'tmp/';

    protected string $url;
    private string $disk;


    private function initStorage(): void
    {
        $this->storage = Storage::disk($this->getDisk());
    }

    public function handler(): void
    {
        $this->file = file_get_contents($this->url);

        $this->initName();

        $this->initStorage();

        $this->initFullPath();

        $this->storage
            ->put($this->getPath(), $this->getFile());
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function initName(): void
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $info = $finfo->buffer($this->file);

        $data = explode('/', $info);

        if (array_key_exists(1, $data)) {
            $name = $this->name ?? time();
            $this->name = $name . '.' . $data[1];
            return;
        }

        $this->name = '';

    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function initFullPath(): string
    {
        return $this->path .= $this->name;
    }

    public function delete(): void
    {
        $this->storage->delete($this->path);
    }

    /**
     * @return mixed
     */
    public function getFile(): mixed
    {
        return $this->file;
    }

    public function getDisk(): string
    {
        return $this->disk;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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
