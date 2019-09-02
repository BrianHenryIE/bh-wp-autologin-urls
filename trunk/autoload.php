<?php
/**
 * Loads all required classes
 *
 * Uses wp-namespace-autoloader for plugin files and require_once for libs.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 * @package    bh-wp-autologin-urls
 *
 * @see https://github.com/pablo-sg-pacheco/wp-namespace-autoloader/
 */
require_once 'lib/wppb/interface-wppb-loader.php';
require_once 'lib/wppb/class-wppb-loader.php';
require_once 'lib/wppb/class-wppb-object.php';

require_once 'lib/wp-namespace-autoloader/class-wp-namespace-autoloader.php';

use BH\Pablo_Pacheco\WP_Namespace_Autoloader\WP_Namespace_Autoloader;

$autoloader = new WP_Namespace_Autoloader(
	array(
		'directory'        => __DIR__,
		'namespace_prefix' => 'BH_WP_Autologin_URLs',
	)
);
$autoloader->init();
