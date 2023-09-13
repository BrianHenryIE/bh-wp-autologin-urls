<?php
/**
 * The login screen specific functionality of the plugin.
 *
 * @link       https://BrianHenry.ie
 * @since      1.7.0
 *
 * @package    brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\Login;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;

/**
 * The login screen functionality of the plugin.
 */
class Login_Assets {

	/**
	 * Needed for the css/js handle, and the version for cache invalidation.
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	/**
	 * Constructor
	 *
	 * @param Settings_Interface $settings Plugin settings for slug and version.
	 */
	public function __construct( Settings_Interface $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @hooked admin_enqueue_scripts
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {

		if ( ! $this->settings->is_magic_link_enabled() ) {
			return;
		}

		wp_enqueue_style( $this->settings->get_plugin_slug(), plugin_dir_url( $this->settings->get_plugin_basename() ) . 'assets/bh-wp-autologin-urls-login.css', array(), $this->settings->get_plugin_version(), 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @hooked admin_enqueue_scripts.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {

		if ( ! $this->settings->is_magic_link_enabled() ) {
			return;
		}

		$handle = 'bh-wp-autologin-urls-login-screen';

		wp_enqueue_script( $handle, plugin_dir_url( $this->settings->get_plugin_basename() ) . 'assets/bh-wp-autologin-urls-login.js', array( 'jquery' ), $this->settings->get_plugin_version(), true );

		$ajax_data      = array(
			'ajaxurl'   => admin_url( 'admin-ajax.php' ),
			'_wp_nonce' => wp_create_nonce( Login_Ajax::class ),
		);
		$ajax_data_json = wp_json_encode( $ajax_data, JSON_PRETTY_PRINT );

		$script = <<<EOD
var bh_wp_autologin_urls = $ajax_data_json;
EOD;

		wp_add_inline_script(
			$handle,
			$script,
			'before'
		);
	}
}
