<?php

namespace BrianHenryIE\WP_Autologin_URLs\Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\Admin\Admin;
use BrianHenryIE\WP_Autologin_URLs\API\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\Settings_Interface;
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

}
