<?php

namespace BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Fields;

abstract class Checkbox_Setting_Element_Abstract extends Settings_Section_Element_Abstract {

	public function __construct( $settings_page_slugname_slug_name, $section = 'default' ) {
		parent::__construct( $settings_page_slugname_slug_name, $section );
	}

	abstract protected function get_is_checked_value(): string;

	abstract protected function get_is_not_checked_value(): string;

	/**
	 * Prints the checkbox as displayed in the right-hand column of the settings table.
	 *
	 * @param array{placeholder:string, helper:string, supplemental:string, default:string} $arguments The field data as registered with add_settings_field().
	 */
	public function print_field_callback( $arguments ): void {

		$is_checked = $this->get_is_checked_value() === $this->value ? 'checked ' : '';
		$label      = $arguments['helper'];

		printf(
			'<fieldset><label for="%1$s"><input id="%1$s" name="%1$s" type="checkbox" value="%2$s" %3$s />%4$s</label></fieldset>',
			esc_attr( $this->id ),
			esc_attr( $this->get_is_checked_value() ),
			esc_attr( $is_checked ),
			wp_kses( $label, array( 'i' => array() ) )
		);

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

		if ( $this->get_is_checked_value() === $value ) {
			return $value;
		} elseif ( null === $value ) {
			return $this->get_is_not_checked_value();
		} else {
			return $this->value;
		}
	}
}
