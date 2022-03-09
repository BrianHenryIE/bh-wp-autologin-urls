<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://BrianHenry.ie
 * @since             1.0.0
 * @package           bh-wp-autologin-urls
 *
 * @wordpress-plugin
 * Plugin Name:       Autologin URLs
 * Plugin URI:        https://wordpress.org/plugins/bh-wp-autologin-urls
 * Description:       Adds autologin credentials to URLs to this site in emails sent to users.
 * Version:           1.3.0
 * Author:            BrianHenryIE
 * Author URI:        https://BrianHenry.ie
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wp-autologin-urls
 * Domain Path:       /languages
 */

namespace BH_WP_Autologin_URLs {

	use BrianHenryIE\WP_Autologin_URLs\API\API;
	use BrianHenryIE\WP_Autologin_URLs\API\DB_Data_Store;
	use BrianHenryIE\WP_Autologin_URLs\WP_Includes\BH_WP_Autologin_URLs;
	use BrianHenryIE\WP_Autologin_URLs\API\Settings;
	use BrianHenryIE\WP_Autologin_URLs\WP_Logger\Logger;

	// If this file is called directly, abort.
	if ( ! defined( 'ABSPATH' ) ) {
		throw new \Exception();
	}

	require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

	/**
	 * Currently plugin version.
	 */
	define( 'BH_WP_AUTOLOGIN_URLS_VERSION', '1.3.0' );
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

}

namespace {

	use BrianHenryIE\WP_Autologin_URLs\API\API_Interface;

	add_action( 'plugins_loaded', 'define_add_autologin_to_url_function', 2 );

	/**
	 * Create global functions for other plugins to use.
	 * Expected to be hooked early on plugins_loaded.
	 * Can be unhooked.
	 *
	 * This approach avoids users instantiating this object each time it is needed, thus preserving the cache.
	 */
	function define_add_autologin_to_url_function(): void {

		if ( ! function_exists( 'add_autologin_to_url' ) ) {

			/**
			 * Adds an autologin parameter to a URLs when possible.
			 *
			 * @param string             $url         The URL to append the autologin code to. This must be a link to this site.
			 * @param int|string|WP_User $user        A valid user id, email, login or user object.
			 * @param ?int               $expires_in  The number of seconds the code will work for.
			 *
			 * @return string
			 */
			function add_autologin_to_url( string $url, $user, ?int $expires_in = null ): string {

				/**
				 * The main plugin class with references to hooked classes.
				 *
				 * @var API_Interface $plugin_api
				 */
				$plugin_api = $GLOBALS['bh-wp-autologin-urls'];

				return $plugin_api->add_autologin_to_url( $url, $user, $expires_in );
			}
		}
	}
}
