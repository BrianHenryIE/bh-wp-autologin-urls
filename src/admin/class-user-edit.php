<?php
/**
 * The additions to the admin user-edit page.
 *
 * TODO: Send magic login email button.
 *
 * @link       https://BrianHenry.ie
 * @since      1.2.0
 *
 * @package    bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use WP_User;

/**
 * The extra field on the user edit page.
 */
class User_Edit {

	/**
	 * Plugin settings, for determining the template path.
	 */
	protected Settings_Interface $settings;

	/**
	 * Core API methods to generate password/URL.
	 */
	protected API_Interface $api;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param API_Interface      $api The core plugin functions.
	 * @param Settings_Interface $settings The plugin settings.
	 *
	 * @since   1.0.0
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings ) {
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Add a field on the admin view of the user profile which contains a login URL.
	 * For use e.g. in support emails. ...tests.
	 *
	 * @hooked edit_user_profile
	 * @hooked show_user_profile
	 *
	 * @see wordpress/wp-admin/user-edit.php
	 *
	 * @param WP_User $profileuser The current WP_User object.
	 */
	public function make_password_available_on_user_page( WP_User $profileuser ): void {

		// TODO: If WooCommerce is installed, this should go to my-account.
		$append        = '/';
		$autologin_url = $this->api->add_autologin_to_url( get_site_url() . $append, $profileuser, WEEK_IN_SECONDS );

		$template = 'admin/user-edit.php';

		$template_filepath = WP_PLUGIN_DIR . '/' . plugin_dir_path( $this->settings->get_plugin_basename() ) . 'templates/' . $template;

		// Check the child theme for template overrides.
		if ( file_exists( get_stylesheet_directory() . $template ) ) {
			$template_filepath = get_stylesheet_directory() . $template;
		} elseif ( file_exists( get_stylesheet_directory() . 'templates/' . $template ) ) {
			$template_filepath = get_stylesheet_directory() . 'templates/' . $template;
		}

		/**
		 * Allow overriding the admin settings template.
		 *
		 * @param string $template_filepath The default or child-theme-overridden template to display an autologin url on the user profile edit admin ui.
		 * @param array $args The variables that will be available to the template.
		 */
		$filtered_template_admin_settings_page = apply_filters( 'bh_wp_autologin_urls_admin_user_edit_template', $template_filepath, func_get_args() );

		if ( file_exists( $filtered_template_admin_settings_page ) ) {
			include $filtered_template_admin_settings_page;
		} else {
			include $template_filepath;
		}
	}
}
