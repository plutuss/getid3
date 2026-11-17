<?php

namespace Plutuss\DTO;

use Plutuss\Metadata\AudioMetadata;
use Plutuss\Metadata\VideoMetadata;

/** Additional capabilities without changing the 2.0 DTO contract. */
interface MediaMetadataInterface extends MediaAnalyzerDTOInterface
{
    public function hasAudio(): bool;
    public function hasVideo(): bool;
    public function getDurationSeconds(): ?float;
    public function getMimeType(): ?string;
    public function getAudioMetadata(): ?AudioMetadata;
    public function getVideoMetadata(): ?VideoMetadata;
    /** @return list<string> */
    public function getErrors(): array;
    /** @return list<string> */
    public function getWarnings(): array;
    public function hasErrors(): bool;
    public function throwIfErrors(): static;
}
