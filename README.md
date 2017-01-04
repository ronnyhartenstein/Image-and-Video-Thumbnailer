# Auto-NEF-Thumbnailer and Auto-MP4-Shrinker for Nextcloud

My Motivation: I have a Nextcloud. I make photos and videos. I don't want to sync this big raw files. 
I want to have a smaller web-version to share it efficent. It should work automatically on new files.
It scans a source directory tree, convert it, and store it in a target directory tree - like a mirror.
Then the Nextcloud Uploader get it and upload it.

## Installation

0. Clone this repo.  (e.g. in `/Users/you/Image-and-Video-Thumbnailer`)
1. Install some stuff: `brew install dcraw netpbm ufraw ffmpeg php`
2. Run scripts manually
3. optional: Install as cronjob

## NEF thumbnailer

Fetch thumbnail from NEF raw images files using `dcraw`. Walks and mirror the tree.

```
Call: php nef-thumbnailer.php -s '/path/to/foto-archive' -t '/path/to/nextcloud/foto-thumbs'
Options:
  -h            this help
  -s path/to    Path with source NEF files
  -t path/to    Target path for thumbnails (e.g. Nextcloud sync folder)
```



## MP4 Shrinker

Converts FullHD high bitrate videos to HDready low bitrate videos using `ffmpeg` and `ffprobe`. Walks and mirror the tree.

```
Call: php mp4-thumbnailer.php -s '/path/to/movies' -t '/path/to/nextcloud/movie-thumbs'
Options:
  -h            this help
  -s path/to    Path with source video files (MP4)
  -t path/to    Target path for shrinked video (e.g. Nextcloud sync folder)
  -f            Force rebuild
```

## Setup Cronjobs
```
5 * * * * php /Users/you/Image-and-Video-Thumbnailer/nef-thumbnailer.php -s /path/to/foto-archive -t /path/to/nextcloud/foto-thumbs
15 * * * * php /Users/you/Image-and-Video-Thumbnailer/mp4-thumbnailer.php -s /path/to/movies -t /path/to/nextcloud/movie-thumbs
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
