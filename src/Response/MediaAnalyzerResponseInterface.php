<?php

namespace Plutuss\Response;

use Illuminate\Support\Collection;

interface MediaAnalyzerResponseInterface
{
    public function getAllInfo(): ?Collection;

    /**
     * @return string
     */
    public function getDuration(): string;

    /**
     * @return array|\Closure|null
     */
    public function comments();

    /**
     * @return array|Collection
     */
    public function getResolution(): array|Collection;

    /**
     * @return mixed
     */
    public function getImage(): mixed;

    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @return array|\ArrayAccess|mixed
     */
    public function getAlbum();

    /**
     * @return \Closure|string
     */
    public function getPlaytime();

    /**
     * @return array|\Closure
     */
    public function getGenres();

    /**
     * @return \Closure|null
     */
    public function getArtist();

    /**
     * @return \Closure|null
     */
    public function getComposer();

    /**
     * @return \Closure|null
     */
    public function getTrackNumber();

    /**
     * @return \Closure|null
     */
    public function getCopyrightInfo();

    /**
     * @return \Closure|null
     */
    public function getFileFormat();

    /**
     * @param string $path
     * @return mixed
     */
    public function getNestedValue(string $path): mixed;
}
