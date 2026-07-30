#!/usr/bin/env php
<?php

/**
 * Sync .wp-env.json and .wp-env.ci.json with the current state of wp-content/plugins and composer.lock.
 *
 * 1. Remove mappings whose values point to files/directories that no longer exist.
 * 2. Add directories found in wp-content/plugins to mappings (preserving existing custom values).
 * 3. Update the WordPress core version from the johnpbloch/wordpress-core entry in composer.lock.
 * 4. For .wp-env.ci.json only: keep the plugins key empty. CI installs the built zip via
 *    tests/_wp-env/initialize-internal.sh instead of mounting the working directory.
 */

$wpEnvPaths       = array( '.wp-env.json', '.wp-env.ci.json' );
$composerLockPath = 'composer.lock';

// Read the WordPress core version from composer.lock once, it is the same for every target file.
$composerLockFileContents = file_get_contents( $composerLockPath );
if ( ! is_string( $composerLockFileContents ) ) {
	throw new \RuntimeException( 'Failed to get contents of ' . $composerLockPath );
}
$composerLock = json_decode( $composerLockFileContents, true );
if ( $composerLock === null ) {
	fwrite( STDERR, "Failed to parse {$composerLockPath}\n" );
	exit( 1 );
}

$core = null;
foreach ( $composerLock['packages-dev'] ?? array() as $package ) {
	if ( $package['name'] === 'johnpbloch/wordpress-core' ) {
		$version = preg_replace( '/\.0$/', '', $package['version'] );
		$core    = 'WordPress/WordPress#' . $version;
		break;
	}
}

foreach ( $wpEnvPaths as $wpEnvPath ) {

	if ( ! file_exists( $wpEnvPath ) ) {
		echo "Skipping missing {$wpEnvPath}\n";
		continue;
	}

	$wpEnvFileContents = file_get_contents( $wpEnvPath );
	if ( ! is_string( $wpEnvFileContents ) ) {
		throw new \RuntimeException( 'Failed to get contents of ' . $wpEnvPath );
	}

	$wpEnv = json_decode( $wpEnvFileContents, true );
	if ( $wpEnv === null ) {
		fwrite( STDERR, "Failed to parse {$wpEnvPath}\n" );
		exit( 1 );
	}

	// 1. Remove stale mappings.
	foreach ( $wpEnv['mappings'] ?? array() as $key => $value ) {
		if ( ! file_exists( $value ) ) {
			echo "Removing stale mapping from {$wpEnvPath}: {$value}\n";
			unset( $wpEnv['mappings'][ $key ] );
		}
	}

	// 2. Add plugin directories (existing custom values take precedence).
	$pluginsDir = 'wp-content/plugins';
	if ( is_dir( $pluginsDir ) ) {
		foreach ( scandir( $pluginsDir ) as $entry ) {
			if ( $entry === '.' || $entry === '..' ) {
				continue;
			}
			$path = $pluginsDir . '/' . $entry;
			if ( is_dir( $path ) && ! is_link( $path ) && ! isset( $wpEnv['mappings'][ $path ] ) ) {
				$wpEnv['mappings'][ $path ] = './' . $path;
			}
		}
		ksort( $wpEnv['mappings'] );
	}

	// 3. Update WordPress core version from composer.lock.
	if ( $core !== null ) {
		$wpEnv['core'] = $core;
	}

	// 4. CI runs against the built zip, which tests/_wp-env/initialize-internal.sh installs with
	// `wp plugin install`. wp-env must not also mount the working directory over the same slug.
	if ( $wpEnvPath === '.wp-env.ci.json' ) {
		$wpEnv['plugins'] = array();
	}

	$json = json_encode( $wpEnv, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR );
	// json_encode uses 4-space indentation; convert to 2-space to match .wp-env.json convention.
	$json = preg_replace_callback( '/^( +)/m', fn( $m ) => str_repeat( ' ', (int) ( strlen( $m[1] ) / 2 ) ), $json );

	file_put_contents( $wpEnvPath, $json . "\n" );
}
