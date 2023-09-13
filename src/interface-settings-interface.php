<?php
/**
 * Interface for settings
 * *
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs;

use BrianHenryIE\WP_Autologin_URLs\Admin\Plugins_Page;

interface Settings_Interface {

	/**
	 * The number of seconds the autologin link should work for.
	 *
	 * @return int Expiry age in seconds.
	 */
	public function get_expiry_age(): int;

	/**
	 * Setting to determine if autologin codes should be added to admin emails.
	 *
	 * @return bool Should the autologin code be added to urls in emails sent to admins?
	 */
	public function get_add_autologin_for_admins_is_enabled(): bool;

	/**
	 * A list of regexes for email subjects that should not have autologin codes added.
	 *
	 * @return string[] Array of regexes.
	 */
	public function get_disallowed_subjects_regex_array(): array;

	/**
	 * A dictionary of regexes and email subjects that should not have autologin codes added.
	 *
	 * @return string[]
	 */
	public function get_disallowed_subjects_regex_dictionary(): array;

	/**
	 * PSR log level. Ideally Error or Notice. Info or Debug for more detail.
	 *
	 * @see LogLevel
	 *
	 * @return string
	 */
	public function get_log_level(): string;

	/**
	 * In troublesome cases, potential issues with caching, user wp-login.php to land on and redirect from.
	 *
	 * @return bool
	 */
	public function get_should_use_wp_login(): bool;

	/**
	 * The plugins slug as a unique handle for JS and CSS.
	 *
	 * @used-by Admin::enqueue_scripts()
	 * @used-by Admin::enqueue_styles()
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string;

	/**
	 * The plugin version, to avoid using cached assets from older releases.
	 *
	 * @used-by Admin::enqueue_scripts()
	 * @used-by Admin::enqueue_styles()
	 *
	 * @return string
	 */
	public function get_plugin_version(): string;

	/**
	 * The plugin basename, used on plugins.php to add links to the correct row.
	 *
	 * @used-by Plugins_Page::action_links()
	 *
	 * @return string
	 */
	public function get_plugin_basename(): string;

	/**
	 * Return the API key to query the Klaviyo API for user details, if entered.
	 */
	public function get_klaviyo_private_api_key(): ?string;

	/**
	 * Enable/disable the magic link feature.
	 */
	public function is_magic_link_enabled(): bool;
}
