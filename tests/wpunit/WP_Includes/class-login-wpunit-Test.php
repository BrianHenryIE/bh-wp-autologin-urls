<?php
/**
 * Login takes care of... logging in... but when no WP User exists to log in, it checks
 * for the presence of WooCommerce and tries to fill in checkout fields.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\Integrations\User_Finder_Factory;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\User_Finder_Interface;
use Codeception\Stub\Expected;
use WP_User;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\WP_Includes\Login
 */
class Login_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * The happy path.
	 *
	 * @covers ::process
	 * @covers ::__construct
	 */
	public function test_process_valid_user_login(): void {

		$logger = new ColorLogger();

		$settings = $this->makeEmpty( Settings_Interface::class );

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_ip_address'             => Expected::once( '1.2.3.4' ),
				'should_allow_login_attempt' => Expected::exactly( 2, true ),
			)
		);

		$new_user = array(
			'user_pass'  => 'password',
			'user_login' => 'test_user',
		);
		$user_id  = wp_insert_user( $new_user );
		$wp_user  = get_user_by( 'id', $user_id );

		$user_finder = $this->makeEmpty(
			User_Finder_Interface::class,
			array(
				'is_querystring_valid' => true,
				'get_wp_user_array'    => array(
					'wp_user' => $wp_user,
					'source'  => 'mock',
				),
			)
		);

		$user_finder_factory = $this->makeEmpty(
			User_Finder_Factory::class,
			array(
				'get_user_finder' => $user_finder,
			)
		);

		$sut = new Login( $api, $settings, $logger, $user_finder_factory );

		$sut->process();

		$logged_in_user_id = get_current_user_id();

		$this->assertEquals( $user_id, $logged_in_user_id );

	}

	/**
	 * @covers ::process
	 */
	public function test_process_user_already_logged_in(): void {

		$logger = new ColorLogger();

		$settings = $this->makeEmpty( Settings_Interface::class );

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_ip_address'             => Expected::never(),
				'should_allow_login_attempt' => Expected::never(),
			)
		);

		$new_user = array(
			'user_pass'  => 'password',
			'user_login' => 'test_user',
		);
		$user_id  = wp_insert_user( $new_user );
		/** @var WP_User $wp_user */
		$wp_user = get_user_by( 'id', $user_id );

		$user_finder = $this->makeEmpty(
			User_Finder_Interface::class,
			array(
				'is_querystring_valid' => true,
				'get_wp_user_array'    => array(
					'wp_user' => $wp_user,
					'source'  => 'mock',
				),
			)
		);

		$user_finder_factory = $this->makeEmpty(
			User_Finder_Factory::class,
			array(
				'get_user_finder' => $user_finder,
			)
		);

		$sut = new Login( $api, $settings, $logger, $user_finder_factory );

		wp_set_current_user( $wp_user->ID, $wp_user->user_login );
		wp_set_auth_cookie( $wp_user->ID );

		assert( get_current_user_id() === $wp_user->ID );

		$sut->process();

		$logged_in_user_id = get_current_user_id();

		$this->assertEquals( $user_id, $logged_in_user_id );

	}
}
