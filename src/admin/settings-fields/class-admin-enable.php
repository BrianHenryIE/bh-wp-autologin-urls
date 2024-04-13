<?php
/**
 * This settings field is a checkbox to signify if autologin codes should be added to emails sent to admins.
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
 * Class Admin_Enable
 */
class Admin_Enable extends Settings_Section_Element_Abstract {

	/**
	 * Admin_Enable constructor.
	 *
	 * @param string             $settings_page_slug_name The slug of the page this setting is being displayed on.
	 * @param Settings_Interface $settings The existing settings saved in the database.
	 */
	public function __construct( $settings_page_slug_name, $settings ) {

		parent::__construct( $settings_page_slug_name );

		$this->value = $settings->get_add_autologin_for_admins_is_enabled() ? 'admin_is_enabled' : 'admin_is_not_enabled';

		$this->id    = Settings::ADMIN_ENABLED;
		$this->title = __( 'Add to admin emails?', 'bh-wp-autologin-urls' );
		$this->page  = $settings_page_slug_name;

		$this->add_settings_field_args['helper']       = __( 'When enabled, emails to administrators <i>will</i> contain autologin URLs.', 'bh-wp-autologin-urls' );
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
		$checkbox_value = 'admin_is_enabled';
		$is_checked     = 'admin_is_enabled' === $value ? 'checked ' : '';
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

		if ( 'admin_is_enabled' === $value ) {
			return 'admin_is_enabled';
		} elseif ( null === $value ) {
			return 'admin_is_not_enabled';
		} else {
			return $this->value;
		}
	}
}
