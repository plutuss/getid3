<?php

namespace Plutuss\Services;

use finfo;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class MediaManagerService implements MediaManagerServiceInterface
{
    private Filesystem $storage;
    private $file;
    private string $name;

    private string $path;


    public function __construct(
        protected readonly string $url,
        private readonly string   $disk
    )
    {
        $this->storage = Storage::disk($this->getDisk());
    }

    public function handler(): void
    {
        $this->file = file_get_contents($this->url);

        $this->initName();

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
            $this->name = time() . '.' . $data[1];
            return;
        }

        $this->name = '';

    }

    public function getPath(): string
    {
        return $this->path = 'tmp/' . $this->name;
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
}
