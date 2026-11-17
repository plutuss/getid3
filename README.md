# getID3 for Laravel

Extract audio, video and image metadata with a Laravel wrapper around
[james-heinrich/getid3](https://github.com/JamesHeinrich/getID3).

## Requirements

- PHP 8.2 or newer with the `fileinfo` extension.
- Laravel 11, 12 or 13, subject to the framework's PHP requirements.
- A Laravel Storage disk for files read through `fromLocalFile()` or `fromUrl()`.

Version 2 introduces explicit runtime requirements and adds methods to the service
and DTO interfaces. See [UPGRADING.md](UPGRADING.md) before upgrading from 1.x.

## Installation

```shell
composer require plutuss/getid3-laravel:^2.0
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

Only HTTP and HTTPS URLs are accepted. The downloader uses a 30-second stream
read timeout and buffers the response in a temporary file instead of loading the
entire download into memory. This is not a total download deadline or a file-size
limit. Validate URLs and apply any host restrictions required by your application
before passing user-supplied URLs to the service.

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

## Read metadata

Analysis returns a `MediaAnalyzerDTOInterface`. Available metadata depends on the
file format and its tags; inspect the full result for getID3 warnings and errors.

| Method | Result |
| --- | --- |
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
| `cache_time` | `86400` seconds | `GETID3_CACHE_TIME` |

Analysis uses Laravel's default cache store. Cache keys include the disk, path,
original filename and a SHA-256 fingerprint of the file contents. Replacing a
file with different bytes of the same size invalidates its cached result. Even a
cache hit requires reading the file to calculate its fingerprint; it saves
analysis work rather than file reads. `GETID3_CACHE=false` disables both caching
and fingerprinting. Version 1 cache entries are not reused and can expire normally.

## Exceptions and analysis errors

A missing input path or file, unsupported URL scheme, failed download, or failed
storage write raises `Plutuss\Exceptions\GetID3Exception`. Laravel filesystem and
getID3 exceptions can also propagate. Opened analysis streams are closed even if
analysis throws. getID3 errors returned as metadata remain available in the DTO;
the wrapper does not convert every `error` field into an exception.

## Development and tests

The default development setup uses PHP 8.4 or newer:

```shell
composer install
composer validate --strict
composer test
```

Tests generate WAV bytes, use fake storage disks and provide an in-memory HTTP
transport. They do not depend on external media files or network services.

GitHub Actions tests Laravel 11, 12 and 13 on PHP 8.2, 8.3 and 8.4 respectively.
The isolated Laravel 11 compatibility job allows legacy framework security
advisories while resolving test dependencies. Normal package installation keeps
Composer's security checks enabled.

## Releases

Changes are recorded in [CHANGELOG.md](CHANGELOG.md). Maintainers publish a numeric
semantic version tag such as `2.0.0` on the commit to release. The release workflow
runs the test matrix, opens a pull request from `release/<version>` to the default
branch, and creates a GitHub release from the matching changelog section after
tests pass. Opening the PR requires the repository setting that allows GitHub
Actions to create pull requests; a PR permission failure does not block release
publication. Composer obtains the package version from the
tag; no `version` field is maintained in `composer.json`.

## License

[MIT](LICENSE.md).
