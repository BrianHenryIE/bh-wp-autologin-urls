<?php
/**
 * Interface for settings
 * *
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/includes
 */

namespace BH_WP_Autologin_URLs\includes;

/**
 * Interface Settings_Interface
 */
interface Settings_Interface {

	/**
	 * The number of seconds the autologin link should work for.
	 *
	 * @return int Expiry age in seconds.
	 */
	public function get_expiry_age();

	/**
	 * Setting to determine if autologin codes should be added to admin emails.
	 *
	 * @return bool Should the autologin code be added to urls in emails sent to admins?
	 */
	public function get_add_autologin_for_admins_is_enabled();

	/**
	 * A list of regexes for email subjects that should not have autologin codes added.
	 *
	 * @return string[] Array of regexes.
	 */
	public function get_disallowed_subjects_regex_array();

	/**
	 * A dictionary of regexes and email subjects that should not have autologin codes added.
	 *
	 * @return string[]
	 */
	public function get_disallowed_subjects_regex_dictionary();
}

