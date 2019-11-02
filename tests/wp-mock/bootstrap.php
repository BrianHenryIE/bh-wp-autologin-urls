<?php
/**
 * PHPUnit bootstrap file for WP_Mock.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

$project_root_dir   = dirname( __FILE__, 3 );
$plugin_root_dir    = $project_root_dir . '/trunk';
$plugin_name        = basename( $project_root_dir );
$plugin_name_php    = $plugin_name . '.php';
$plugin_path_php    = $plugin_root_dir . '/' . $plugin_name_php;
$plugin_basename    = $plugin_name . '/' . $plugin_name_php;
$wordpress_root_dir = $project_root_dir . '/vendor/wordpress/wordpress/src';

require_once $project_root_dir . '/vendor/autoload.php'; // Composer autoloader.

require_once $plugin_root_dir . '/autoload.php';

WP_Mock::bootstrap();

