<?php
/**
 * The wp-admin settings page.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/admin
 */

namespace BH_WP_Autologin_URLs\admin;

use BH_WP_Autologin_URLs\includes\Settings_Interface;
use BH_WP_Autologin_URLs\admin\partials\Settings_Section_Element_Abstract;
use BH_WP_Autologin_URLs\admin\partials\Admin_Enable;
use BH_WP_Autologin_URLs\admin\partials\Expiry_Age;
use BH_WP_Autologin_URLs\admin\partials\Regex_Subject_Filters;
use BH_WP_Autologin_URLs\WPPB\WPPB_Object;


/**
 * The setting page of the plugin.
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/admin
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Settings_Page extends WPPB_Object {

	/**
	 * The settings, to pass to the individual fields for populating.
	 *
	 * @var Settings_Interface $settings The previously saved settings for the plugin.
	 */
	private $settings;

	/**
	 * Settings_Page constructor.
	 *
	 * @param string             $plugin_name The plugin name.
	 * @param string             $version The plugin version.
	 * @param Settings_Interface $settings The previously saved settings for the plugin.
	 */
	public function __construct( $plugin_name, $version, $settings ) {
		parent::__construct( $plugin_name, $version );

		$this->settings = $settings;
	}

	/**
	 * Add the Autologin URLs settings menu-item/page as a submenu-item of the Settings menu.
	 *
	 * /wp-admin/options-general.php?page=bh-wp-autologin-urls
	 *
	 * @hooked admin_menu
	 */
	public function add_settings_page() {

		add_options_page(
			'Autologin URLs',
			'Autologin URLs',
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Registered above, called by WordPress to display the admin settings page.
	 */
	public function display_plugin_admin_page() {

		$example_url = site_url() . '/?autologin=' . get_current_user_id() . '~Yxu1UQG8IwJO';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/admin-display.php';
	}

	/**
	 * Register the one settings section with WordPress.
	 *
	 * @hooked admin_init
	 */
	public function setup_sections() {

		$settings_page_slug_name = $this->plugin_name;

		add_settings_section(
			'default',
			'Settings',
			null,
			$settings_page_slug_name
		);
	}

	/**
	 * Field Configuration, each item in this array is one field/setting we want to capture.
	 *
	 * @hooked admin_init
	 *
	 * @see https://github.com/reside-eng/wordpress-custom-plugin/blob/master/admin/class-wordpress-custom-plugin-admin.php
	 *
	 * @since    1.0.0
	 */
	public function setup_fields() {

		$settings_page_slug_name = $this->plugin_name;

		$fields = array();

		$fields[] = new Expiry_Age( $this->plugin_name, $this->version, $settings_page_slug_name, $this->settings );
		$fields[] = new Admin_Enable( $this->plugin_name, $this->version, $settings_page_slug_name, $this->settings );
		$fields[] = new Regex_Subject_Filters( $this->plugin_name, $this->version, $settings_page_slug_name, $this->settings );

		foreach ( $fields as $field ) {

			$field->add_settings_field();

			$field->register_setting();

		}
	}
}
