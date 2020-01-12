<?php
/**
 * The plugin page output of the plugin.
 *
 * @link
 * @since      3.0.0
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/admin
 */

namespace BH_WP_Autologin_URLs\admin;

use BH_WP_Autologin_URLs\WPPB\WPPB_Object;

/**
 * This class adds a `Settings` link on the plugins.php page.
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/admin
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Plugins_Page extends WPPB_Object {

	/**
	 * Add link to settings page in plugins.php list.
	 *
	 * @param array $links_array The existing plugin links (usually "Deactivate").
	 *
	 * @return array The links to display below the plugin name on plugins.php.
	 */
	public function action_links( $links_array ) {

		$settings_url = admin_url( '/options-general.php?page=' . $this->plugin_name );

		array_unshift( $links_array, '<a href="' . $settings_url . '">Settings</a>' );

		return $links_array;
	}

	/**
	 * Add a link to GitHub repo on the plugins list.
	 *
	 * @see https://rudrastyh.com/wordpress/plugin_action_links-plugin_row_meta.html
	 *
	 * @param string[] $plugin_meta The meta information/links displayed by the plugin description.
	 * @param string   $plugin_file_name The plugin filename to match when filtering.
	 * @param array    $plugin_data Associative array including PluginURI, slug, Author, Version.
	 * @param string   $status The plugin status, e.g. 'Inactive'.
	 *
	 * @return array The filtered $plugin_meta.
	 */
	public function row_meta( $plugin_meta, $plugin_file_name, $plugin_data, $status ) {

		if ( $this->plugin_name . '/' . $this->plugin_name . '.php' === $plugin_file_name ) {

			$plugin_meta[] = '<a target="_blank" href="https://github.com/BrianHenryIE/BH-WP-Autologin-URLs">View plugin on GitHub</a>';
		}

		return $plugin_meta;
	}

}
