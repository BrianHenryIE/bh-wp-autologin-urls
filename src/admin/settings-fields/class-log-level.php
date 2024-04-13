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
use Psr\Log\LogLevel;

/**
 * Class
 */
class Log_Level extends Settings_Section_Element_Abstract {

	/**
	 * Array of PSR log levels we might log to.
	 *
	 * @var string[]
	 */
	protected $log_levels;

	/**
	 * Log_Level constructor.
	 *
	 * @param string             $settings_page_slug_name The slug of the page this setting is being displayed on.
	 * @param Settings_Interface $settings The existing settings saved in the database.
	 */
	public function __construct( $settings_page_slug_name, $settings ) {

		parent::__construct( $settings_page_slug_name );

		$this->value = $settings->get_log_level();

		$this->id    = Settings::LOG_LEVEL;
		$this->title = __( 'Log level', 'bh-wp-autologin-urls' );
		$this->page  = $settings_page_slug_name;

		$this->add_settings_field_args['helper']       = __( 'Set to Debug to diagnose problems, Info to see times this plugin is logging users in. Warning: debug logs will contain autologin codes.', 'bh-wp-autologin-urls' );
		$this->add_settings_field_args['supplemental'] = __( 'default: Notice', 'bh-wp-autologin-urls' );

		// TODO: Consider removing the ones that are never used in the plugin.
		$this->log_levels = array( 'none', LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG );
	}

	/**
	 * Prints the checkbox as displayed in the right-hand column of the settings table.
	 *
	 * @param array{helper:string, supplemental:string} $arguments The data registered with add_settings_field().
	 */
	public function print_field_callback( $arguments ): void {

		$label = $arguments['helper'];

		printf( '<fieldset><label for="%1$s"><select id="%1$s" name="%1$s" />', esc_attr( $this->id ) );

		foreach ( $this->log_levels as $level ) {

			echo '<option value="' . esc_attr( $level ) . '"' . ( $this->value === $level ? ' selected' : '' ) . '>' . esc_html( ucfirst( $level ) ) . '</option>';
		}

		echo '</select>';

		printf( '%1$s</label></fieldset>', wp_kses( $label, array( 'i' => array() ) ) );

		$logs_url = admin_url( 'admin.php?page=bh-wp-autologin-urls-logs' );

		printf( '<p class="description">%s – %s</p>', esc_html( $arguments['supplemental'] ), sprintf( '<a href="%s">View Logs</a>', esc_url( $logs_url ) ) );
	}

	/**
	 * Check it's a valid log PSR log level.
	 *
	 * @param string $value The data posted from the HTML form.
	 *
	 * @return string The value to save in the database.
	 */
	public function sanitize_callback( $value ) {

		if ( is_string( $value ) ) {

			if ( in_array( $value, $this->log_levels, true ) ) {
				return $value;
			}
		}

		// Return the previous value.
		return $this->value;
	}
}
