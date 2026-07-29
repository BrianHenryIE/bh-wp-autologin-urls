<?php
/**
 * Move Patchwork's Composer "files" autoload entry to the front of the list so
 * Patchwork is loaded before any other autoloaded file. Otherwise a file loaded
 * earlier can pull in a class (e.g. WP_CLI) before Patchwork is able to
 * instrument it, causing:
 *
 * Patchwork\Exceptions\DefinedTooEarly : The file that defines WP_CLI::line() was included earlier than Patchwork. Please reverse this order to be able to redefine the function in question.
 *
 * Run after `composer dump-autoload` (e.g. from a `post-autoload-dump` script).
 *
 * @package brianhenryie/bh-wp-cli-logger
 */

$vendor_dir     = __DIR__ . '/../vendor';
$autoload_files = array(
	$vendor_dir . '/composer/autoload_files.php',
	$vendor_dir . '/composer/autoload_static.php',
);

// Whether Patchwork is actually installed. When it is not (e.g. a `--no-dev`
// install) there is nothing to hoist and silence is correct; when it is, failing
// to hoist it is a real problem worth warning about.
$patchwork_installed = is_dir( $vendor_dir . '/antecedent/patchwork' );
$hoisted_any         = false;

foreach ( $autoload_files as $autoload_file ) {

	if ( ! is_file( $autoload_file ) ) {
		continue;
	}

	// Read the file into an array of lines, preserving line endings.
	$lines = file( $autoload_file );

	if ( false === $lines ) {
		fwrite( STDERR, "modify-autoload: failed to read {$autoload_file}\n" );
		continue;
	}

	// Find the line with the Patchwork entry.
	$patchwork_index = null;
	foreach ( $lines as $index => $line ) {
		if ( false !== strpos( $line, '/antecedent/patchwork/Patchwork.php' ) ) {
			$patchwork_index = $index;
			break;
		}
	}

	if ( null === $patchwork_index ) {
		continue;
	}

	// Find the line that opens the array enclosing the Patchwork entry. Searching
	// before removing the entry keeps these indices valid: the opener always
	// precedes the entry, so it is unaffected by the later removal.
	$opener_index = null;
	for ( $i = $patchwork_index - 1; $i >= 0; $i-- ) {
		if ( 1 === preg_match( '/array\s*\(/', $lines[ $i ] ) ) {
			$opener_index = $i;
			break;
		}
	}

	if ( null === $opener_index ) {
		fwrite( STDERR, "modify-autoload: could not find the array opener in {$autoload_file}; skipping to avoid corrupting it\n" );
		continue;
	}

	// Move the Patchwork entry to the top of its array, just after the opener.
	$patchwork_line = $lines[ $patchwork_index ];
	unset( $lines[ $patchwork_index ] );
	$lines = array_values( $lines );
	array_splice( $lines, $opener_index + 1, 0, $patchwork_line );

	if ( false === file_put_contents( $autoload_file, implode( '', $lines ) ) ) {
		fwrite( STDERR, "modify-autoload: failed to write {$autoload_file}\n" );
		exit( 1 );
	}

	$hoisted_any = true;
}

if ( $patchwork_installed && ! $hoisted_any ) {
	fwrite( STDERR, "modify-autoload: WARNING - Patchwork is installed but its autoload entry was not hoisted; the unit suite may fail with Patchwork\\Exceptions\\DefinedTooEarly.\n" );
}
