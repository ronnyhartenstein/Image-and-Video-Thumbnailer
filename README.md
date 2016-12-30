# Auto-NEF-Thumbnailer für Nextcloud


**NEF zu JPG mit ImageMagick**
http://www.imagemagick.org/discourse-server/viewtopic.php?f=1&t=22988

```
convert input.NEF -quality 98% output.jpg

convert elephpant.NEF -auto-gamma -auto-level -auto-orient -thumbnail 2048x2048 elephpant.jpg
```

**NEF zu JPG mit dcraw**
http://superuser.com/questions/577643/how-do-i-batch-convert-thousands-of-nefs-to-jpegs
https://www.cybercom.net/~dcoffin/dcraw/

```
dcraw -c -w input.NEF | pnmtopng > output.png

brew install dcraw netpbm ufraw
```

extrahiert das in der NEF enthaltene Kamera-JPG!
```
dcraw -e input.NEF 
```


**Autokorrektur via ImageMagick?**
http://stackoverflow.com/questions/5250409/imagemagick-auto-adjust-the-colours-of-an-image-a-la-other-photo-management-ap#5260446
http://www.imagemagick.org/script/command-line-options.php


**Convert via Lightroom / Camera RAW via CLI?**
https://forums.adobe.com/thread/428398
Nope..


**PHP oder Go?**

Command Line Interface
PHP: http://inchoo.net/dev-talk/symfony2-cli/
Go: https://golang.org/pkg/flag/
