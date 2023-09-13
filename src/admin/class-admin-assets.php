<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;

/**
 * The admin area functionality of the plugin.
 */
class Admin_Assets {

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

		wp_enqueue_style( $this->settings->get_plugin_slug(), plugin_dir_url( $this->settings->get_plugin_basename() ) . 'assets/bh-wp-autologin-urls-admin.css', array(), $this->settings->get_plugin_version(), 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @hooked admin_enqueue_scripts.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {

		wp_enqueue_script( $this->settings->get_plugin_slug(), plugin_dir_url( $this->settings->get_plugin_basename() ) . 'assets/bh-wp-autologin-urls-admin.js', array( 'jquery' ), $this->settings->get_plugin_version(), true );
	}
}
