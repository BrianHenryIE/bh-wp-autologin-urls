<?php
/**
 * Loads all required classes
 *
 * Uses classmap & wp-namespace-autoloader.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 * @package    brianhenryie/bh-wp-autologin-urls
 *
 * @see https://github.com/pablo-sg-pacheco/wp-namespace-autoloader/
 */

namespace BrianHenryIE\WP_Autologin_URLs;

use BrianHenryIE\WP_Autologin_URLs\Alley_Interactive\Autoloader\Autoloader;

if ( file_exists( __DIR__ . '/vendor-prefixed/autoload.php' ) ) {
	require_once __DIR__ . '/vendor-prefixed/autoload.php';
}
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

Autoloader::generate(
	__NAMESPACE__,
	__DIR__ . '/src',
)->register();
