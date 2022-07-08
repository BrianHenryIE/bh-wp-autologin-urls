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

use BrianHenryIE\WP_Autologin_URLs\Pablo_Pacheco\WP_Namespace_Autoloader\WP_Namespace_Autoloader;

require_once __DIR__ . '/vendor-prefixed/autoload.php';

$wpcs_autoloader = new WP_Namespace_Autoloader( array( 'classes_dir' => array( 'src' ) ) );
$wpcs_autoloader->init();
