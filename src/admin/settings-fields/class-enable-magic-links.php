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
class Enable_Magic_Links extends Settings_Section_Element_Abstract {

	/**
	 * Admin_Enable constructor.
	 *
	 * @param string             $settings_page The slug of the page this setting is being displayed on.
	 * @param Settings_Interface $settings The existing settings saved in the database.
	 */
	public function __construct( string $settings_page, Settings_Interface $settings ) {

		parent::__construct( $settings_page );

		$this->value = $settings->is_magic_link_enabled() ? 'magic_links_is_enabled' : 'magic_links_is_not_enabled';

		$this->id    = Settings::MAGIC_LINK_ENABLED;
		$this->title = __( 'Enable magic links?', 'bh-wp-autologin-urls' );
		$this->page  = $settings_page;

		$this->register_setting_args['type']    = 'string';
		$this->register_setting_args['default'] = 'magic_links_is_not_enabled';

		$this->add_settings_field_args['helper'] = __( 'Add a button beside login buttons allowing users to send themselves an email with an instant login link.', 'bh-wp-autologin-urls' );

		$login_screens_links = '<a href="' . wp_login_url() . '">' . str_replace( trailingslashit( site_url() ), '', wp_login_url() ) . '</a>';
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$login_screens_links .= ', <a href="' . wc_get_page_permalink( 'myaccount' ) . '">' . str_replace( trailingslashit( site_url() ), '', wc_get_page_permalink( 'myaccount' ) ) . '</a>';

			// If "Allow customers to log into an existing account during checkout" is checked.
			if ( 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
				$login_screens_links .= ', <a href="' . wc_get_page_permalink( 'checkout' ) . '">' . str_replace( trailingslashit( site_url() ), '', wc_get_page_permalink( 'checkout' ) ) . '</a>';
			}
		}

		/* translators: hyperlinks to login screens */
		$this->add_settings_field_args['supplemental'] = __( 'default: false', 'bh-wp-autologin-urls' ) . '<br/>' . sprintf( __( 'When enabling magic links, be sure to check the appearance of the button on your login screens (%s) and make appropriate CSS changes if necessary.', 'bh-wp-autologin-urls' ), $login_screens_links );
	}

	/**
	 * Prints the checkbox as displayed in the right-hand column of the settings table.
	 *
	 * @param array{helper:string, supplemental:string} $arguments The data registered with add_settings_field().
	 */
	public function print_field_callback( $arguments ): void {

		$value = $this->value;

		// This is what is POSTed when the checkbox is ticked.
		$checkbox_value = 'magic_links_is_enabled';
		$is_checked     = 'magic_links_is_enabled' === $value ? 'checked ' : '';
		$label          = $arguments['helper'];

		printf( '<fieldset><label for="%1$s"><input id="%1$s" name="%1$s" type="checkbox" value="%2$s" %3$s />%4$s</label></fieldset>', esc_attr( $this->id ), esc_attr( $checkbox_value ), esc_attr( $is_checked ), wp_kses( $label, array( 'i' => array() ) ) );

		$allowed_html = array(
			'br' => array(),
			'a'  => array(
				'href'   => array(),
				'target' => array(),
			),
		);
		printf( '<p class="description">%s</p>', wp_kses( $arguments['supplemental'], $allowed_html ) );
	}

	/**
	 * If an unexpected value is POSTed, don't make any change to what's in the database.
	 *
	 * @param ?string $value The data posted from the HTML form.
	 *
	 * @return string The value to save in the database.
	 */
	public function sanitize_callback( $value ) {

		if ( 'magic_links_is_enabled' === $value ) {
			return 'magic_links_is_enabled';
		} elseif ( null === $value ) {
			return 'magic_links_is_not_enabled';
		} else {
			return $this->value;
		}
	}
}
