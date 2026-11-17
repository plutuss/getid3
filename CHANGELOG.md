# Changelog

## [2.0.0] - 2026-11-17

### Compatibility

- Require PHP 8.2+ and Laravel 11, 12 or 13.
- Add artwork data and MIME accessors to `MediaAnalyzerDTOInterface`, and path/name
  setters to `MediaManagerServiceInterface`; custom implementations must add them.
- Resolve independent service instances instead of sharing singleton state.
- Accept HTTP and HTTPS download URLs only.

### Added

- `getImageData()` and `getImageMimeType()` for embedded artwork without HTTP side effects.
- Download buffering in temporary files, a stream read timeout and explicit download/write failures.
- Unique default download names and support for directories without a trailing slash.
- Regression tests using generated WAV data, fake disks and in-memory HTTP streams.
- CI for Laravel 11–13 and a tag-triggered release workflow gated by the test matrix.
- Release pull request creation through the repository workflow token.
- Usage, configuration and upgrade documentation.

### Fixed

- Repeated URL downloads accumulating filenames and extensions in their output paths.
- Temporary download cleanup after analysis exceptions and analysis stream cleanup.
- Cache collisions between disks and stale analysis after replacing same-size files.
- Missing package defaults when configuration had not been published.
- Timezone-dependent duration formatting and duration wrapping after 24 hours.
- Missing normalized tag values and incorrect ID3v1/ID3v2 fallback conditions.
- Empty DTO handling, missing-title fallback and facade return annotations for fluent setters.

### Validation

- 16 tests and 54 assertions passed with Laravel 11, 12 and 13 on PHP 8.4.12.
- Composer validation and PHP syntax checks passed.

[2.0.0]: https://github.com/plutuss/getid3/compare/1.5.0...2.0.0
