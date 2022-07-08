<?php
/**
 * This settings field is a checkbox to signify if autologin URLs should be redirect URLs via wp-login.php.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/admin/partials
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin\Partials;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\Settings;

/**
 * Class
 */
class Use_WP_Login extends Settings_Section_Element_Abstract {

	/**
	 * Admin_Enable constructor.
	 *
	 * @param string             $settings_page The slug of the page this setting is being displayed on.
	 * @param Settings_Interface $settings The existing settings saved in the database.
	 */
	public function __construct( string $settings_page, Settings_Interface $settings ) {

		parent::__construct( $settings_page );

		$this->value = $settings->get_should_use_wp_login() ? 'use_wp_login_is_enabled' : 'use_wp_login_is_not_enabled';

		$this->id    = Settings::SHOULD_USE_WP_LOGIN;
		$this->title = __( 'Use wp-login.php?', 'bh-wp-autologin-urls' );
		$this->page  = $settings_page;

		$this->register_setting_args['type']    = 'string';
		$this->register_setting_args['default'] = 'use_wp_login_is_not_enabled';

		$this->add_settings_field_args['helper']       = __( 'If users are not being logged in or if they need to refresh the page after landing, this will perform the login on wp-login.php before redirecting to the correct URL.', 'bh-wp-autologin-urls' );
		$this->add_settings_field_args['supplemental'] = __( 'default: false', 'bh-wp-autologin-urls' );
	}

	/**
	 * Prints the checkbox as displayed in the right-hand column of the settings table.
	 *
	 * @param array{helper:string, supplemental:string} $arguments The data registered with add_settings_field().
	 */
	public function print_field_callback( $arguments ): void {

		$value = $this->value;

		// This is what is POSTed when the checkbox is ticked.
		$checkbox_value = 'use_wp_login_is_enabled';
		$is_checked     = 'use_wp_login_is_enabled' === $value ? 'checked ' : '';
		$label          = $arguments['helper'];

		printf( '<fieldset><label for="%1$s"><input id="%1$s" name="%1$s" type="checkbox" value="%2$s" %3$s />%4$s</label></fieldset>', esc_attr( $this->id ), esc_attr( $checkbox_value ), esc_attr( $is_checked ), wp_kses( $label, array( 'i' => array() ) ) );

		printf( '<p class="description">%s</p>', esc_html( $arguments['supplemental'] ) );
	}

	/**
	 * If an unexpected value is POSTed, don't make any change to what's in the database.
	 *
	 * @param ?string $value The data posted from the HTML form.
	 *
	 * @return string The value to save in the database.
	 */
	public function sanitize_callback( $value ) {

		if ( 'use_wp_login_is_enabled' === $value ) {
			return 'use_wp_login_is_enabled';
		} elseif ( null === $value ) {
			return 'use_wp_login_is_not_enabled';
		} else {
			return $this->value;
		}
	}

}
