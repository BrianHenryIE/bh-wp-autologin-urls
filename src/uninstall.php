<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Require files for constants.
require_once plugin_dir_path( __FILE__ ) . 'includes/interface-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'api/interface-api.php';
require_once plugin_dir_path( __FILE__ ) . 'api/class-api.php';

use BH_WP_Autologin_URLs\api\API;
use BH_WP_Autologin_URLs\includes\Settings;

/**
 * Delete the passwords stored as transients in wp_options.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */
function bh_wp_autologin_urls_clear_transients() {
	global $wpdb;

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_' . API::TRANSIENT_PREFIX . '%' ),
			$wpdb->esc_like( '_transient_timeout_' . API::TRANSIENT_PREFIX . '%' )
		)
	);

}
bh_wp_autologin_urls_clear_transients();

/**
 * Delete each of the wp_options entries used by the plugin.
 */
function bh_wp_autologin_urls_delete_settings() {

	delete_option( Settings::EXPIRY_TIME_IN_SECONDS );
	delete_option( Settings::ADMIN_ENABLED );
	delete_option( Settings::SUBJECT_FILTER_REGEX_DICTIONARY );

}
bh_wp_autologin_urls_delete_settings();
