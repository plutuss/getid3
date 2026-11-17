#!/bin/sh
set -eu
cd "$(dirname "$0")"
ffmpeg_bin="${FFMPEG_BIN:-ffmpeg}"
"$ffmpeg_bin" -hide_banner -loglevel error -y -f lavfi -i 'sine=frequency=440:sample_rate=44100:duration=0.3' -c:a libmp3lame -b:a 64k -metadata title=Synthetic-tone tone.mp3
"$ffmpeg_bin" -hide_banner -loglevel error -y -f lavfi -i 'sine=frequency=440:sample_rate=44100:duration=0.3' -c:a flac -metadata title=Synthetic-tone tone.flac
"$ffmpeg_bin" -hide_banner -loglevel error -y -f lavfi -i 'color=c=blue:s=64x48:r=10:d=0.3' -f lavfi -i 'sine=frequency=440:sample_rate=44100:duration=0.3' -c:v libx264 -pix_fmt yuv420p -c:a aac -t 0.3 -movflags +faststart -metadata title=Synthetic-clip clip.mp4
"$ffmpeg_bin" -hide_banner -loglevel error -y -display_rotation:v:0 90 -i clip.mp4 -c copy rotated.mov
"$ffmpeg_bin" -hide_banner -loglevel error -y -f lavfi -i 'color=c=red:s=16x12' -frames:v 1 -threads 1 pixel.png
