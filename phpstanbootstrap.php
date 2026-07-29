<?php
/**
 * Define constants that PhpStan cannot find.
 *
 * @see https://phpstan.org/user-guide/discovering-symbols#global-constants
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

// define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content' );
// define( 'WP_PLUGIN_DIR', __DIR__ . '/wp-content/plugins' );

// Defined in `wp_cookie_constants()`, which runs too late for the stubs to declare them.
define( 'AUTH_COOKIE', 'wordpress_' );
define( 'SECURE_AUTH_COOKIE', 'wordpress_sec_' );
define( 'LOGGED_IN_COOKIE', 'wordpress_logged_in_' );
