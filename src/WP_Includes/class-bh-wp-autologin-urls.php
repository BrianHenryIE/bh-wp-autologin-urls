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

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\WP_Autologin_URLs\Admin\User_Edit;
use BrianHenryIE\WP_Autologin_URLs\API\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\API;
use BrianHenryIE\WP_Autologin_URLs\Admin\Admin;
use BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Page;
use BrianHenryIE\WP_Autologin_URLs\Admin\Plugins_Page;
use BrianHenryIE\WP_Autologin_URLs\API\Settings;
use BrianHenryIE\WP_Autologin_URLs\API\Settings_Interface;
use Psr\Log\LoggerInterface;
use BrianHenryIE\WP_Autologin_URLs\API\DB_Data_Store;

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
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @param API_Interface      $api The main plugin functions.
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger PSR Logger.
	 *
	 * @since    1.0.0
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {

		$this->api      = $api;
		$this->settings = $settings;
		$this->logger   = $logger;

		$this->setup_api();

		$this->set_locale();

		$this->define_admin_hooks();
		$this->define_plugins_page_hooks();

		$this->define_wp_mail_hooks();

		$this->define_wp_login_hooks();

		$this->define_cron_hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	protected function set_locale(): void {

		$plugin_i18n = new I18n();

		add_action( 'init', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Instantiates the API class for use by the wp_mail filtering class and makes it publicly available.
	 */
	protected function setup_api(): void {

		$datastore = new DB_Data_Store( $this->logger );

		add_action( 'plugins_loaded', array( $datastore, 'create_db' ), 1 );

		$plugin_api = $this->api;

		add_filter( 'add_autologin_to_message', array( $plugin_api, 'add_autologin_to_message' ), 10, 2 );
		add_filter( 'add_autologin_to_url', array( $plugin_api, 'add_autologin_to_url' ), 10, 2 );
	}

	/**
	 * Register the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	protected function define_admin_hooks(): void {

		$plugin_admin = new Admin( $this->settings );

		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );

		$plugin_settings_page = new Settings_Page( $this->settings, $this->logger );

		add_action( 'admin_menu', array( $plugin_settings_page, 'add_settings_page' ) );
		add_action( 'admin_init', array( $plugin_settings_page, 'setup_sections' ) );
		add_action( 'admin_init', array( $plugin_settings_page, 'setup_fields' ) );

		$user_edit = new User_Edit( $this->api );
		add_action( 'edit_user_profile', array( $user_edit, 'make_password_available_on_user_page' ), 1, 1 );
	}

	/**
	 * Add a Settings link and a link to the plugin on GitHub.
	 */
	protected function define_plugins_page_hooks(): void {

		$plugins_page = new Plugins_Page( $this->settings );

		$plugin_basename = $this->settings->get_plugin_basename();

		add_filter( "plugin_action_links_{$plugin_basename}", array( $plugins_page, 'action_links' ), 10, 4 );
		add_filter( 'plugin_row_meta', array( $plugins_page, 'row_meta' ), 20, 4 );
	}

	/**
	 * Register the hooks related to manipulating outgoing emails.
	 *
	 * @since    1.0.0
	 */
	protected function define_wp_mail_hooks(): void {

		$plugin_wp_mail = new WP_Mail( $this->api, $this->settings );

		add_filter( 'wp_mail', array( $plugin_wp_mail, 'add_autologin_links_to_email' ), 3, 4 );
	}

	/**
	 * Register the hooks related to the actual login functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	protected function define_wp_login_hooks(): void {

		$plugin_login = new Login( $this->api, $this->logger );

		add_action( 'plugins_loaded', array( $plugin_login, 'wp_init_process_autologin' ), 2 );

		add_action( 'plugins_loaded', array( $plugin_login, 'login_newsletter_urls' ), 0 );
		add_action( 'plugins_loaded', array( $plugin_login, 'login_mailpoet_urls' ), 0 );
	}

	/**
	 * Register actions to schedule the cron job and to handle its execution.
	 */
	protected function define_cron_hooks(): void {

		$cron = new Cron( $this->api, $this->logger );

		add_action( 'plugins_loaded', array( $cron, 'schedule_job' ) );
		add_action( Cron::DELETE_EXPIRED_CODES_JOB_NAME, array( $cron, 'delete_expired_codes' ) );
	}

}
