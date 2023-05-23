<?php
/**
 * Adds links to Settings and Logs on the "Plugin updated successfully" page.
 *
 * @package brianhenryie/bh-wc-shipment-tracking-updates
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Checks is WooCommerce active, then appends the Settings link to the "Return to plugins installer" link on the plugin update page.
 */
class Plugin_Installer {
	use LoggerAwareTrait;

	/**
	 * Settings needed to determine if the current update is for this plugin, then to generate the correct url.
	 *
	 * @uses Settings_Interface::get_plugin_basename()
	 * @uses Settings_Interface::get_plugin_slug()
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger The plugin's PSR logger.
	 */
	public function __construct( Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->settings = $settings;
	}

	/**
	 * Add the settings page link to the existing links.
	 *
	 * These are only displayed on subsequent plugin installs, not the first.
	 *
	 * @hooked install_plugin_complete_actions
	 * @see \Plugin_Installer_Skin::after()
	 *
	 * @param string[] $install_actions Array of plugin action links.
	 * @param object   $_api            Object containing WordPress.org API plugin data. Empty
	 *                                  for non-API installs, such as when a plugin is installed
	 *                                  via upload.
	 * @param string   $plugin_file     Path to the plugin file relative to the plugins directory.
	 *
	 * @return string[]
	 */
	public function add_settings_link( $install_actions, $_api, $plugin_file ): array {

		if ( $plugin_file !== $this->settings->get_plugin_basename() ) {
			return $install_actions;
		}

		$install_actions[] = '•';

		$settings_url      = admin_url( '/options-general.php?page=' . $this->settings->get_plugin_slug() );
		$install_actions[] = '<a href="' . $settings_url . '">Go to Autologin URLs settings</a>';

		return $install_actions;
	}
}
