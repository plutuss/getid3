# getID3 for Laravel — media metadata and upload validation

Extract audio, video and image metadata with a Laravel wrapper around
[james-heinrich/getid3](https://github.com/JamesHeinrich/getID3).

**`plutuss/getid3-laravel`** provides a Laravel facade, typed audio/video metadata,
Storage integration and fluent validation rules for uploaded media.

[View on Packagist](https://packagist.org/packages/plutuss/getid3-laravel) ·
[Source on GitHub](https://github.com/plutuss/getid3)

## When to use this package

- Get MP3 or MP4 duration in seconds, video resolution, codecs and audio properties.
- Read music metadata such as ID3 tags, artist, album, title and embedded cover art.
- Validate Laravel uploads by media type, duration, resolution and audio presence.
- Analyze files from Laravel Storage disks, uploaded files or HTTP/HTTPS URLs.
- Cache analysis results using file content or file metadata.

Format support and available metadata depend on getID3 and the input file.
This package analyzes metadata; it does not transcode media or generate thumbnails.

## Quick start

```shell
composer require plutuss/getid3-laravel:^2.1
```

```php
use Plutuss\Facades\MediaAnalyzer;

$media = MediaAnalyzer::fromLocalFile('videos/example.mp4')->throwIfErrors();

$seconds = $media->getDurationSeconds();
$width = $media->getVideoMetadata()?->getDisplayWidth();
$hasAudio = $media->hasAudio();
```

The path is relative to your default Laravel Storage disk. See below for uploaded
files, other disks, URL downloads and validation rules.

## Requirements

- PHP 8.2 or newer with the `fileinfo` extension; HTTPS downloads also need OpenSSL stream support.
- Laravel 11, 12 or 13, subject to the framework's PHP requirements.
- A Laravel Storage disk for files read through `fromLocalFile()` or `fromUrl()`.

Version 2 introduces explicit runtime requirements and adds methods to the service
and DTO interfaces. See [UPGRADING.md](UPGRADING.md) before upgrading from 1.x.

## Installation

```shell
composer require plutuss/getid3-laravel:^2.1
```

Laravel discovers the service provider automatically. Default configuration is
loaded without publishing files. To customize it, optionally run:

```shell
php artisan vendor:publish --provider="Plutuss\Providers\GetId3ServiceProvider"
```

## Analyze a file

### Laravel Storage

Paths are relative to the selected disk's root. When omitted, the disk comes from
`config('filesystems.default')`.

```php
use Plutuss\Facades\MediaAnalyzer;

$media = MediaAnalyzer::fromLocalFile('videos/example.mov');
$media = MediaAnalyzer::fromLocalFile('videos/example.mov', 'public');

$duration = $media->getDuration();
$codec = $media->getNestedValue('video.codec');
```

Local and remote disks use the Laravel filesystem API. Install and configure the
appropriate Laravel filesystem adapter when using S3, FTP or SFTP.

### Uploaded file

```php
use Illuminate\Http\Request;
use Plutuss\Facades\MediaAnalyzer;

public function analyze(Request $request)
{
    $request->validate(['media' => ['required', 'file']]);
    $media = MediaAnalyzer::uploadFile($request->file('media'));

    return response()->json($media->getAllInfo());
}
```

### URL

```php
use Plutuss\Facades\MediaAnalyzer;

$media = MediaAnalyzer::fromUrl('https://example.com/song.mp3');
```

Only HTTP and HTTPS URLs are accepted. Downloads are buffered on disk, have a
30-second read and request-opening timeout and at most twenty redirects. Total
duration and byte limits remain opt-in (`null` by default) for compatibility with
existing long-running downloads.

For user-supplied URLs, configure limits and an explicit host policy:

```php
use Plutuss\Download\DownloadOptions;

$media = MediaAnalyzer::setDownloadOptions(new DownloadOptions(
    maxBytes: 50 * 1024 * 1024,
    timeout: 20,
    readTimeout: 5,
    connectTimeout: 5,
    maxRedirects: 3,
    allowedHosts: ['media.example.com', 'cdn.example.com'],
    allowPrivateNetworks: false,
))->fromUrl($url);
```

Hosts are exact, case-insensitive names without ports or wildcards; an empty list
allows any host. Every redirect is checked. When private networks are disallowed,
all resolved addresses must be public and the connection is pinned to an approved
IP, while HTTPS still verifies the original hostname. TLS certificate checking
is never disabled. System DNS calls cannot be interrupted by the PHP stream
budget; the remaining budget is checked after resolution. Byte limits are enforced
while reading even without a `Content-Length` header.

For compatibility, private networks remain allowed by default. Set
`download.allow_private_networks` to false for public-only downloads. Download
configuration lives under `getid3.download`, with snake_case equivalents of the
options above: `max_bytes`, `timeout`, `read_timeout`, `connect_timeout`,
`max_redirects`, `allowed_hosts`, `allow_private_networks`. Per-instance
`DownloadOptions` replaces those defaults for that instance.

Downloaded files are written to `tmp/` on the configured disk and removed after
analysis, including when analysis throws. To retain a downloaded file:

```php
$media = MediaAnalyzer::saveFileFromUrl(true)
    ->setDisk('public')
    ->setFilePath('music')
    ->setFileName('song')
    ->fromUrl('https://example.com/song.mp3');
```

`setFilePath()` accepts directories with or without a trailing slash.
`setFileName()` takes a name without an extension; the extension is derived from
the detected MIME type. Generated names are random. An explicit name allows
replacing a file at the same path. These two setters apply to URL downloads;
`setPath()` selects the input path for a later `fromLocalFile()` call.

Options on an existing service instance remain in effect until changed. Use
`saveFileFromUrl(false)` to restore automatic deletion on that instance.
Container resolutions now return independent services, so keep a reference to the
configured instance when using dependency injection.

## Validate uploaded media

Use one fluent rule to share a single analysis across all its constraints:

```php
use Plutuss\Rules\MediaRule;

$request->validate([
    'video' => [
        'required',
        'file',
        MediaRule::video()
            ->maxDuration(120)
            ->minResolution(1280, 720)
            ->requireAudio()
            ->mimeTypes(['video/mp4', 'video/quicktime']),
    ],
]);
```

Factories: `MediaRule::video()`, `audio()`, `image()` and `media()`. Available
constraints: `minDuration()` / `maxDuration()` in seconds, `minResolution()` /
`maxResolution()` in pixels, `requireAudio()` and exact `mimeTypes()`.
Boundary values are inclusive. Video resolution uses display dimensions, accounting for
quarter-turn rotation. Image resolution uses encoded pixel dimensions; EXIF
orientation is not applied. Each dimension must satisfy its bound; a landscape bound
does not automatically accept its portrait equivalent. Missing required metadata
fails validation. File extensions alone do not determine the media type, and
getID3 errors fail the rule. Warnings do not fail it. A successful metadata
analysis does not guarantee that every frame can be decoded or played.

One rule chain analyzes once. Separate rule instances or a subsequent manual
analysis are separate calls and use the configured analysis cache. Rules accept
valid Laravel `UploadedFile` instances; `required` handles missing inputs.

Messages are supplied in English and Russian and follow the application locale.
To customize them:

```shell
php artisan vendor:publish --tag=getid3-translations
```

## Read metadata

The default analyzer returns a `MediaMetadataInterface`, extending the existing
`MediaAnalyzerDTOInterface` without changing its 2.0 methods. Available metadata depends on the
file format and its tags; inspect the full result for getID3 warnings and errors.

| Method | Result |
| --- | --- |
| `hasAudio()`, `hasVideo()` | Whether audio/video metadata is present; images are excluded from `hasVideo()` |
| `getDurationSeconds()` | Duration as `?float`; unknown is null, zero remains zero |
| `getMimeType()` | MIME type as `?string` |
| `getAudioMetadata()`, `getVideoMetadata()` | Readonly typed metadata objects, or null |
| `getErrors()`, `getWarnings()` | Lists of analysis messages |
| `hasErrors()` | Whether analysis returned errors |
| `throwIfErrors()` | Returns the DTO or throws `MediaAnalysisException` |
| `getAllInfo()`, `all()` | Complete metadata as a Laravel collection |
| `getNestedValue($path, $default = null)` | A nested value using dot notation |
| `getDuration()` | Elapsed time as `HH:MM:SS`, including hours above 24 |
| `getPlaytimeSeconds()` | Numeric duration when present, otherwise an empty string |
| `getPlaytime()` | getID3's formatted playtime, otherwise an empty string |
| `getFileSize()` | File size in bytes, or zero when absent |
| `getFileSize(true)` | File size formatted by Laravel's `Number::fileSize()` |
| `getResolution()` | Collection with `resolution_x` and `resolution_y`, or an empty array without video metadata |
| `getComments()`, `comments()` | Normalized comments, or an empty array |
| `getTitle()` | First title tag, falling back to the filename or an empty string |
| `getAlbum()` | First album tag, or an empty string |
| `getGenres()` | Genre metadata, or an empty array |
| `getArtist()`, `getComposer()` | Metadata value, or null |
| `getTrackNumber()`, `getCopyrightInfo()` | Metadata value, or null |
| `getFileFormat()` | Detected file format, or null |
| `getImageData()` | First embedded artwork's raw bytes, or null |
| `getImageMimeType()` | First embedded artwork's MIME type, or null |
| `getImage()` | Artwork bytes; also sets the HTTP Content-Type when present |

Artist, composer, genre, track and copyright getters preserve top-level metadata
and fall back to normalized `comments` fields. Values can be arrays; the wrapper
preserves their shape. Duration formatting does not depend on the server timezone.

Top-level fields are also available as properties; array values become Laravel
collections:

```php
$mime = $media->mime_type;
$video = $media->video;
$codec = $media->getNestedValue('video.codec', 'unknown');
```

### Typed audio and video metadata

```php
$seconds = $media->getDurationSeconds(); // ?float
$audio = $media->getAudioMetadata();
$sampleRate = $audio?->sampleRate;       // ?int, Hz
$channels = $audio?->channels;           // ?int

$video = $media->getVideoMetadata();
$width = $video?->width;                // encoded width, pixels
$displayWidth = $video?->getDisplayWidth();
$displayHeight = $video?->getDisplayHeight();
```

`AudioMetadata` exposes `codec`, `bitrate`, `sampleRate`, `channels` and
`bitsPerSample`. `VideoMetadata` exposes `codec`, `width`, `height`, `frameRate`,
`bitrate` and `rotation`. Bitrates use bits/second, frame rate uses frames/second,
and rotation uses degrees reported by getID3. Invalid or missing numeric metadata
becomes null. Encoded dimensions remain unchanged. Display dimensions swap at
90/270 degrees, use encoded dimensions when rotation is absent, and return null
for arbitrary rotations. getID3 also stores image dimensions in its raw `video` section. The new
`hasVideo()` and `getVideoMetadata()` exclude images; image validation reads
the raw image dimensions separately.

For dependency injection, use `ConfigurableMediaAnalyzerInterface` for 2.1
options, or retain `MediaAnalyzerServiceInterface` for the existing 2.0 contract.

### Embedded artwork

Use the new accessors when your application controls the HTTP response:

```php
$image = $media->getImageData();
$mime = $media->getImageMimeType();

if ($image !== null && $mime !== null) {
    return response($image)->header('Content-Type', $mime);
}
```

The existing `getImage()` method retains its HTTP header behavior for compatibility.

## Configuration and caching

The published `config/getid3.php` provides:

| Key | Default | Environment variable |
| --- | --- | --- |
| `key_prefix` | `getid3` | — |
| `cache` | `true` | `GETID3_CACHE` |
| `cache_strategy` | `content` | `GETID3_CACHE_STRATEGY` |
| `cache_time` | `86400` seconds | `GETID3_CACHE_TIME` |

Analysis uses Laravel's default cache store. Select a strategy globally or per
service instance:

```php
use Plutuss\Cache\CacheStrategy;

$media = MediaAnalyzer::setCacheStrategy(CacheStrategy::Metadata)
    ->fromLocalFile('videos/example.mp4', 's3');
```

| Strategy | Behavior |
| --- | --- |
| `content` (default) | SHA-256 of all bytes; detects same-size replacements, but reads the file on cache hits |
| `metadata` | Size plus modification time; a cache hit does not open the file stream |
| `none` | Skips analysis caching and fingerprinting |

`GETID3_CACHE=false` takes precedence over per-instance strategy selection.
Unknown strategy names fail explicitly. Keys distinguish strategies, disks,
paths, filenames and sizes. Old 1.x/2.0 entries expire normally.

Modification times may have coarse precision. Metadata mode can miss a same-size
replacement with the same modification time; use content mode when this matters.
For a trusted ETag or object version identifier supplied by your application:

```php
$media = MediaAnalyzer::setCacheStrategy('metadata')
    ->fromLocalFile('videos/example.mp4', 's3', version: $objectVersion);
```

This identifier changes the cache key instead of querying modification time; it
does not select an S3 object version to download. The caller must associate it
with the bytes read. Generic Laravel disks do not expose ETags through the
filesystem contract. Non-seekable read streams are buffered for getID3.

An optional benchmark runs inside an installed Laravel application:

```shell
php vendor/plutuss/getid3-laravel/benchmarks/cache.php /path/to/laravel videos/example.mp4 s3 10
```

It reports first-call latency, mean repeated-call latency and PHP peak memory
for each strategy. Its isolated cache entries expire after 60 seconds; it never
flushes the application's cache. Results depend on file, disk and cache store;
the tests separately verify that metadata cache hits perform no stream opens.

## Exceptions and analysis errors

A missing input path or file, unsupported URL scheme, failed download, or failed
storage write raises `Plutuss\Exceptions\GetID3Exception`. Laravel filesystem and
getID3 exceptions can also propagate. Opened analysis streams are closed even if
analysis throws. getID3 errors returned as metadata remain available in the DTO;
the wrapper does not automatically convert `error` fields into exceptions.
Use `$media->throwIfErrors()` to opt in. `MediaAnalysisException` extends
`GetID3Exception` and preserves the complete message list in `$exception->errors`.
Warnings remain separate and do not trigger this exception.

## Development and tests

The default development setup uses PHP 8.4 or newer:

```shell
composer install
composer validate --strict
composer test
```

Tests generate WAV bytes, use fake storage disks, and include small synthetic
MP3, FLAC, MP4, rotated MOV and PNG fixtures. Download tests use in-memory
transports; tests do not require network services or FFmpeg. Fixture provenance
and optional regeneration instructions are in [tests/fixtures/README.md](tests/fixtures/README.md).

GitHub Actions tests Laravel 11, 12 and 13 on PHP 8.2, 8.3 and 8.4 respectively.
The isolated Laravel 11 compatibility job allows legacy framework security
advisories while resolving test dependencies. Normal package installation keeps
Composer's security checks enabled.

## Releases

Changes are recorded in [CHANGELOG.md](CHANGELOG.md). Maintainers publish a numeric
semantic version tag such as `2.1.0` on the commit to release. The release workflow
runs the test matrix and creates a GitHub release from the matching changelog
section only after all jobs pass. Composer obtains the package version from the
tag; no `version` field is maintained in `composer.json`.

## License

[MIT](LICENSE.md).
