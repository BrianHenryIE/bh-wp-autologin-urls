<?php
/**
 * This settings field is a text field to save the Klaviyo private key
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin\Partials;

use BrianHenryIE\WP_Autologin_URLs\API\Settings_Interface;

class Klaviyo_Private_Key extends Settings_Section_Element_Abstract {

	/**
	 * Constructor.
	 *
	 * @param string             $settings_page The slug of the page this setting is being displayed on.
	 * @param Settings_Interface $settings The existing settings saved in the database.
	 */
	public function __construct( $settings_page, $settings ) {

		parent::__construct( $settings_page );

		$this->value = $settings->get_klaviyo_private_api_key();

		$this->id    = 'bh_wp_autologin_urls_klaviyo_private_key';
		$this->title = 'Klaviyo Private Key:';
		$this->page  = $settings_page;

		$this->add_settings_field_args['helper']      = sprintf( __( 'Find your API keys at  <a href="%s" target="_blank">klaviyo.com/account#api-keys-tab</a>.', 'bh-wp-autologin-urls' ), 'https://www.klaviyo.com/account#api-keys-tab' );
		$this->add_settings_field_args['placeholder'] = '';
	}

	/**
	 * The function used by WordPress Settings API to output the field.
	 *
	 * @param array{placeholder:string, helper:string, supplemental:string} $arguments Settings passed from WordPress do_settings_fields() function.
	 */
	public function print_field_callback( $arguments ): void {

		$value = $this->value;

		$arguments['placeholder'] = '';

		printf( '<input name="%1$s" id="%1$s" type="text" placeholder="%2$s" value="%3$s" />', esc_attr( $this->id ), esc_attr( $arguments['placeholder'] ), esc_attr( $value ) );

		printf( '<span class="helper">%s</span>', $arguments['helper'] );

		// printf( '<p class="description">%s</p>', esc_html( $arguments['supplemental'] ) );
	}

	/**
	 * TODO: What would show it is invalid?
	 *
	 * @param string $value The value POSTed by the Settings API.
	 *
	 * @return string
	 */
	public function sanitize_callback( $value ) {

		return $value;
	}

}
