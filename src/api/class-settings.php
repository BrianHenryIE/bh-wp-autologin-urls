<?php
/**
 * Define the settings class.
 *
 * Includes constants for wp_option names and on construction retrieves data for wp_mail filter options.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\API;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\WP_Logger\Logger;
use Psr\Log\LogLevel;

/**
 * Plain old typed object wrapping WordPress wp_options.
 */
class Settings implements Settings_Interface, Logger_Settings_Interface {

	const ADMIN_ENABLED                   = 'bh_wp_autologin_urls_is_admin_enabled';
	const SUBJECT_FILTER_REGEX_DICTIONARY = 'bh_wp_autologin_urls_subject_filter_regex_dictionary';
	const LOG_LEVEL                       = 'bh_wp_autologin_urls_log_level';
	const SHOULD_USE_WP_LOGIN             = 'bh_wp_autologin_urls_should_use_wp_login';
	const MAGIC_LINK_ENABLED              = 'bh_wp_autologin_urls_magic_link_enabled';

	const DEFAULT_LOG_LEVEL = LogLevel::NOTICE;

	/**
	 * A dictionary of regex:notes, where the regex is applied to the email subject to
	 * disable adding the autologin code and the notes are for the admin UI to remind the
	 * user what the regex means.
	 *
	 * @var string[] array
	 */
	protected $disallowed_subjects_regex_dictionary = array();

	/**
	 * Queries WordPress options table for settings, provides default values and remedial validation.
	 *
	 * Settings constructor.
	 */
	public function __construct() {

		$this->disallowed_subjects_regex_dictionary = array(
			'/^.*Login Details$/'                   => '[Example Site] Login Details',
			'/^.*Your new password$/'               => 'Example Site Your new password',
			'/^Password Reset Request.*$/'          => 'Password Reset Request for Example Site',
			'/^Please complete your registration$/' => 'Please complete your registration',
		);
	}

	/**
	 * The expiry time as used when creating the transient that stores the password hash.
	 *
	 * @return int The expiry time in seconds, as set on the settings page.
	 */
	public function get_expiry_age(): int {
		$expiry_time = get_option( 'bh_wp_autologin_urls_seconds_until_expiry', 604800 );
		return intval( $expiry_time ) > 0 ? intval( $expiry_time ) : 604800;
	}

	/**
	 * The configuration setting as defined in the WordPress admin UI, saying if emails to admins should get autologin urls.
	 *
	 * @return bool Should the autologin code be added to urls in emails sent to admins?
	 */
	public function get_add_autologin_for_admins_is_enabled(): bool {

		$autologin_for_admins_is_enabled = get_option( self::ADMIN_ENABLED, 'admin_is_not_enabled' );
		return 'admin_is_enabled' === $autologin_for_admins_is_enabled;
	}

	/**
	 * A list of regexes for email subjects that should not have autologin codes added.
	 *
	 * @return string[]
	 */
	public function get_disallowed_subjects_regex_array(): array {
		return array_keys( $this->get_disallowed_subjects_regex_dictionary() );
	}

	/**
	 * A dictionary of regexes and email subjects that should not have autologin codes added.
	 *
	 * @return string[]
	 */
	public function get_disallowed_subjects_regex_dictionary(): array {

		$disallowed_subjects_regex_dictionary = get_option( self::SUBJECT_FILTER_REGEX_DICTIONARY, $this->disallowed_subjects_regex_dictionary );

		$disallowed_subjects_regex_dictionary = is_array( $disallowed_subjects_regex_dictionary ) ? $disallowed_subjects_regex_dictionary : $this->disallowed_subjects_regex_dictionary;

		return $disallowed_subjects_regex_dictionary;
	}

	/**
	 * Change links to redirect form wp-login.php rather than going directly to the link.
	 *
	 * @return bool
	 */
	public function get_should_use_wp_login(): bool {
		return get_option( self::SHOULD_USE_WP_LOGIN, false ) === 'use_wp_login_is_enabled';
	}

	/**
	 * The PSR log level to print, defaults to Info.
	 *
	 * @return string
	 */
	public function get_log_level(): string {

		return get_option( self::LOG_LEVEL, self::DEFAULT_LOG_LEVEL );
	}

	/**
	 * The plugin slug as required by the logger.
	 *
	 * @used-by Logger
	 * @used-by Admin
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return 'bh-wp-autologin-urls';
	}

	/**
	 * The plugin version, used for asset caching.
	 */
	public function get_plugin_version(): string {
		return defined( 'BH_WP_AUTOLOGIN_URLS_VERSION' )
			? constant( 'BH_WP_AUTOLOGIN_URLS_VERSION' )
			: '2.4.2';
	}

	/**
	 * Plugin name for use by the logger in friendly messages printed to WordPress admin UI.
	 *
	 * @return string
	 * @see Logger
	 */
	public function get_plugin_name(): string {
		return 'Autologin URLs';
	}

	/**
	 * The plugin basename is used by the logger to add the plugins page action link.
	 * (and maybe for PHP errors)
	 *
	 * @return string
	 * @see Logger
	 */
	public function get_plugin_basename(): string {
		return defined( 'BH_WP_AUTOLOGIN_URLS_BASENAME' ) ? BH_WP_AUTOLOGIN_URLS_BASENAME : 'bh-wp-autologin-urls/bh-wp-autologin-urls.php';
	}

	/**
	 * Return the API key to query the Klaviyo API for user details, if entered.
	 */
	public function get_klaviyo_private_api_key(): ?string {
		return get_option( 'bh_wp_autologin_urls_klaviyo_private_key', null );
	}

	/**
	 * Enable/disable the magic link feature.
	 */
	public function is_magic_link_enabled(): bool {
		return 'magic_links_is_enabled' === get_option( self::MAGIC_LINK_ENABLED, 'magic_links_is_not_enabled' );
	}
}
