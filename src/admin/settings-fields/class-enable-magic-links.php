<?php
/**
 * This settings field is a checkbox to enable adding "send magic link" buttons on login screens.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Fields;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\Settings;

/**
 * Class
 */
class Enable_Magic_Links extends Checkbox_Setting_Element_Abstract {

	/**
	 * This is what is POSTed when the checkbox is ticked.
	 */
	protected function get_is_checked_value(): string {
		return 'magic_links_is_enabled';
	}
	protected function get_is_not_checked_value(): string {
		return 'magic_links_is_not_enabled';
	}

	/**
	 * Admin_Enable constructor.
	 *
	 * @param string             $settings_page_slug_name The slug of the page this setting is being displayed on.
	 * @param Settings_Interface $settings The existing settings saved in the database.
	 */
	public function __construct( string $settings_page_slug_name, Settings_Interface $settings ) {

		parent::__construct( $settings_page_slug_name );

		$this->value = $settings->is_magic_link_enabled() ? 'magic_links_is_enabled' : 'magic_links_is_not_enabled';

		$this->id    = Settings::MAGIC_LINK_ENABLED;
		$this->title = __( 'Enable magic links?', 'bh-wp-autologin-urls' );

		$this->register_setting_args['type']    = 'string';
		$this->register_setting_args['default'] = 'magic_links_is_not_enabled';

		$this->add_settings_field_args['helper'] = __( 'Add a button beside login buttons allowing users to send themselves an email with an instant login link.', 'bh-wp-autologin-urls' );

		$login_screens_links = '<a href="' . wp_login_url() . '">' . str_replace( trailingslashit( site_url() ), '', wp_login_url() ) . '</a>';
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$login_screens_links .= ', <a href="' . wc_get_page_permalink( 'myaccount' ) . '">' . str_replace( trailingslashit( site_url() ), '', wc_get_page_permalink( 'myaccount' ) ) . '</a>';

			// If "Allow customers to log into an existing account during checkout" is checked.
			if ( 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
				$login_screens_links .= ', <a href="' . wc_get_page_permalink( 'checkout' ) . '">' . str_replace( trailingslashit( site_url() ), '', wc_get_page_permalink( 'checkout' ) ) . '</a>';
			}
		}

		/* translators: hyperlinks to login screens */
		$this->add_settings_field_args['supplemental'] = __( 'default: false', 'bh-wp-autologin-urls' ) . '<br/>' . sprintf( __( 'When enabling magic links, be sure to check the appearance of the button on your login screens (%s) and make appropriate CSS changes if necessary.', 'bh-wp-autologin-urls' ), $login_screens_links );
	}
}
