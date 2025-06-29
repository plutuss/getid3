<?php

namespace Plutuss\Services;

interface MediaManagerServiceInterface
{
    public function handler(): void;

    public function getPath(): string;

    public function delete(): void;

    public function getName(): string;

    public function getFile(): mixed;

    public function getDisk(): string;

    public function setUrl(string $url): static;

    public function setDisk(string $disk): static;

}
