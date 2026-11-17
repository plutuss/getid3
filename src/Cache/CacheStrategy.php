<?php

namespace Plutuss\Cache;

enum CacheStrategy: string
{
    case Content = 'content';
    case Metadata = 'metadata';
    case None = 'none';
}
