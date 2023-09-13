<?php
/**
 * The plugin page output of the plugin.
 *
 * @link
 * @since      1.3.0
 *
 * @package    brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;

/**
 * This class adds a `Settings` link and a `GitHub` link on the plugins.php page.
 */
class Plugins_Page {

	/**
	 * Needed for the plugin slug and basename.
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings_Interface $settings The plugin settings.
	 */
	public function __construct( Settings_Interface $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Add link to settings page in plugins.php list.
	 *
	 * @hooked plugin_action_links_{basename}
	 *
	 * @param array<int|string, string>   $links_array The existing plugin links (usually "Deactivate"). May or may not be indexed with a string.
	 * @param ?string                     $plugin_file The plugin basename.
	 * @param ?array<string, string|bool> $plugin_data The parsed plugin header data.
	 * @param ?string                     $context 'all'|'active'|'inactive'...
	 * @return array<int|string, string> The links to display below the plugin name on plugins.php.
	 */
	public function action_links( array $links_array, ?string $plugin_file, ?array $plugin_data, ?string $context ): array {

		$settings_url = admin_url( '/options-general.php?page=' . $this->settings->get_plugin_slug() );

		array_unshift( $links_array, '<a href="' . $settings_url . '">Settings</a>' );

		return $links_array;
	}

	/**
	 * Add a link to GitHub repo on the plugins list.
	 *
	 * @hooked plugin_row_meta
	 *
	 * @see https://rudrastyh.com/wordpress/plugin_action_links-plugin_row_meta.html
	 *
	 * @param array<int|string, string>  $plugin_meta The meta information/links displayed by the plugin description.
	 * @param string                     $plugin_file_name The plugin filename to match when filtering.
	 * @param array<string, string|bool> $plugin_data Associative array including PluginURI, slug, Author, Version.
	 * @param string                     $status The plugin status, e.g. 'Inactive'.
	 *
	 * @return array<int|string, string> The filtered $plugin_meta.
	 */
	public function row_meta( array $plugin_meta, string $plugin_file_name, array $plugin_data, string $status ): array {

		if ( $this->settings->get_plugin_basename() === $plugin_file_name ) {

			$plugin_meta[] = '<a target="_blank" href="https://github.com/BrianHenryIE/' . $this->settings->get_plugin_slug() . '">View on GitHub</a>';
		}

		return $plugin_meta;
	}
}
