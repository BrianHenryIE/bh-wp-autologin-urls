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
 * @subpackage bh-wp-autologin-urls/includes
 */

namespace BH_WP_Autologin_URLs\includes;

/**
 * Class Settings
 */
class Settings implements Settings_Interface {

	const EXPIRY_TIME_IN_SECONDS          = 'bh_wp_autologin_urls_seconds_until_expiry';
	const ADMIN_ENABLED                   = 'bh_wp_autologin_urls_is_admin_enabled';
	const SUBJECT_FILTER_REGEX_DICTIONARY = 'bh_wp_autologin_urls_subject_filter_regex_dictionary';

	/**
	 * The expiry time as set on the settings page.
	 *
	 * @var int The chosen expiry time in seconds.
	 */
	private $expiry_time;

	/**
	 * Indicates if autologin codes should be added to emails to admins.
	 *
	 * @var bool Should add autologin codes for admins.
	 */
	private $autologin_for_admins_is_enabled = false;

	/**
	 * A dictionary of regex:notes, where the regex is applied to the email subject to
	 * disable adding the autologin code and the notes are for the admin UI to remind the
	 * user what the regex means.
	 *
	 * @var string[] array
	 */
	private $disallowed_subjects_regex_dictionary = array();

	/**
	 * Queries WordPress options table for settings, provides default values and remedial validation.
	 *
	 * Settings constructor.
	 */
	public function __construct() {

		$expiry_time       = get_option( self::EXPIRY_TIME_IN_SECONDS, 604800 );
		$this->expiry_time = is_int( intval( $expiry_time ) ) && $expiry_time > 0 ? intval( $expiry_time ) : 604800;

		$autologin_for_admins_is_enabled       = get_option( self::ADMIN_ENABLED, 'admin_is_not_enabled' );
		$this->autologin_for_admins_is_enabled = 'admin_is_enabled' === $autologin_for_admins_is_enabled;

		$default_subject_regexes = array(
			'/^.*Login Details$/'                   => '[Example Site] Login Details',
			'/^.*Your new password$/'               => 'Example Site Your new password',
			'/^Password Reset Request.*$/'          => 'Password Reset Request for Example Site',
			'/^Please complete your registration$/' => 'Please complete your registration',
		);

		$disallowed_subject_regex_dictionary        = get_option( self::SUBJECT_FILTER_REGEX_DICTIONARY, $default_subject_regexes );
		$this->disallowed_subjects_regex_dictionary = is_array( $disallowed_subject_regex_dictionary ) ? $disallowed_subject_regex_dictionary : $default_subject_regexes;
	}

	/**
	 * The expiry time as used when creating the transient that stores the password hash.
	 *
	 * @return int The expiry time in seconds, as set on the settings page.
	 */
	public function get_expiry_age() {
		return $this->expiry_time;
	}

	/**
	 * The configuration setting as defined in the WordPress admin UI, saying if emails to admins should get autologin urls.
	 *
	 * @return bool Should the autologin code be added to urls in emails sent to admins?
	 */
	public function get_add_autologin_for_admins_is_enabled() {
		return $this->autologin_for_admins_is_enabled;
	}

	/**
	 * A list of regexes for email subjects that should not have autologin codes added.
	 *
	 * @return string[]
	 */
	public function get_disallowed_subjects_regex_array() {
		return array_keys( $this->disallowed_subjects_regex_dictionary );
	}

	/**
	 * A dictionary of regexes and email subjects that should not have autologin codes added.
	 *
	 * @return string[]
	 */
	public function get_disallowed_subjects_regex_dictionary() {
		return $this->disallowed_subjects_regex_dictionary;
	}

}
