<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/admin
 */

namespace BH_WP_Autologin_URLs\admin;

use BH_WP_Autologin_URLs\WPPB\WPPB_Object;

/**
 * The admin area functionality of the plugin.
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/admin
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Admin extends WPPB_Object {

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @hooked admin_enqueue_scripts
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bh-wp-autologin-urls-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @hooked admin_enqueue_scripts.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bh-wp-autologin-urls-admin.js', array( 'jquery' ), $this->version, true );
	}

}
