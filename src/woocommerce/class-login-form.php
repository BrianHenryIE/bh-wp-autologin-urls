<?php
/**
 * Add "send magic login link" button to the WooCommerce login form.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\WooCommerce;

use BrianHenryIE\WP_Autologin_URLs\Login\Login_Ajax;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;

/**
 * Enqueue the WooCommerce login form specific JavaScript.
 */
class Login_Form {

	/**
	 * Needed for plugin basename and version for JS URL and versioning.
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
	 * Register the JavaScript when the WooCommerce login form is printed.
	 *
	 * @hooked woocommerce_before_customer_login_form.
	 * @hooked woocommerce_before_checkout_form
	 *
	 * @since    1.7.0
	 */
	public function enqueue_script(): void {

		if ( ! $this->settings->is_magic_link_enabled() ) {
			return;
		}

		$url = plugin_dir_url( $this->settings->get_plugin_basename() ) . 'assets/bh-wp-autologin-urls-woocommerce-login.js';

		$handle = 'bh-wp-autologin-urls-woocommerce-login-form';
		wp_enqueue_script( $handle, $url, array( 'jquery' ), $this->settings->get_plugin_version(), true );

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
