<?php
/**
 * Tests for WP_Mail.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\wp_mail;

use BH_WP_Autologin_URLs\includes\Settings_Interface;
use BH_WP_Autologin_URLs\api\API_Interface;

/**
 * Class WP_Mail_WP_Mock_Test
 */
class WP_Mail_WP_Mock_Test extends \Codeception\Test\Unit {

	protected function _before() {

		\WP_Mock::setUp();
	}


	/**
	 * The plugin name. Unlikely to ever change.
	 *
	 * @var string Plugin name
	 */
	private $plugin_name = 'bh-wp-autologin-urls';

	/**
	 * The last plugin version these tests were written against.
	 *
	 * @var string Plugin version.
	 */
	private $version = '1.0.0';

	/**
	 * Test constructor.
	 */
	public function test_constructor() {

		$api_mock = $this->createMock( API_Interface::class );

		$settings_mock = $this->createMock( Settings_Interface::class );

		$wp_mail = new WP_Mail( $this->plugin_name, $this->version, $api_mock, $settings_mock );

		$this->assertInstanceOf( WP_Mail::class, $wp_mail );

	}

	/**
	 * Test NOT adding the autologin code to an email when its subject matches a regex in the settings.
	 */
	public function test_disallowed_subjects_in_settings() {

		$settings_mock = $this->createMock( Settings_Interface::class );
		$settings_mock->method( 'get_disallowed_subjects_regex_array' )->willReturn( array( '/^myRegex\d\d\d$/' ) );

		$api_mock = $this->createMock( API_Interface::class );

		$wp_mail = new WP_Mail( $this->plugin_name, $this->version, $api_mock, $settings_mock );

		$email_args = array(
			'to'      => 'brianhenryie@gmail.com',
			'subject' => 'myRegex123',
			'message' => 'https://example.org',
		);

		$user = $this->createMock( '\WP_User' );

		$user->method( 'has_cap' )
			 ->with( 'administrator' )
			 ->willReturn( false );

		\WP_Mock::userFunction(
			'get_user_by',
			array(
				'args'   => array( 'email', 'brianhenryie@gmail.com' ),
				'times'  => 1,
				'return' => $user,
			)
		);

		\WP_Mock::userFunction(
			'get_site_url',
			array(
				'return' => 'https://example.org',
			)
		);

		$filtered_email_args = $wp_mail->add_autologin_links_to_email( $email_args );

		$this->assertEquals( $email_args, $filtered_email_args );

	}

}
