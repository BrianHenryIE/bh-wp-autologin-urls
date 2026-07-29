#!/usr/bin/env php
<?php

/**
 * Sync .wp-env.json with the current state of wp-content/plugins and composer.lock.
 *
 * 1. Remove mappings whose values point to files/directories that no longer exist.
 * 2. Add directories found in wp-content/plugins to mappings (preserving existing custom values).
 * 3. Update the WordPress core version from the johnpbloch/wordpress-core entry in composer.lock.
 */

$wpEnvPath        = '.wp-env.json';
$composerLockPath = 'composer.lock';

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
		echo "Removing stale mapping: {$value}\n";
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
$composerLockFileontents = file_get_contents( $composerLockPath );
if ( ! is_string( $composerLockFileontents ) ) {
	throw new \RuntimeException( 'Failed to get contents of ' . $composerLockPath );
}
$composerLock = json_decode( $composerLockFileontents, true );
if ( $composerLock === null ) {
	fwrite( STDERR, "Failed to parse {$composerLockPath}\n" );
	exit( 1 );
}

foreach ( $composerLock['packages-dev'] ?? array() as $package ) {
	if ( $package['name'] === 'johnpbloch/wordpress-core' ) {
		$version       = preg_replace( '/\.0$/', '', $package['version'] );
		$wpEnv['core'] = 'WordPress/WordPress#' . $version;
		break;
	}
}

$json = json_encode( $wpEnv, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR );
// json_encode uses 4-space indentation; convert to 2-space to match .wp-env.json convention.
$json = preg_replace_callback( '/^( +)/m', fn( $m ) => str_repeat( ' ', (int) ( strlen( $m[1] ) / 2 ) ), $json );

file_put_contents( $wpEnvPath, $json . "\n" );
