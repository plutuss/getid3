<?php

namespace Plutuss\Exceptions;

class MediaAnalysisException extends GetID3Exception
{
    /** @param list<string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode('; ', $errors));
    }
}
