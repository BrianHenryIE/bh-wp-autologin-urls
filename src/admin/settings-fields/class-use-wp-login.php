<?php
/**
 * This settings field is a checkbox to signify if autologin URLs should be redirect URLs via wp-login.php.
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
class Use_WP_Login extends Checkbox_Setting_Element_Abstract {

	protected function get_is_checked_value(): string {
		return 'use_wp_login_is_enabled';
	}
	protected function get_is_not_checked_value(): string {
		return 'use_wp_login_is_not_enabled';
	}

	/**
	 * Admin_Enable constructor.
	 *
	 * @param string             $settings_page_slug_name The slug of the page this setting is being displayed on.
	 * @param Settings_Interface $settings The existing settings saved in the database.
	 */
	public function __construct( string $settings_page_slug_name, Settings_Interface $settings ) {

		parent::__construct( $settings_page_slug_name );

		$this->value = $settings->get_should_use_wp_login() ? 'use_wp_login_is_enabled' : 'use_wp_login_is_not_enabled';

		$this->id    = Settings::SHOULD_USE_WP_LOGIN;
		$this->title = __( 'Use wp-login.php?', 'bh-wp-autologin-urls' );
		$this->page  = $settings_page_slug_name;

		$this->register_setting_args['type']    = 'string';
		$this->register_setting_args['default'] = 'use_wp_login_is_not_enabled';

		$this->add_settings_field_args['helper']       = __( 'If users are not being logged in or if they need to refresh the page after landing, this will perform the login on wp-login.php before redirecting to the correct URL.', 'bh-wp-autologin-urls' );
		$this->add_settings_field_args['supplemental'] = __( 'default: false', 'bh-wp-autologin-urls' );
	}
}
