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
        return date('H:i:s', $this->data->get('playtime_seconds') ?? 0);
    }

    /**
     * @param string $key
     * @return bool
     */
    private function hasData(string $key): bool
    {
        if ($this->data->has($key)) {
            return true;
        }
        return false;
    }


    /**
     * @return array|\Closure|null
     */
    public function comments()
    {
        if ($this->hasData('comments')) {
            return $this->data->get('comments');
        }
        return [];
    }

    /**
     * @return array|Collection
     */
    public function getResolution(): array|Collection
    {
        $data = $this->data;
        if (!$this->hasData('video')) {
            return [];
        }

        return collect([
            'resolution_x' => $data->get('video')['resolution_x'],
            'resolution_y' => $data->get('video')['resolution_y'],
        ]);
    }

    /**
     * @return mixed
     */
    public function getImage(): mixed
    {
        $data = $this->data;

        header('Content-Type: ' . Arr::get($data->get('comments'), 'picture.0.image_mime'));

        return Arr::get($data->get('comments'), 'picture.0.data');

    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        $title = Arr::get($this->comments(), 'title.0', '');

        if (empty($title)) {
            return $this->data->get('filename');
        }

        return $title;
    }

    /**
     * @return array|\ArrayAccess|mixed
     */
    public function getAlbum()
    {
        return Arr::get($this->comments(), 'album.0', '');
    }

    /**
     * @return \Closure|string
     */
    public function getPlaytime()
    {
        return $this->data->get('playtime_string') ?? '';
    }

    /**
     * @return array|\Closure
     */
    public function getGenres()
    {
        return $this->data->get('genre') ?? [];
    }

    /**
     * @return \Closure|null
     */
    public function getArtist()
    {
        return $this->data->get('artist') ?? null;
    }

    /**
     * @return \Closure|null
     */
    public function getComposer()
    {
        return $this->data->get('composer') ?? null;
    }

    /**
     * @return \Closure|null
     */
    public function getTrackNumber()
    {
        return $this->data->get('track_number') ?? null;
    }

    /**
     * @return \Closure|null
     */
    public function getCopyrightInfo()
    {
        return $this->data->get('copyright') ?? null;
    }

    /**
     * @return \Closure|null
     */
    public function getFileFormat()
    {
        return $this->data->get('fileformat') ?? null;
    }


}
