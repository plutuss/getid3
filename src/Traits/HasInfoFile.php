<?php

namespace Plutuss\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasInfoFile
{

    use HasNumberData;

    /**
     * @param string $key
     * @return bool
     */
    private function hasData(string $key): bool
    {
        return $this->data->has($key);
    }

    /**
     * @return array|mixed
     */
    public function getComments(): mixed
    {
        return $this->getNestedValue('comments', []);
    }

    /**
     * @return array|mixed
     */
    public function comments(): mixed
    {
        return $this->getComments();
    }

    /**
     * @return array|Collection
     */
    public function getResolution(): array|Collection
    {

        if (!$this->hasData('video')) {
            return [];
        }

        return collect([
            'resolution_x' => $this->getNestedValue('video.resolution_x'),
            'resolution_y' => $this->getNestedValue('video.resolution_y'),
        ]);
    }

    /**
     * @return mixed
     */
    public function getImage(): mixed
    {
        $mime = $this->getImageMimeType();
        if ($mime !== null) {
            header('Content-Type: ' . $mime);
        }

        return $this->getImageData();
    }

    /** Return embedded artwork without changing the HTTP response. */
    public function getImageData(): ?string
    {
        return Arr::get($this->getComments(), 'picture.0.data');
    }

    public function getImageMimeType(): ?string
    {
        return Arr::get($this->getComments(), 'picture.0.image_mime');
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        $title = Arr::get($this->getComments(), 'title.0', '');

        if (empty($title)) {
            return (string) $this->getNestedValue('filename', '');
        }

        return $title;
    }

    /**
     * @return array|\ArrayAccess|mixed
     */
    public function getAlbum(): mixed
    {
        return Arr::get($this->getComments(), 'album.0', '');
    }

    /**
     * @return array|mixed
     */
    public function getGenres(): mixed
    {
        return $this->getNestedValue('genre', $this->getNestedValue('comments.genre')) ?? [];
    }

    /**
     * @return mixed|null
     */
    public function getArtist(): mixed
    {
        return $this->getNestedValue('artist', $this->getNestedValue('comments.artist')) ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getComposer(): mixed
    {
        return $this->getNestedValue('composer', $this->getNestedValue('comments.composer')) ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getTrackNumber(): mixed
    {
        return $this->getNestedValue('track_number', $this->getNestedValue('comments.track_number')) ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getCopyrightInfo(): mixed
    {
        return $this->getNestedValue('copyright', $this->getNestedValue('comments.copyright')) ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getFileFormat(): mixed
    {
        return $this->getNestedValue('fileformat') ?? null;
    }

}
