<?php
/**
 * PHPUnit bootstrap file for WP_Mock.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();

/** @var string $project_root_dir */
global $project_root_dir;
require_once $project_root_dir . '/wordpress/wp-includes/class-wp-user.php';
