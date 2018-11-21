# Auto-NEF-Thumbnailer and Auto-MP4-Shrinker for Nextcloud

My Motivation: I have a Nextcloud. I make photos and videos. I don't want to sync this big raw files. 
I want to have a smaller web-version to share it efficent. It should work automatically on new files.
It scans a source directory tree, convert it, and store it in a target directory tree - like a mirror.
Then the Nextcloud Uploader get it and upload it.

## Installation

0. Clone this repo.  (e.g. in `/Users/you/Image-and-Video-Thumbnailer`)
1. Docker build
2. Run scripts manually
3. optional: Install as cronjob

## Docker build

```
docker build -t thumbnailer .
docker run -it --rm \
    -v "$PWD":/project \
    -v "/path/to/foto-archive":/mnt/source \
    -v "/path/to/nextcloud/foto-thumbs":/mnt/target \
    -w /project thumbnailer \
    php run thumbnail:nef /mnt/source /mnt/target

docker run -it --rm \
    --mount 'source=$PWD,target=/project' \
    --mount 'source=$PWD/testfiles/src,target=/mnt/source' \
    --mount 'source=$PWD/testfiles/trg,target=/mnt/target' \
    -w /project thumbnailer \
    php run thumbnail:nef /mnt/source /mnt/target

docker run -it --rm -v "$PWD":/project -w /project php:7.2-stretch-cli php run
```

Hilfe
- zum PHP-Image: https://hub.docker.com/_/php/
- Docker Volumes: https://docs.docker.com/storage/volumes/#start-a-container-with-a-volume
- Docker Workdir: https://docs.docker.com/engine/reference/builder/#workdir


## NEF thumbnailer

Fetch thumbnail from NEF raw images files using `dcraw`. Walks and mirror the tree.

```
Call: php run thumbnail:nef '/path/to/foto-archive' '/path/to/nextcloud/foto-thumbs'
```

## MP4 Shrinker

Converts FullHD high bitrate videos to HDready low bitrate videos using `ffmpeg` and `ffprobe`. Walks and mirror the tree.

```
Call: php run thumbnail:mp4 '/path/to/movies' '/path/to/nextcloud/movie-thumbs'
```

## Setup Cronjobs
```
5 * * * * php /Users/you/Image-and-Video-Thumbnailer/run thumbnail:nef -s /path/to/foto-archive -t /path/to/nextcloud/foto-thumbs
15 * * * * php /Users/you/Image-and-Video-Thumbnailer/run thumbnail:mp4 -s /path/to/movies -t /path/to/nextcloud/movie-thumbs
```

You'll get nice notifications about the progress :)

## Works on Windows? Linux?

I have a Mac, so it is made for Mac. 

Most of the stuff works also on Linux (-`brew` +`apt-get install`).

Version for Windows? Feel free to fork it and make a PR :)


## Some background informations

**NEF to JPG with ImageMagick**
http://www.imagemagick.org/discourse-server/viewtopic.php?f=1&t=22988

```
convert input.NEF -quality 98% output.jpg

convert elephpant.NEF -auto-gamma -auto-level -auto-orient -thumbnail 2048x2048 elephpant.jpg
```

**NEF to JPG with `dcraw`**
http://superuser.com/questions/577643/how-do-i-batch-convert-thousands-of-nefs-to-jpegs
https://www.cybercom.net/~dcoffin/dcraw/

```
dcraw -c -w input.NEF | pnmtopng > output.png

brew install dcraw netpbm ufraw
```

extracts the thumbnail JPG out of the NEF!
```
dcraw -e input.NEF 
```


**Autocorrection by ImageMagick?**
http://stackoverflow.com/questions/5250409/imagemagick-auto-adjust-the-colours-of-an-image-a-la-other-photo-management-ap#5260446
http://www.imagemagick.org/script/command-line-options.php
Not necessary.

**Convert via Lightroom / Camera RAW via CLI?**
https://forums.adobe.com/thread/428398
Nope..
