<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/includes
 */

namespace BH_WP_Autologin_URLs\includes;

use BH_WP_Autologin_URLs\api\API_Interface;
use BH_WP_Autologin_URLs\api\API;
use BH_WP_Autologin_URLs\admin\Admin;
use BH_WP_Autologin_URLs\admin\Settings_Page;
use BH_WP_Autologin_URLs\admin\Plugins_Page;
use BH_WP_Autologin_URLs\login\Login;
use BH_WP_Autologin_URLs\wp_mail\WP_Mail;
use BH_WP_Autologin_URLs\WPPB\WPPB_Loader_Interface;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/includes
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 *
 * phpcs:disable Squiz.PHP.DisallowMultipleAssignments.Found
 */
class BH_WP_Autologin_URLs {

	/**
	 * Instance member of API class to expose to WordPress to allow users unhook actions.
	 *
	 * @var API_Interface
	 */
	public $api;

	/**
	 * Plugin settings as saved in the database.
	 *
	 * @var Settings_Interface
	 */
	public $settings;

	/**
	 * Instance member of Admin class to expose to WordPress to allow users unhook actions.
	 *
	 * @var Admin
	 */
	public $admin;


	/**
	 * Instance member of Settings Page class to expose to WordPress to allow users unhook actions.
	 *
	 * @var Settings_Page
	 */
	public $settings_page;

	/**
	 * Instance member of I18n class to expose to WordPress to allow users unhook actions.
	 *
	 * @var I18n
	 */
	public $i18n;

	/**
	 * Instance member of Login class to expose to WordPress to allow users unhook actions.
	 *
	 * @var Login
	 */
	public $login;

	/**
	 * Instance member of WP_Mail class to expose to WordPress, e.g. to allow users unhook actions.
	 *
	 * @var WP_Mail
	 */
	public $wp_mail;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WPPB_Loader_Interface    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 *
	 * @param WPPB_Loader_Interface $loader The class which adds the actions and filters.
	 * @param Settings_Interface    $settings The plugin settings.
	 */
	public function __construct( $loader, $settings ) {
		if ( defined( 'BH_WP_AUTOLOGIN_URLS_VERSION' ) ) {
			$this->version = BH_WP_AUTOLOGIN_URLS_VERSION;
		} else {
			$this->version = '1.1.2';
		}
		$this->plugin_name = 'bh-wp-autologin-urls';

		$this->loader = $loader;

		$this->settings = $settings;

		$this->setup_api();

		$this->set_locale();

		$this->define_admin_hooks();
		$this->define_wp_mail_hooks();

		$this->define_login_hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$this->i18n = $plugin_i18n = new I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Instantiates the API class for use by the wp_mail filtering class and makes it publicly available.
	 */
	private function setup_api() {

		$this->api = $plugin_api = new API( $this->plugin_name, $this->version, $this->settings );

		$this->loader->add_filter( 'add_autologin_to_message', $plugin_api, 'add_autologin_to_message', 10, 2 );
		$this->loader->add_filter( 'add_autologin_to_url', $plugin_api, 'add_autologin_to_url', 10, 2 );
	}


	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$this->admin = $plugin_admin = new Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->settings_page = $plugin_settings_page = new Settings_Page( $this->get_plugin_name(), $this->get_version(), $this->settings );

		$this->loader->add_action( 'admin_menu', $plugin_settings_page, 'add_settings_page' );
		$this->loader->add_action( 'admin_init', $plugin_settings_page, 'setup_sections' );
		$this->loader->add_action( 'admin_init', $plugin_settings_page, 'setup_fields' );

		$this->plugins_page = $plugins_page = new Plugins_Page( $this->get_plugin_name(), $this->get_version() );

		$plugin_basename = 'bh-wp-autologin-urls/bh-wp-autologin-urls.php';

		$this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $plugins_page, 'action_links' );
		$this->loader->add_filter( 'plugin_row_meta', $plugins_page, 'row_meta', 20, 4 );
	}


	/**
	 * Register all of the hooks related to manipulating outgoing emails.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_wp_mail_hooks() {

		$this->wp_mail = $plugin_wp_mail = new WP_Mail( $this->get_plugin_name(), $this->get_version(), $this->api, $this->settings );

		$this->loader->add_filter( 'wp_mail', $plugin_wp_mail, 'add_autologin_links_to_email', 3, 4 );
	}


	/**
	 * Register the the hooks related to the actual login functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_login_hooks() {

		$this->login = $plugin_login = new Login( $this->get_plugin_name(), $this->get_version(), $this->api );

		$this->loader->add_action( 'plugins_loaded', $plugin_login, 'wp_init_process_autologin', 2 );

	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    WPPB_Loader_Interface    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
