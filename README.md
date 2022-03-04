## About
Several standalone scripts to help manage image libraries through bulk transformation processes. Requires ImageMagick (https://pecl.php.net/package/imagick) and ScriptOutput (https://github.com/slserpent/script_output) libraries and tested on PHP 7.4.

## Scripts

### convert.php
Converts PNGs (or other image types) into JPEGs with subdirectory traversal. Writes the image file output to the same name and directory as the input file with the extension changed to .jpg.

### deletterbox.php
Removes letterboxing from images with subdirectory traversal. False positives are possible with solid colors along image edges, e.g. dark interiors, backdrops, or clear sky, so is best used on only images with known letterboxing. Can account for artifacting from low-quality JPEG artifacts. However, only works for solid color backgrounds; cannot remove letterboxing where the background is a zoomed blurred copy of the image. Use ImageMagick >7.0.8 for better accuracy.

### mirror.php
Mirrors images (flips horizontally across the y-axis) with subdirectory traversal. Useful for correcting mirroring from selfie cameras or mirroring to confuse image matching.

## Examples

### Convert all PNG and Bitmap images to JPEG

```dos
image_tools>php.exe "convert.php" "path=D:\Desktop\Downloads&pattern=/\.(png|bmp)$/i"
```

### Deletterbox images located in given directory but don't traverse subdirectories. Write output images to subdirectory "cropped".

```dos
image_tools>php.exe "deletterbox.php" "path=D:\Desktop\deletterbox&output=copy&traverse=0"
```

### Mirror images located in given directory and traverse subdirectories, but match only images with the EXIF tag "mirrored". Overwrites input files with output per defaults.

```dos
image_tools>php.exe "mirror.php" "path=D:\My Documents\My Pictures&tag=mirrored"
```