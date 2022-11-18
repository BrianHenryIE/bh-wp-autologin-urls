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

require_once __DIR__ . '/vendor-prefixed/autoload.php';

Autoloader::generate(
	__NAMESPACE__,
	__DIR__ . '/src',
)->register();
