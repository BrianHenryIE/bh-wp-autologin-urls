<?php
/**
 * Loads all required classes
 *
 * Uses PSR4 & wp-namespace-autoloader.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 * @package    bh-wp-autologin-urls
 *
 * @see https://github.com/pablo-sg-pacheco/wp-namespace-autoloader/
 */

namespace BH_WP_Autologin_URLs;

use BrianHenryIE\WP_Autologin_URLs\Pablo_Pacheco\WP_Namespace_Autoloader\WP_Namespace_Autoloader;

require_once __DIR__ . '/strauss/autoload.php';

$wpcs_autoloader = new WP_Namespace_Autoloader();
$wpcs_autoloader->init();
