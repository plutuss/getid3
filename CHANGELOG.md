# Changelog

## [2.1.0] - 2026-11-17

### Added

- Fluent `MediaRule::video()`, `audio()`, `image()` and `media()` validation with
  duration, display resolution, required audio and MIME constraints, using one
  analysis per rule chain. English/Russian messages support custom attribute names.
- Readonly `AudioMetadata` and `VideoMetadata`, nullable normalized numeric values,
  stream-presence checks, typed duration/MIME accessors and rotation-aware dimensions.
- `getErrors()`, `getWarnings()`, `hasErrors()` and opt-in `throwIfErrors()` with
  a dedicated exception preserving the message list.
- `DownloadOptions`: byte limits, total/read/opening timeouts, redirect budgets,
  exact host allowlists and opt-in public-network-only downloads with IP pinning.
- `content`, `metadata` and `none` cache strategies. Metadata cache hits avoid
  opening a stream and can use caller-supplied object version identifiers.
- Buffering for non-seekable filesystem streams.
- Synthetic MP3, FLAC, MP4, rotated MOV and PNG fixtures, focused regression tests,
  an optional cache benchmark and documentation for each feature.

### Compatibility

- PHP 8.2+ and Laravel 11–13 remain supported.
- Existing 2.0 interfaces are unchanged; new contracts extend the original API.
- Content-based caching remains the default. Private network access remains
  enabled and byte limits are opt-in.
- Downloads retain 30-second read/opening timeouts; total time and byte limits
  are opt-in. HTTP errors and truncated declared bodies are rejected.
- No FFmpeg dependency is added to the package or test runtime.

### Validation

- 65 tests / 245 assertions on PHP 8.4.12; framework compatibility matrix checked
  separately in CI for PHP 8.2/Laravel 11, PHP 8.3/Laravel 12 and PHP 8.4/Laravel 13.
- PHP syntax, Composer validation, workflow syntax and benchmark smoke checks.

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

[2.1.0]: https://github.com/plutuss/getid3/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/plutuss/getid3/compare/1.5.0...2.0.0
