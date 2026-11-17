<?php

namespace Plutuss\Metadata;

/** Normalization shared by typed metadata and validation. */
final class MetadataValue
{
    public static function number(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }
        $number = (float) $value;
        return is_finite($number) && $number >= 0 ? $number : null;
    }

    public static function integer(mixed $value): ?int
    {
        $number = self::number($value);
        return $number !== null && $number < PHP_INT_MAX ? (int) $number : null;
    }

    public static function text(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
