<?php

namespace Plutuss\DTO;

use Illuminate\Support\Collection;

interface MediaAnalyzerDTOInterface
{

    /**
     * @return Collection|null
     */
    public function getAllInfo(): ?Collection;

    /**
     * @return Collection|null
     */
    public function all(): ?Collection;

    /**
     * @return string
     */
    public function getDuration(): string;

    /**
     * @return mixed
     */
    public function getComments(): mixed;

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
     * @return mixed
     */
    public function getAlbum(): mixed;

    /**
     * @return mixed
     */
    public function getPlaytime(): mixed;

    /**
     * @return mixed
     */
    public function getGenres(): mixed;

    /**
     * @return mixed
     */
    public function getArtist(): mixed;

    /**
     * @return mixed
     */
    public function getComposer(): mixed;

    /**
     * @return mixed
     */
    public function getTrackNumber(): mixed;

    /**
     * @return mixed
     */
    public function getCopyrightInfo(): mixed;

    /**
     * @return mixed
     */
    public function getFileFormat(): mixed;

    /**
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function getNestedValue(string $path, mixed $default = null): mixed;


    /**
     * @return mixed|string
     */
    public function getPlaytimeSeconds(): mixed;


    /**
     * @param bool $withNumber
     * @return mixed|null
     */
    public function getFileSize(bool $withNumber = false): mixed;
}
