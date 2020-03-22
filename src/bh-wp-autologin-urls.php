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
 * Version:           1.1.2
 * Author:            BrianHenryIE
 * Author URI:        https://BrianHenry.ie
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wp-autologin-urls
 * Domain Path:       /languages
 */

namespace BH_WP_Autologin_URLs {

	use BH_WP_Autologin_URLs\WPPB\WPPB_Loader;
	use BH_WP_Autologin_URLs\includes\BH_WP_Autologin_URLs;
	use BH_WP_Autologin_URLs\includes\Settings;

	// If this file is called directly, abort.
	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

	/**
	 * Currently plugin version.
	 */
	define( 'BH_WP_AUTOLOGIN_URLS_VERSION', '1.1.2' );

	/**
	 * Function to keep the loader and settings objects out of the namespace.
	 *
	 * @return BH_WP_Autologin_URLs
	 */
	function instantiate_bh_wp_autologin_urls() {

		$loader = new WPPB_Loader();

		$settings = new Settings();

		$bh_wp_autologin_urls = new BH_WP_Autologin_URLs( $loader, $settings );

		return $bh_wp_autologin_urls;
	}

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 *
	 * phpcs:disable Squiz.PHP.DisallowMultipleAssignments.Found
	 */
	$GLOBALS['bh-wp-autologin-urls'] = $bh_wp_autologin_urls = instantiate_bh_wp_autologin_urls();
	$bh_wp_autologin_urls->run();

}

namespace {

	use BH_WP_Autologin_URLs\includes\BH_WP_Autologin_URLs;
	use BH_WP_Autologin_URLs\api\API_Interface;

	add_action( 'plugins_loaded', 'define_add_autologin_to_url_function', 2 );

	/**
	 * Create global functions for other plugins to use.
	 * Expected to be hooked early on plugins_loaded.
	 * Can be unhooked.
	 *
	 * This approach avoids users instantiating this object each time it is needed, thus preserving the cache.
	 */
	function define_add_autologin_to_url_function() {

		if ( ! function_exists( 'add_autologin_to_url' ) ) {

			/**
			 * Adds an autologin parameter to a URLs when possible.
			 *
			 * @param string             $url         The URL to append the autologin code to. This must be a link to this site.
			 * @param int|string|WP_User $user        A valid user id, email, login or user object.
			 * @param int                $expires_in  The number of seconds the code will work for.
			 *
			 * @return string
			 */
			function add_autologin_to_url( $url, $user, $expires_in = null ) {

				/**
				 * The main plugin class with references to hooked classes.
				 *
				 * @var BH_WP_Autologin_URLs $plugin
				 */
				$plugin = $GLOBALS['bh-wp-autologin-urls'];

				/**
				 * API class with methods for generating and validating codes.
				 *
				 * @var API_Interface $api
				 */
				$api = $plugin->api;

				return $api->add_autologin_to_url( $url, $user, $expires_in );
			}
		}
	}
}
