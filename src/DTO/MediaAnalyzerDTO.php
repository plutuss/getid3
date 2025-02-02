<?php

namespace Plutuss\DTO;

use Illuminate\Support\Collection;
use Plutuss\Traits\HasInfoFile;
use Plutuss\Traits\HasPropertyTrait;

//class MediaAnalyzerResponse implements MediaAnalyzerResponseInterface
class MediaAnalyzerDTO implements MediaAnalyzerDTOInterface
{
    use HasInfoFile, HasPropertyTrait;

    public function __construct(
        protected ?Collection $data
    )
    {
    }


}
