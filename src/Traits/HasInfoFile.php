<?php

namespace Plutuss\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasInfoFile
{

    /**
     * @return string
     */
    public function getDuration(): string
    {
        return date('H:i:s', $this->getNestedValue('playtime_seconds') ?? 0);
    }

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
        header('Content-Type: ' . Arr::get($this->getComments(), 'picture.0.image_mime'));

        return Arr::get($this->getComments(), 'picture.0.data');

    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        $title = Arr::get($this->getComments(), 'title.0', '');

        if (empty($title)) {
            return $this->getNestedValue('filename');
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
     * @return mixed|string
     */
    public function getPlaytime(): mixed
    {
        return $this->getNestedValue('playtime_string') ?? '';
    }


    /**
     * @return array|mixed
     */
    public function getGenres(): mixed
    {
        return $this->getNestedValue('genre') ?? [];
    }


    /**
     * @return mixed|null
     */
    public function getArtist(): mixed
    {
        return $this->getNestedValue('artist') ?? null;
    }


    /**
     * @return mixed|null
     */
    public function getComposer(): mixed
    {
        return $this->getNestedValue('composer') ?? null;
    }


    /**
     * @return mixed|null
     */
    public function getTrackNumber(): mixed
    {
        return $this->getNestedValue('track_number') ?? null;
    }


    /**
     * @return mixed|null
     */
    public function getCopyrightInfo(): mixed
    {
        return $this->getNestedValue('copyright') ?? null;
    }


    /**
     * @return mixed|null
     */
    public function getFileFormat(): mixed
    {
        return $this->getNestedValue('fileformat') ?? null;
    }


}
