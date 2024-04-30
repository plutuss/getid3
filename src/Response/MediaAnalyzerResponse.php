<?php

namespace Plutuss\Response;

use Illuminate\Support\Collection;
use Plutuss\Traits\HasInfoFile;
use Plutuss\Traits\HasPropertyTrait;

class MediaAnalyzerResponse implements MediaAnalyzerResponseInterface
{
    use HasInfoFile, HasPropertyTrait;

    public function __construct(
        protected ?Collection $data
    )
    {
    }


}
