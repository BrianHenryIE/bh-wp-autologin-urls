<?php
/**
 * The wp-admin settings page.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin;

use BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Fields\Enable_Magic_Links;
use BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Fields\Klaviyo_Private_Key;
use BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Fields\Log_Level;
use BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Fields\Use_WP_Login;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Fields\Settings_Section_Element_Abstract;
use BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Fields\Admin_Enable;
use BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Fields\Expiry_Age;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * The setting page of the plugin.
 */
class Settings_Page {
	use LoggerAwareTrait;

	/**
	 * The settings, to pass to the individual fields for populating.
	 *
	 * @var Settings_Interface $settings The previously saved settings for the plugin.
	 */
	protected Settings_Interface $settings;

	/**
	 * Settings_Page constructor.
	 *
	 * @param Settings_Interface $settings The previously saved settings for the plugin.
	 * @param LoggerInterface    $logger A PSR logger.
	 */
	public function __construct( Settings_Interface $settings, LoggerInterface $logger ) {
		$this->settings = $settings;
		$this->setLogger( $logger );
	}

	/**
	 * Add the Autologin URLs settings menu-item/page as a submenu-item of the Settings menu.
	 *
	 * /wp-admin/options-general.php?page=bh-wp-autologin-urls
	 *
	 * @hooked admin_menu
	 */
	public function add_settings_page(): void {

		add_options_page(
			'Autologin URLs',
			'Autologin URLs',
			'manage_options',
			$this->settings->get_plugin_slug(),
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Registered above, called by WordPress to display the admin settings page.
	 */
	public function display_plugin_admin_page(): void {

		$logger      = $this->logger;
		$example_url = site_url() . '/?autologin=' . get_current_user_id() . '~Yxu1UQG8IwJO';

		$template = 'admin/settings-page.php';

		$template_admin_settings_page = WP_PLUGIN_DIR . '/' . plugin_dir_path( $this->settings->get_plugin_basename() ) . 'templates/' . $template;

		// Check the child theme for template overrides.
		if ( file_exists( get_stylesheet_directory() . $template ) ) {
			$template_admin_settings_page = get_stylesheet_directory() . $template;
		} elseif ( file_exists( get_stylesheet_directory() . 'templates/' . $template ) ) {
			$template_admin_settings_page = get_stylesheet_directory() . 'templates/' . $template;
		}

		/**
		 * Allow overriding the admin settings template.
		 */
		$filtered_template_admin_settings_page = apply_filters( 'bh_wp_autologin_urls_admin_settings_page_template', $template_admin_settings_page );

		if ( file_exists( $filtered_template_admin_settings_page ) ) {
			include $filtered_template_admin_settings_page;
		} else {
			include $template_admin_settings_page;
		}
	}

	/**
	 * Register the one settings section with WordPress.
	 *
	 * @hooked admin_init
	 */
	public function setup_sections(): void {

		$settings_page_slug_name = $this->settings->get_plugin_slug();

		add_settings_section(
			'default',
			'Settings',
			function () {},
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
	public function setup_fields(): void {

		$settings_page_slug_name = $this->settings->get_plugin_slug();

		/**
		 * Other plugins (WooCommerce, WP Affiliate) handle this by using an array here, where each array element has
		 * a 'type' which is instantiated, then the defaults are overwritten from other properties in the array.
		 *
		 * @var Settings_Section_Element_Abstract[] $fields Each element to be displayed.
		 */
		$fields = array();

		$fields[] = new Enable_Magic_Links( $settings_page_slug_name, $this->settings );
		$fields[] = new Expiry_Age( $settings_page_slug_name, $this->settings );
		$fields[] = new Admin_Enable( $settings_page_slug_name, $this->settings );
		$fields[] = new Use_WP_Login( $settings_page_slug_name, $this->settings );
		$fields[] = new Klaviyo_Private_Key( $settings_page_slug_name, $this->settings );
		$fields[] = new Log_Level( $settings_page_slug_name, $this->settings );

		foreach ( $fields as $field ) {

			$field->add_settings_field();

			$field->register_setting();

		}
	}
}
