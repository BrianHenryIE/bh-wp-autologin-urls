<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://BrianHenry.ie
 * @since             1.0.0
 * @package           brianhenryie/bh-wp-autologin-urls
 *
 * @wordpress-plugin
 * Plugin Name:       Magic Emails & Autologin URLs
 * Plugin URI:        https://wordpress.org/BrianHenryIE/bh-wp-autologin-urls
 * Description:       Log in users via emails sent from WordPress.
 * Version:           2.4.2
 * Tested up to:      6.4
 * Requires PHP:      7.4
 * Author:            BrianHenryIE
 * Author URI:        https://BrianHenry.ie
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wp-autologin-urls
 * Domain Path:       /languages
 *
 * GitHub Plugin URI: https://github.com/BrianHenryIE/bh-wp-autologin-urls
 * Release Asset:     true
 */

namespace BH_WP_Autologin_URLs;

use BrianHenryIE\WP_Autologin_URLs\API\API;
use BrianHenryIE\WP_Autologin_URLs\API\Data_Stores\DB_Data_Store;
use BrianHenryIE\WP_Autologin_URLs\BH_WP_Autologin_URLs;
use BrianHenryIE\WP_Autologin_URLs\API\Settings;
use BrianHenryIE\WP_Autologin_URLs\WP_Logger\Logger;
use Exception;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	throw new Exception();
}

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

/**
 * Currently plugin version.
 */
define( 'BH_WP_AUTOLOGIN_URLS_VERSION', '2.4.2' );
define( 'BH_WP_AUTOLOGIN_URLS_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Function to keep the loader and settings objects out of the namespace.
 *
 * @return API
 */
function instantiate_bh_wp_autologin_urls(): API {

	$settings  = new Settings();
	$logger    = Logger::instance( $settings );
	$datastore = new DB_Data_Store( $logger );
	$api       = new API( $settings, $logger, $datastore );

	new BH_WP_Autologin_URLs( $api, $settings, $logger );

	return $api;
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
$GLOBALS['bh-wp-autologin-urls'] = instantiate_bh_wp_autologin_urls();
