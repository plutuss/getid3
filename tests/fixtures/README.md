# Synthetic media fixtures

These small files were generated for this package from FFmpeg's color and sine
sources. They contain no third-party recordings or images and are distributed
under the package's MIT license.

- `tone.mp3`, `tone.flac`: 440 Hz mono tone at 44,100 Hz, approximately 0.3 seconds.
- `clip.mp4`: 64 × 48 blue H.264 video at 10 fps with AAC audio.
- `rotated.mov`: the same video with a 90-degree display rotation.
- `pixel.png`: 16 × 12 red image.

Generation used FFmpeg 9.0.2. To regenerate, run `sh tests/fixtures/generate.sh`
with FFmpeg on PATH, or set `FFMPEG_BIN` to its executable. Encoded bytes can vary
between FFmpeg builds; tests check metadata rather than byte-for-byte output.
FFmpeg is not needed to run the tests or use the package.
