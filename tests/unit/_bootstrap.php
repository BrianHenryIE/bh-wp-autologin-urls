<?php
/**
 * PHPUnit bootstrap file for WP_Mock.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

global $plugin_root_dir;
require_once $plugin_root_dir . '/autoload.php';

WP_Mock::bootstrap();
