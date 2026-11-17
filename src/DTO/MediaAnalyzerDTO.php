<?php

namespace Plutuss\DTO;

use Illuminate\Support\Collection;
use Plutuss\Traits\HasInfoFile;
use Plutuss\Traits\HasMediaMetadata;
use Plutuss\Traits\HasPropertyTrait;

class MediaAnalyzerDTO implements MediaMetadataInterface
{
    use HasInfoFile, HasPropertyTrait, HasMediaMetadata;

    protected Collection $data;

    public function __construct(?Collection $data)
    {
        $this->data = $data ?? collect();
    }
}
