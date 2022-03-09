<?php
/**
 * PHPUnit bootstrap file for WP_Mock.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();

global $plugin_root_dir;
require_once $plugin_root_dir . '/autoload.php';

global $project_root_dir;
require_once $project_root_dir . '/vendor/wordpress/wordpress/src/wp-includes/class-wp-user.php';
