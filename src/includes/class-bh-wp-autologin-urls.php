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


namespace BrianHenryIE\WP_Autologin_URLs\includes;

use BrianHenryIE\WP_Autologin_URLs\admin\User_Edit;
use BrianHenryIE\WP_Autologin_URLs\api\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\api\API;
use BrianHenryIE\WP_Autologin_URLs\admin\Admin;
use BrianHenryIE\WP_Autologin_URLs\admin\Settings_Page;
use BrianHenryIE\WP_Autologin_URLs\admin\Plugins_Page;
use BrianHenryIE\WP_Autologin_URLs\api\Settings_Interface;
use Psr\Log\LoggerInterface;
use BrianHenryIE\WP_Autologin_URLs\api\DB_Data_Store;


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
	 * Instance of PSR logger to record logins and issues.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

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
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger PSR Logger.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $settings, $logger ) {

		$this->settings = $settings;
		$this->logger   = $logger;

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

		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );

	}

	/**
	 * Instantiates the API class for use by the wp_mail filtering class and makes it publicly available.
	 */
	private function setup_api() {

		$datastore = new DB_Data_Store();

		add_action( 'plugins_loaded', array( $datastore, 'create_db', 1 ) );

		$this->api = $plugin_api = new API( $this->settings, $this->logger, $datastore );

		add_filter( 'add_autologin_to_message', array( $plugin_api, 'add_autologin_to_message', 10, 2 ) );
		add_filter( 'add_autologin_to_url', array( $plugin_api, 'add_autologin_to_url', 10, 2 ) );
	}


	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$this->admin = $plugin_admin = new Admin( $this->settings );

		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );

		$this->settings_page = $plugin_settings_page = new Settings_Page( $this->settings, $this->logger );

		add_action( 'admin_menu', array( $plugin_settings_page, 'add_settings_page' ) );
		add_action( 'admin_init', array( $plugin_settings_page, 'setup_sections' ) );
		add_action( 'admin_init', array( $plugin_settings_page, 'setup_fields' ) );

		$this->plugins_page = $plugins_page = new Plugins_Page( $this->settings );

		$plugin_basename = 'bh-wp-autologin-urls/bh-wp-autologin-urls.php';

		add_filter( 'plugin_action_links_' . $plugin_basename, array( $plugins_page, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( $plugins_page, 'row_meta', 20, 4 ) );

		$this->user_edit = new User_Edit( $this->api );
		add_action( 'edit_user_profile', array( $this->user_edit, 'make_password_available_on_user_page', 1, 1 ) );

	}


	/**
	 * Register all of the hooks related to manipulating outgoing emails.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_wp_mail_hooks() {

		$this->wp_mail = $plugin_wp_mail = new WP_Mail( $this->api, $this->settings );

		add_filter( 'wp_mail', array( $plugin_wp_mail, 'add_autologin_links_to_email', 3, 4 ) );
	}


	/**
	 * Register the the hooks related to the actual login functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_login_hooks() {

		$this->login = $plugin_login = new Login( $this->api, $this->logger );

		add_action( 'plugins_loaded', array( $plugin_login, 'wp_init_process_autologin', 2 ) );

		add_action( 'plugins_loaded', array( $plugin_login, 'login_newsletter_urls', 0 ) );
		add_action( 'plugins_loaded', array( $plugin_login, 'login_mailpoet_urls', 0 ) );

	}

}
