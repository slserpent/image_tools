<?php
/*
 * Converts PNGs (or other image types) into JPEGs with subdirectory traversal. Writes the image file output to the same name and directory as the input file with the extension changed to .jpg.
 */

require_once "util.php";
require_once "../script_output/script_output.php";
set_time_limit(0);

//the script can run in either CLI or CGI
$options = ScriptOutput::get_params();
//init the output class
$output = new ScriptOutput([['title' => "Convert-to-JPEG ImageMagick Script", 'wrap' => 120]]);

if (!can_iterate($options)) {
	$output->header("Options");
	$output->begin_section();
	$output->line("path: path to image directory");
	$output->line("pattern: regex pattern for files to match. Default is a pattern matching *.png files, but could also include *.bmp or other formats if desired.");
	$output->line("traverse: boolean indicating whether or not to traverse subdirectories. Default is traverse true.");
	$output->line("overwrite: boolean indicating whether or not to overwrite a file if it has the same name as the input file with .jpg extension. Default is false.");
	$output->line("delete: boolean indicating whether or not to delete input files on successful conversion. Default is true.");
	$output->end_section();
	exit();
}

//handle input variables
if (has_value($options['path'])) {
	$path = $options['path'];
	if (!file_exists($path)) die("Invalid path.");
} else die("No path specified.");
if (has_value($options['pattern'])) {
	$pattern = $options['output'];
} else $pattern = '/\.png$/i';
if (has_value($options['traverse'])) {
	$traverse = (bool)$options['traverse'];
} else $traverse = true;
if (has_value($options['overwrite'])) {
	$overwrite = (bool)$options['overwrite'];
} else $overwrite = false;
if (has_value($options['delete'])) {
	$delete_on_success = (bool)$options['delete'];
} else $delete_on_success = true;

$output->begin_section("Current Options");
$output->line("Path: " . $path);
$output->line("Pattern: " . $pattern);
$output->line("Traverse Subdirectories: " . ScriptOutput::data_to_string($traverse));
$output->line("Overwrite Existing Targets: " . ScriptOutput::data_to_string($overwrite));
$output->line("Delete On Successful Conversion: " . ScriptOutput::data_to_string($delete_on_success));
$output->end_section();

$output->begin_section();
$output->line("Gathering List of Files...");
$images = traverse_directory($path, $pattern, null, $traverse);
$output->line(count($images) . " images found matching pattern.");
$output->end_section();

//iterate over matches
$output->begin_section();
$total_source_filesize = 0;
$total_target_filesize = 0;
foreach ($images as $image_file) {
	$output->line("Converting $image_file...");

	//save as jpg with same filename and different extension
	$path_parts = pathinfo($image_file);
	$target_filename = $path_parts['dirname'] . "\\" . $path_parts['filename'] . ".jpg";

	if (file_exists($target_filename)) {
		if ($overwrite) {
			$output->line("Target file $target_filename already exists. Overwriting per options.");
		} else {
			$output->line("Target file $target_filename already exists. Skipping per options.");
			continue;
		}
	}

	//load file into imagick, convert, and save
	$image = new Imagick($image_file);
	$image->setImageFormat('jpeg');
	$image->setImageCompressionQuality(95);
	$image->writeImage($target_filename);
	$image->clear();

	$total_source_filesize += filesize($image_file);
	$total_target_filesize += filesize($target_filename);

	//copy file attributes over to new file
	//currently no native way to change file create time in PHP
	if (touch($target_filename, filemtime($image_file), fileatime($image_file)) === false) {
		$output->line("Failed to copy file times.");
		continue;
	} else {
		//only delete if attribute copy success
		if ($delete_on_success) {
			if (unlink($image_file) === false) {
				$output->line("Failed to delete source file.");
				continue;
			} else {
				$output->line("Source file deleted successfully.");
			}
		}

		print "Source file converted successfully to $target_filename.\n";
	}
}
$output->end_section();

$output->line("Total filesize savings: " . print_filesize($total_source_filesize - $total_target_filesize));


