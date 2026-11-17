# Upgrading from 1.x to 2.0

## Runtime requirements

Version 2 explicitly requires PHP 8.2+ and Laravel 11, 12 or 13, subject to the
framework's own PHP requirements. Check compatibility before updating:

```shell
composer why-not plutuss/getid3-laravel 2.0.0
composer require plutuss/getid3-laravel:^2.0 --with-all-dependencies
```

Applications on older PHP or Laravel versions should remain on the 1.x series
until their runtime is upgraded.

## Interfaces and service lifetime

Existing facade entry points, DTO accessors and fluent setters remain available.
If you provide custom implementations, add these methods:

- `MediaAnalyzerDTOInterface`: `getImageData(): ?string` and `getImageMimeType(): ?string`.
- `MediaManagerServiceInterface`: `setPath(string $path): static` and `setName(string $name): static`.

The provider now uses transient bindings instead of singletons. Keep the service
instance when configuring and executing an analysis; do not configure one
container resolution and expect another resolution to share its options.

## Corrected behavior

- URL downloads accept HTTP and HTTPS only; local files belong on a Storage disk.
- Default URL filenames are random instead of timestamp-based. Explicit names
  continue to work; repeated downloads no longer append extensions or filenames
  to the previous path.
- Download directories accept a missing trailing slash.
- Unsaved URL downloads are removed if analysis throws; analysis streams close
  on both success and failure.
- `getDuration()` formats elapsed time independently of timezone and supports
  durations beyond 24 hours.
- Artist, genre, composer, track and copyright getters now fall back to
  normalized comments, so previously missing values may become available.
- A DTO constructed with null data behaves as an empty result.
- Cache keys fingerprint file contents and include the disk. Old entries expire
  normally; a global cache flush is unnecessary. Fingerprinting reads the file
  even on a cache hit.

## Configuration and artwork

Publishing configuration is now optional; package defaults are merged into the
application automatically. Existing published overrides remain supported.

`getImage()` retains its HTTP header behavior. Prefer `getImageData()` and
`getImageMimeType()` when constructing a response or reading artwork in a job or
CLI command.
