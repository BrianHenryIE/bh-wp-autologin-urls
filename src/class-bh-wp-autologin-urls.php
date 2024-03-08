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
 */

namespace BrianHenryIE\WP_Autologin_URLs;

use BrianHenryIE\WP_Autologin_URLs\Admin\Plugin_Installer;
use BrianHenryIE\WP_Autologin_URLs\Admin\User_Edit;
use BrianHenryIE\WP_Autologin_URLs\Admin\Admin_Assets;
use BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Page;
use BrianHenryIE\WP_Autologin_URLs\Admin\Plugins_Page;
use BrianHenryIE\WP_Autologin_URLs\Admin\Users_List_Table;
use BrianHenryIE\WP_Autologin_URLs\Logger\Klaviyo_Logs;
use BrianHenryIE\WP_Autologin_URLs\Login\Login_Ajax;
use BrianHenryIE\WP_Autologin_URLs\Login\Login_Assets;
use BrianHenryIE\WP_Autologin_URLs\WooCommerce\Admin_Order_UI;
use BrianHenryIE\WP_Autologin_URLs\WooCommerce\Login_Form;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\CLI;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\Cron;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\I18n;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\Login;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\REST_API;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\WP_Mail;
use Exception;
use Psr\Log\LoggerInterface;
use WP_CLI;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
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

		$this->define_admin_ui_hooks();
		$this->define_login_ui_hooks();

		$this->define_plugins_page_hooks();
		$this->define_plugin_installer_hooks();

		$this->define_wp_mail_hooks();

		$this->define_wp_login_hooks();

		$this->define_cron_hooks();

		$this->define_woocommerce_admin_order_ui_hooks();
		$this->define_woocommerce_login_form_hooks();

		$this->define_logger_hooks();

		$this->define_cli_hooks();
		$this->define_rest_api_hooks();
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

		$plugin_api = $this->api;

		add_filter( 'add_autologin_to_message', array( $plugin_api, 'add_autologin_to_message' ), 10, 2 );
		add_filter( 'add_autologin_to_url', array( $plugin_api, 'add_autologin_to_url' ), 10, 2 );

		add_action(
			'plugins_loaded',
			function () {
				require_once dirname( __DIR__ ) . '/functions.php';
			},
			2
		);
	}

	/**
	 * Register the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	protected function define_admin_ui_hooks(): void {

		$admin_assets = new Admin_Assets( $this->settings );

		add_action( 'admin_enqueue_scripts', array( $admin_assets, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $admin_assets, 'enqueue_scripts' ) );

		$plugin_settings_page = new Settings_Page( $this->settings, $this->logger );

		add_action( 'admin_menu', array( $plugin_settings_page, 'add_settings_page' ) );
		add_action( 'admin_init', array( $plugin_settings_page, 'setup_sections' ) );
		add_action( 'admin_init', array( $plugin_settings_page, 'setup_fields' ) );

		$user_edit = new User_Edit( $this->api, $this->settings );
		add_action( 'edit_user_profile', array( $user_edit, 'make_password_available_on_user_page' ), 1, 1 );
		add_action( 'show_user_profile', array( $user_edit, 'make_password_available_on_user_page' ), 1, 1 );

		$users_list_table = new Users_List_Table( $this->api, $this->settings );
		add_filter( 'user_row_actions', array( $users_list_table, 'add_magic_email_link' ), 10, 2 );
		add_action( 'admin_init', array( $users_list_table, 'send_magic_email_link' ) );
	}

	/**
	 * Hooks related to the wp-login.php UI.
	 */
	protected function define_login_ui_hooks(): void {

		$login_assets = new Login_Assets( $this->settings );

		add_action( 'login_enqueue_scripts', array( $login_assets, 'enqueue_styles' ) );
		add_action( 'login_enqueue_scripts', array( $login_assets, 'enqueue_scripts' ) );

		$login_ajax = new Login_Ajax( $this->api, $this->logger );

		add_action( 'wp_ajax_nopriv_bh_wp_autologin_urls_send_magic_link', array( $login_ajax, 'email_magic_link' ) );
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
	 * Add a Settings link and a link to "Plugin updated successfully." page that is displayed after updating.
	 */
	protected function define_plugin_installer_hooks(): void {

		$plugin_installer = new Plugin_Installer( $this->settings, $this->logger );

		add_filter( 'install_plugin_complete_actions', array( $plugin_installer, 'add_settings_link' ), 10, 3 );
	}

	/**
	 * Register the hooks related to manipulating outgoing emails.
	 *
	 * @since    1.0.0
	 */
	protected function define_wp_mail_hooks(): void {

		$plugin_wp_mail = new WP_Mail( $this->api, $this->settings );

		add_filter( 'wp_mail', array( $plugin_wp_mail, 'add_autologin_links_to_email' ), 3 );
	}

	/**
	 * Register the hooks related to the actual login functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	protected function define_wp_login_hooks(): void {

		$plugin_login = new Login( $this->api, $this->settings, $this->logger );

		add_filter( 'determine_current_user', array( $plugin_login, 'process' ), 30 );
	}

	/**
	 * Register actions to schedule the cron job and to handle its execution.
	 */
	protected function define_cron_hooks(): void {

		$cron = new Cron( $this->api, $this->logger );

		add_action( 'plugins_loaded', array( $cron, 'schedule_job' ) );
		add_action( Cron::DELETE_EXPIRED_CODES_JOB_NAME, array( $cron, 'delete_expired_codes' ) );
	}

	/**
	 * Register the filter to add the autologin code to the "Customer payment page" link in the admin UI.
	 */
	protected function define_woocommerce_admin_order_ui_hooks(): void {

		$admin_order_ui = new Admin_Order_UI( $this->api, $this->settings );

		add_filter( 'woocommerce_get_checkout_payment_url', array( $admin_order_ui, 'add_to_payment_url' ), 10, 2 );

		add_filter( 'gettext_woocommerce', array( $admin_order_ui, 'remove_arrow_from_link_text' ), 10, 3 );

		add_action( 'admin_enqueue_scripts', array( $admin_order_ui, 'enqueue_script' ) );
		add_action( 'admin_enqueue_scripts', array( $admin_order_ui, 'enqueue_styles' ) );
	}

	/**
	 * Register the action for enqueuing the JavaScript to add the magic-link button to the WooCommerce login form.
	 */
	protected function define_woocommerce_login_form_hooks(): void {

		$login_form = new Login_Form( $this->settings );

		add_action( 'woocommerce_before_customer_login_form', array( $login_form, 'enqueue_script' ) );
		add_action( 'woocommerce_before_checkout_form', array( $login_form, 'enqueue_script' ) );
	}

	/**
	 * Register filters for augmenting the data printed on the logs table.
	 *
	 * @see wp-admin/admin.php?page=bh-wp-autologin-urls-logs
	 */
	protected function define_logger_hooks(): void {

		$klaviyo_logs = new Klaviyo_Logs();

		add_filter( 'bh-wp-autologin-urls_bh_wp_logger_column', array( $klaviyo_logs, 'link_to_klaviyo_profile_search' ), 10, 5 );
	}

	/**
	 * Register actions for CLI commands.
	 */
	protected function define_cli_hooks(): void {

		if ( ! class_exists( WP_CLI::class ) ) {
			return;
		}

		$cli = new CLI( $this->api );

		try {
			WP_CLI::add_command( 'autologin-urls get-url', array( $cli, 'add_autologin_to_url' ) );
			WP_CLI::add_command( 'autologin-urls send-magic-link', array( $cli, 'send_magic_link' ) );
		} catch ( Exception $e ) {
			$this->logger->error( 'Failed to register WP CLI commands: ' . $e->getMessage(), array( 'exception' => $e ) );
		}
	}

	protected function define_rest_api_hooks(): void {

		$rest_api = new REST_API( $this->api );

		add_action( 'rest_api_init', array( $rest_api, 'register_routes' ) );
	}
}
