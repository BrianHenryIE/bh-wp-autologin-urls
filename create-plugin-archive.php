<?php
/**
 * Creates a zip archive of the plugin ready to upload to WordPress.
 *
 * Filename takes the form "plugin-slug-version.zip", e.g. "bh-wp-autologin-urls-1.2.0.zip".
 * The current directory name is used as the plugin slug.
 * The version number is read from the plugin file at "src/plugin-slug.php".
 *
 * composer require nelexa/zip --dev --no-scripts
 *
 * @see https://github.com/Ne-Lexa/php-zip
 */

use PhpZip\ZipFile;

require_once 'vendor/autoload.php';

// TODO: If Mozart is in composer.json, check vendor or dependencies directories have been created (i.e. was
// `mozart compose` run before this?).

if ( ! file_exists( 'src' ) ) {
	echo 'No "src" directory to add to zip file. Check current working directory.';
	exit();
}

// Get plugin slug from directory name.
$plugin_slug = basename( dirname( __FILE__ ) );

$plugin_file = __DIR__ . "/src/{$plugin_slug}.php";

if ( ! file_exists( $plugin_file ) ) {
	echo "Plugin file missing at \"/src/{$plugin_slug}.php\". Current directory name is used to infer plugin slug and filename.";
	exit();
}


$zip_file = new ZipFile();

// Add the content.
$zip_file->addDirRecursive( 'src', $plugin_slug );

// Read version from mail plugin file.
// " * Version:           1.2.0"
$version = '';

$fp = fopen( $plugin_file, 'r+' );
if ( false !== $fp ) {
	while ( $line = stream_get_line( $fp, 1024 * 1024, "\n" ) ) {
		$output_array = array();
		if ( 1 === preg_match( '/\s+\*\sVersion:\s+(?<version>\d+\.\d+\.\d+)/', $line, $output_array ) ) {

			$version = '-' . $output_array['version'];
			break;
		}
	}
}
fclose( $fp );


$zip_file->saveAsFile( "{$plugin_slug}{$version}.zip" );
