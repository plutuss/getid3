<?php

namespace Plutuss\Traits;

trait HasNumberData
{

    /**
     * @return string
     */
    public function getDuration(): string
    {
        return date('H:i:s',
            $this->getNestedValue('playtime_seconds') ?? 0);
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
