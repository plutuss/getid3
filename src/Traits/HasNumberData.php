<?php

namespace Plutuss\Traits;

trait HasNumberData
{

    /**
     * @return string
     */
    public function getDuration(): string
    {
        $seconds = max(0, (int) $this->getNestedValue('playtime_seconds', 0));

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    /**
     * @return mixed|string
     */
    public function getPlaytime(): mixed
    {
        return $this->getNestedValue('playtime_string') ?? '';
    }

    /**
     * @return mixed|string
     */
    public function getPlaytimeSeconds(): mixed
    {
        return $this->getNestedValue('playtime_seconds') ?? '';
    }

    /**
     * @param bool $withNumber
     * @return mixed|string|null
     */
    public function getFileSize(bool $withNumber = false): mixed
    {
        $filesize = $this->getNestedValue('filesize') ?? 0;

        if ($withNumber) {
            return \Illuminate\Support\Number::fileSize($filesize);
        }

        return $filesize;
    }
}
