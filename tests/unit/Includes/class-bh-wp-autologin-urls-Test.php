<?php

namespace BrianHenryIE\WP_Autologin_URLs\Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\Admin\Admin;
use BrianHenryIE\WP_Autologin_URLs\Admin\Plugins_Page;
use BrianHenryIE\WP_Autologin_URLs\API\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\Settings_Interface;
use Codeception\Stub\Expected;
use WP_Mock\Matcher\AnyInstance;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\Includes\BH_WP_Autologin_URLs
 */
class BH_WP_Autologin_URLs_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		parent::setup();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::set_locale
	 * @covers ::__construct
	 */
	public function test_set_locale_hooked(): void {

		\WP_Mock::expectActionAdded(
			'init',
			array( new AnyInstance( I18n::class ), 'load_plugin_textdomain' )
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_wp_login_hooks
	 */
	public function test_wp_login_hooks(): void {

		\WP_Mock::expectActionAdded(
			'plugins_loaded',
			array( new AnyInstance( Login::class ), 'wp_init_process_autologin' ),
			2
		);
		\WP_Mock::expectActionAdded(
			'plugins_loaded',
			array( new AnyInstance( Login::class ), 'login_newsletter_urls' ),
			0
		);
		\WP_Mock::expectActionAdded(
			'plugins_loaded',
			array( new AnyInstance( Login::class ), 'login_mailpoet_urls' ),
			0
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_cron_hooks
	 */
	public function test_define_cron_hooks(): void {

		\WP_Mock::expectActionAdded(
			'plugins_loaded',
			array( new AnyInstance( Cron::class ), 'schedule_job' )
		);
		\WP_Mock::expectActionAdded(
			'bh_wp_autologin_urls_delete_expired_codes',
			array( new AnyInstance( Cron::class ), 'delete_expired_codes' )
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_plugins_page_hooks
	 */
	public function test_define_plugins_page_hooks(): void {

		$basename = 'bh-wp-autologin-urls/bh-wp-autologin-urls.php';

		\WP_Mock::expectFilterAdded(
			"plugin_action_links_{$basename}",
			array( new AnyInstance( Plugins_Page::class ), 'action_links' ),
			10,
			4
		);
		\WP_Mock::expectFilterAdded(
			'plugin_row_meta',
			array( new AnyInstance( Plugins_Page::class ), 'row_meta' ),
			20,
			4
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => Expected::once(
					function() use ( $basename ) {
						return $basename;}
				),
			)
		);
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}
}
