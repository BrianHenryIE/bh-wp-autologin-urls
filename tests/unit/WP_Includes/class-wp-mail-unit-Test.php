<?php
/**
 * Tests for WP_Mail.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\WP_Autologin_URLs\API\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\API_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\WP_Includes\WP_Mail
 */
class WP_Mail_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Test constructor.
	 *
	 * @covers ::__construct()
	 */
	public function test_constructor(): void {

		$api_mock = $this->createMock( API_Interface::class );

		$settings_mock = $this->createMock( Settings_Interface::class );

		$wp_mail = new WP_Mail( $api_mock, $settings_mock );

		$this->assertInstanceOf( WP_Mail::class, $wp_mail );

	}

	/**
	 * Test NOT adding the autologin code to an email when its subject matches a regex in the settings.
	 *
	 * @covers ::add_autologin_links_to_email
	 */
	public function test_disallowed_subjects_in_settings(): void {

		$settings_mock = $this->createMock( Settings_Interface::class );
		$settings_mock->method( 'get_disallowed_subjects_regex_array' )->willReturn( array( '/^myRegex\d\d\d$/' ) );

		$api_mock = $this->createMock( API_Interface::class );

		$wp_mail = new WP_Mail( $api_mock, $settings_mock );

		$email_args = array(
			'to'      => 'brianhenryie@gmail.com',
			'subject' => 'myRegex123',
			'message' => 'https://example.org',
		);

		$user = $this->createMock( '\WP_User' );

		$user->method( 'has_cap' )->with( 'administrator' )->willReturn( false );

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

	/**
	 * @covers ::add_autologin_links_to_email
	 */
	public function test_do_not_add_to_cc(): void {

		$settings_mock = $this->createMock( Settings_Interface::class );

		$api_mock = $this->makeEmpty(
			API_Interface::class,
			array(
				'add_autologin_to_message' => Expected::never(),
			)
		);

		$wp_mail = new WP_Mail( $api_mock, $settings_mock );

		$email_args = array(
			'to'      => 'brianhenryie@gmail.com',
			'subject' => 'BCC Test',
			'message' => 'https://example.org',
			'headers' => 'Cc:example@mail.com',
		);

		$user = $this->createMock( '\WP_User' );
		$user->method( 'has_cap' )->with( 'administrator' )->willReturn( false );

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

	/**
	 * @covers ::add_autologin_links_to_email
	 */
	public function test_do_not_add_to_bcc(): void {

		$settings_mock = $this->createMock( Settings_Interface::class );

		$api_mock = $this->makeEmpty(
			API_Interface::class,
			array(
				'add_autologin_to_message' => Expected::never(),
			)
		);

		$wp_mail = new WP_Mail( $api_mock, $settings_mock );

		$email_args = array(
			'to'      => 'brianhenryie@gmail.com',
			'subject' => 'BCC Test',
			'message' => 'https://example.org',
			'headers' => array( 'Bcc:example@mail.com' ),
		);

		$user = $this->createMock( '\WP_User' );
		$user->method( 'has_cap' )->with( 'administrator' )->willReturn( false );

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
