<?php
/*
 * Mirrors images (flips horizontally across the y-axis) with subdirectory traversal. Useful for correcting mirroring from selfie cameras or mirroring to confuse image matching.
 */

//the text to append to filenames when saving with rename output mode
$rename_text = ".mirrored";
//the subdirectory name when saving with rename output mode
$copy_dir = "mirrored";
//the minimum JPEG quality (0-100) when saving the cropped image, regardless of what the input quality was
$min_jpg_quality = 75;

require_once "util.php";
require_once "../script_output/script_output.php";
set_time_limit(0);

//the script can run in either CLI or CGI
$options = ScriptOutput::get_params();
//init the output class
$output = new ScriptOutput([['title' => "Mirror ImageMagick Script", 'wrap' => 120]]);

if (!can_iterate($options)) {
	$output->header("Options");
	$output->line("path: path to image directory");
	$output->line("output: how to output modified images, either 'rename', 'copy', or 'overwrite'. Rename places \$rename_text before image file extension in same directory. Copy puts image file in relative directory \$copy_dir with same filename. Default method is 'rename'.");
	$output->line("pattern: regex pattern for files to match. Default is a pattern matching *.jpg and *.png files.");
	$output->line("tag: optional EXIF keyword tag to filter on.");
	$output->line("traverse: boolean indicating whether or not to traverse subdirectories. Default is traverse true.");
	exit();
}

//handle input variables
if (has_value($options['path'])) {
    $path = $options['path'];
    if (!file_exists($path)) die("Invalid path.");
} else die("No path specified.");
if (has_value($options['output'])) {
    $output_method = $options['output'];
    if (!preg_match('/^(rename|copy|overwrite)$/i', $output_method)) die("Invalid output method.");
} else $output_method = "rename";
if (has_value($options['pattern'])) {
	$pattern = $options['output'];
} else $pattern = '/\.(png|jpg)$/i';
if (has_value($options['tag'])) {
	$tag = $options['tag'];
}
if (has_value($options['traverse'])) {
	$traverse = (bool)$options['traverse'];
} else $traverse = true;

$output->header("Current Options");
$output->line("Path: " . $path);
$output->line("Subdirectory Traversal: " . strtoupper(var_export($traverse, true)));
$output->line("Output Method: " . strtoupper($output_method));

//get all our image files using traversal util function
$images = traverse_directory($path, $pattern, $tag, $traverse);

$output->header("Mirroring");

$total_images = count($images);
$output->line("Total Images: $total_images");
$count_mirrored = 0;

$output->begin_section();
$output->set_wrap(false);

//iterate over matches
foreach ($images as $i => $image_file) {
	try {
		$mod_time = filemtime($image_file);
		$access_time = fileatime($image_file);

		//load file into imagick
		$image = new Imagick();
		$image->readImage($image_file);
		$in_quality = $image->getImageCompressionQuality();

		$image->flopImage();

		//choose what path to output the file as based on the output method option
		$path_parts = pathinfo($image_file);
		switch ($output_method) {
			case "copy":
				$target_dir = $path_parts['dirname'] . "\\$copy_dir";
				if (!file_exists($target_dir)) mkdir($target_dir);
				$target_filename = $target_dir . "\\" . $path_parts['basename'];
				break;
			case "overwrite":
				$target_filename = $image_file;
				break;
			case "rename":
			default:
				$target_filename = $path_parts['dirname'] . "\\" . $path_parts['filename'] . $rename_text . "." . $path_parts['extension'];
		}

		//should automatically set the image format to the input format
		$image->setImageCompressionQuality(max($in_quality, $min_jpg_quality));
		$image->writeImage($target_filename);
		$image->clear();

		$count_mirrored++;

		//copy file attributes over to new file
		if (touch($target_filename, $mod_time, $access_time) === false) {
			$output->line("Failed to copy file times for $image_file.");
		} else {
			$output->line(sprintf("[%d%%] Success: %s (%d%%) -> %s", round(($i/$total_images)*100), $image_file, $in_quality, $target_filename));
		}
	} catch (Exception $ex) {
		$output->line("Failure: " . $ex->getMessage());
	}
}
$output->end_section();

$output->begin_section();
$output->line("Images Mirrored: $count_mirrored");

if (has_value($tag)) {
	$output->line("Remember to remove tag. It is not possible with this program.");
}