<?php

namespace Plutuss\DTO;

use Illuminate\Support\Collection;
use Plutuss\Traits\HasInfoFile;
use Plutuss\Traits\HasPropertyTrait;

class MediaAnalyzerDTO implements MediaAnalyzerDTOInterface
{
    use HasInfoFile, HasPropertyTrait;

    protected Collection $data;

    public function __construct(?Collection $data)
    {
        $this->data = $data ?? collect();
    }
}
