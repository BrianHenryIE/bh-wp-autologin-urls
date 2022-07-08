<?php
/**
 * Tests for WP_Mail.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use Codeception\Stub\Expected;
use WP_User;

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
	 * Test the function returns early when there is no user account for the email address.
	 *
	 * @covers ::add_autologin_links_to_email
	 */
	public function test_no_wp_user_found_for_email(): void {

		$settings_mock = $this->createMock( Settings_Interface::class );

		$api_mock = $this->createMock( API_Interface::class );

		$wp_mail = new WP_Mail( $api_mock, $settings_mock );

		$email_args = array(
			'to'      => 'brianhenryie@gmail.com',
			'subject' => 'Example Email',
			'message' => 'https://example.org',
		);

		\WP_Mock::userFunction(
			'get_user_by',
			array(
				'args'   => array( 'email', 'brianhenryie@gmail.com' ),
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_site_url',
			array(
				'times'  => 0,
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

		$user = $this->createMock( WP_User::class );
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
