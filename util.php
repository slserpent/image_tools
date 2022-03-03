<?php

/**
 * for checking if input variables have a valid value, i.e. is set and not empty string
 * @param $value
 * @return bool
 */
function has_value($value) {
	//passing by reference is apparently not useful as variables are copy-on-write
	if (!isset($value)) return false;
	if ($value === "") return false;
	return true;
}

/**
 * for checking if a variable can be used in a foreach loop, i.e. is an array with elements
 * @param $value
 * @return bool
 */
function can_iterate($value) {
	//passing by reference is apparently not useful as variables are copy-on-write
	if (!isset($value)) return false;
	if (!is_array($value)) return false;
	if (count($value) == 0) return false;
	return true;
}

/**
 * Traverse directory finding all files that match pattern
 * @param string $path The path to search
 * @param string $pattern A preg regex pattern to match against filenames
 * @param string $tag_filter A EXIF keyword tag to filter by
 * @param bool $traverse Whether or not to traverse subdirectories
 * @return array Array of filenames
 */
 function traverse_directory($path, $pattern, $tag_filter = null, $traverse = true) {
    $return_images = [];
    $path = realpath($path); //removes trailing slash and other errant characters

    if ($handle = opendir($path)) {
        while (false !== ($filename = readdir($handle))) {
            //if not parent or current directory
            if ($filename != "." && $filename != "..") {
                $filepath = "$path\\$filename";
                if (is_dir($filepath)) {
                    //if directory, traverse and then merge the results
                    if ($traverse) {
                        $return_images = array_merge($return_images, traverse_directory($filepath, $pattern, $tag_filter, $traverse));
                    }
                } else {
                	//must match both filename filter and tag filter (if applicable)
                	$image_match = false;
					if (preg_match($pattern, $filename)) $image_match = true;
					if (($image_match == true) && isset($tag_filter)) {
						if (image_has_tag($filepath, $tag_filter)) $image_match = true; else $image_match = false;
					}

					if ($image_match == true) {
                    	$return_images[] = $filepath;
                    }
                }
            }
        }
        closedir($handle);
    }
    return $return_images;
}

function image_has_tag($image, $tag_filter) {
	if (($exif = @exif_read_data($image, "IFD0", true)) !== false) {
		if (has_value($exif['IFD0']['Keywords'])) {
			//remove nullchars cause the string gets converted wrong
			$tags = explode(";", str_replace("\0", "", $exif['IFD0']['Keywords']));
			if (can_iterate($tags)) {
				foreach ($tags as $tag) {
					if ($tag == $tag_filter) {
						return true;
					}
				}
			}
		}
	}
	return false;
}

//takes a filesize in bytes and returns the appropriate size with abbreviation
function print_filesize($size) {
	if ($size < 1024) return $size . " B";
	$count = 0;
	$format = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
	while(($size/1024) > 1 && $count < 8) {
		$size=$size/1024;
		$count++;
	}
	return number_format($size,1,'.',',') . " " . $format[$count];
}