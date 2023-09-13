<?php
/**
 * An abstract settings element for extending.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Fields;

/**
 * Code common across setting elements.
 *
 * @see https://github.com/reside-eng/wordpress-custom-plugin
 * @see register_setting()
 * @see add_settings_field()
 *
 * Class Settings_Section_Element
 */
abstract class Settings_Section_Element_Abstract {

	/**
	 * The unique setting id, as used in the wp_options table.
	 *
	 * @var string The id of the setting in the database.
	 */
	protected $id;

	/**
	 * The setting's existing value. Used in HTML value="".
	 *
	 * @var mixed The previously saved value.
	 */
	protected $value;

	/**
	 * The name of the setting as it is printed in the left column of the settings table.
	 *
	 * @var string $title The title of the setting.
	 */
	protected $title;

	/**
	 * The slug of the settings page this setting is shown on.
	 *
	 * @var string $page The settings page page slug.
	 */
	protected $page;

	/**
	 * The section name as used with add_settings_section().
	 *
	 * @var string $section The section/tab the setting is displayed in.
	 */
	protected $section = 'default';

	/**
	 * The data array the WordPress Settings API passes to print_field_callback().
	 *
	 * @var array{helper:string, supplemental:string, default:mixed, placeholder:string} Array of data available to print_field_callback()
	 */
	protected $add_settings_field_args;

	/**
	 * The options array used when registering the setting.
	 *
	 * @var array{type:string, description:string, sanitize_callback:callable, show_in_rest:bool|array<string,mixed>, default:mixed} $register_setting_args Data used to describe the setting when registered.
	 */
	protected array $register_setting_args = array();

	/**
	 * Settings_Section_Element constructor.
	 *
	 * @param string $settings_page_slug_name The page slug the settings section is on.
	 * @param string $section The name of the section the settings are displayed in.
	 */
	public function __construct( $settings_page_slug_name, $section = 'default' ) {

		$this->page    = $settings_page_slug_name;
		$this->section = $section;

		$this->register_setting_args['description']       = '';
		$this->register_setting_args['sanitize_callback'] = array( $this, 'sanitize_callback' );
		$this->register_setting_args['show_in_rest']      = false;
	}

	/**
	 * Add the configured settings field to the page and section.
	 */
	public function add_settings_field(): void {

		add_settings_field(
			$this->id,
			$this->title,
			array( $this, 'print_field_callback' ),
			$this->page,
			$this->section,
			$this->add_settings_field_args
		);
	}

	/**
	 * Register the setting with WordPress so it is whitelisted for saving.
	 */
	public function register_setting(): void {

		register_setting(
			$this->page,
			$this->id,
			$this->register_setting_args
		);
	}

	/**
	 * Echo the HTML for configuring this setting.
	 *
	 * @param array{placeholder:string, helper:string, supplemental:string, default:string} $arguments The field data as registered with add_settings_field().
	 */
	abstract public function print_field_callback( $arguments ): void;

	/**
	 * Carry out any sanitization and pre-processing of the POSTed data before it is saved in the database.
	 *
	 * This gets run twice on the first request it is run – where false is the old value, WordPress then runs `add_option()`.
	 *
	 * @param mixed $value The value entered by the user as POSTed to WordPress.
	 *
	 * @return mixed
	 */
	abstract public function sanitize_callback( $value );
}
