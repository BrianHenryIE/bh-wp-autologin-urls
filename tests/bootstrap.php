<?php
/**
 * PHPUnit bootstrap file for WP_Mock.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

$GLOBALS['project_root_dir']   = $project_root_dir  = dirname( __FILE__, 2 );
$GLOBALS['plugin_root_dir']    = $plugin_root_dir   = $project_root_dir . '/src';
$GLOBALS['plugin_name']        = $plugin_name       = basename( $project_root_dir );
$GLOBALS['plugin_name_php']    = $plugin_name_php   = $plugin_name . '.php';
$GLOBALS['plugin_path_php']                         = $plugin_root_dir . '/' . $plugin_name_php;
$GLOBALS['plugin_basename']                         = $plugin_name . '/' . $plugin_name_php;
$GLOBALS['wordpress_root_dir']                      = $project_root_dir . '/vendor/wordpress/wordpress/src';

//require_once $project_root_dir . '/vendor/autoload.php'; // Composer require-dev autoloader.

require_once $plugin_root_dir . '/autoload.php';
