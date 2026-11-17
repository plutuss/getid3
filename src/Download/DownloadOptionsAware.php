<?php

namespace Plutuss\Download;

interface DownloadOptionsAware
{
    public function setDownloadOptions(DownloadOptions $options): static;
}
