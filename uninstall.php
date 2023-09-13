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
	throw new \Exception( 'WP_UNINSTALL_PLUGIN not defined' );
}

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;

/**
 * Delete the passwords stored as transients in wp_options.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */
function bh_wp_autologin_urls_clear_transients(): void {
	global $wpdb;

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_bh_autologin_bh_autologin_%' ),
			$wpdb->esc_like( '_transient_timeout_bh_autologin_%' )
		)
	);
}
bh_wp_autologin_urls_clear_transients();

/**
 * Delete each of the wp_options entries used by the plugin.
 *
 * @see Settings_Interface
 */
function bh_wp_autologin_urls_delete_settings(): void {

	// TODO: ReflectionClass::getConstants.

	delete_option( 'bh_wp_autologin_urls_seconds_until_expiry' );
	delete_option( 'bh_wp_autologin_urls_is_admin_enabled' );
	delete_option( 'bh_wp_autologin_urls_subject_filter_regex_dictionary' );
}
bh_wp_autologin_urls_delete_settings();


/**
 * Delete the database table used to save the passwords.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
 */
function bh_wp_autologin_urls_drop_table(): void {
	global $wpdb;

	$wpdb->query(
		"DROP TABLE IF EXISTS {$wpdb->prefix}autologin_urls"
	);
}
bh_wp_autologin_urls_drop_table();
