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
