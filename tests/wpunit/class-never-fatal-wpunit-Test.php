<?php
/**
 * The plugin is never essential to a request: when its data store fails, the operation it was
 * hooked into must still complete.
 *
 * Regression tests for an uncaught `Exception: Operation not allowed when innodb_force_recovery > 0`
 * which escaped `DB_Data_Store::save()` through the `wp_mail` filter and broke an unrelated
 * plugin's email.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API\API;
use BrianHenryIE\WP_Autologin_URLs\API\Data_Stores\DB_Data_Store;
use BrianHenryIE\WP_Autologin_URLs\API\Settings;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\Login;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\WP_Mail;

/**
 * @see \BrianHenryIE\WP_Autologin_URLs\API\API
 * @see \BrianHenryIE\WP_Autologin_URLs\WP_Includes\WP_Mail
 * @see \BrianHenryIE\WP_Autologin_URLs\WP_Includes\Login
 */
class Never_Fatal_WPUnit_Test extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Make every query touching the autologin table throw, simulating an unavailable database.
	 *
	 * The `query` filter runs inside `$wpdb`, so the throwable propagates out of
	 * `$wpdb->insert()` / `$wpdb->get_row()` exactly as a real database failure would.
	 */
	protected function make_the_database_fail(): void {
		add_filter(
			'query',
			function ( $query ) {
				if ( str_contains( $query, 'autologin_urls' ) ) {
					throw new \Exception( 'Operation not allowed when innodb_force_recovery > 0.' );
				}
				return $query;
			}
		);
	}

	protected function get_api( ColorLogger $logger ): API {
		$data_store = new DB_Data_Store( $logger );
		$data_store->create_db();

		return new API( new Settings(), $logger, $data_store );
	}

	/**
	 * The reported crash: adding a code fails, so the URL is returned unchanged.
	 *
	 * @covers \BrianHenryIE\WP_Autologin_URLs\API\API::add_autologin_to_url
	 */
	public function test_add_autologin_to_url_returns_the_url_unchanged(): void {

		$logger  = new ColorLogger();
		$api     = $this->get_api( $logger );
		$user_id = $this->factory->user->create();

		$this->make_the_database_fail();

		$url = get_site_url() . '/some-page/';

		$result = $api->add_autologin_to_url( $url, $user_id, 3600 );

		$this->assertEquals( $url, $result, 'The URL should be returned unmodified.' );
		$this->assertStringNotContainsString( 'autologin=', $result );
		$this->assertTrue( $logger->hasErrorRecords(), 'The failure should be logged at error level, which surfaces the admin notice.' );
	}

	/**
	 * The `wp_mail` filter must return its arguments so the email still sends.
	 *
	 * @covers \BrianHenryIE\WP_Autologin_URLs\WP_Includes\WP_Mail::add_autologin_links_to_email
	 */
	public function test_wp_mail_filter_returns_its_arguments(): void {

		$logger = new ColorLogger();
		$api    = $this->get_api( $logger );

		$user_id = $this->factory->user->create();
		$user    = get_user_by( 'id', $user_id );

		$this->make_the_database_fail();

		$sut = new WP_Mail( $api, new Settings(), $logger );

		$wp_mail_args = array(
			'to'          => $user->user_email,
			'subject'     => 'Test subject',
			'message'     => 'Visit ' . get_site_url() . '/some-page/ to continue.',
			'attachments' => array(),
		);

		$result = $sut->add_autologin_links_to_email( $wp_mail_args );

		$this->assertEquals( $wp_mail_args['message'], $result['message'], 'The message should be unmodified.' );
		$this->assertEquals( $wp_mail_args['to'], $result['to'] );
	}

	/**
	 * `determine_current_user` runs on every request, so a database failure must not be fatal –
	 * and must not log anyone in.
	 *
	 * @covers \BrianHenryIE\WP_Autologin_URLs\WP_Includes\Login::process
	 * @covers \BrianHenryIE\WP_Autologin_URLs\API\API::verify_autologin_password
	 */
	public function test_login_returns_the_incoming_user_id(): void {

		$logger  = new ColorLogger();
		$api     = $this->get_api( $logger );
		$user_id = $this->factory->user->create();

		// Generate a real code before breaking the database, so the querystring is well-formed.
		$url = $api->add_autologin_to_url( get_site_url(), $user_id, 3600 );
		$this->assertStringContainsString( 'autologin=', $url, 'Precondition: a code was created.' );

		$this->make_the_database_fail();

		$this->go_to( $url );
		wp_set_current_user( 0 );

		$sut = new Login( $api, new Settings(), $logger );

		$result = $sut->process( 0 );

		$this->assertEquals( 0, $result, 'A database failure must not log the user in.' );
	}

	/**
	 * The cron callback must not throw – other callbacks on the event would not run.
	 *
	 * @covers \BrianHenryIE\WP_Autologin_URLs\API\API::delete_expired_codes
	 */
	public function test_delete_expired_codes_does_not_throw(): void {

		$logger = new ColorLogger();
		$api    = $this->get_api( $logger );

		$this->make_the_database_fail();

		$result = $api->delete_expired_codes();

		$this->assertNull( $result['deleted_count'] );
		$this->assertTrue( $logger->hasErrorRecords() );
	}

	/**
	 * Rather than emailing a "Sign-in Link" which does not sign anyone in.
	 *
	 * @covers \BrianHenryIE\WP_Autologin_URLs\API\API::send_magic_link
	 */
	public function test_magic_link_is_not_sent_without_a_code(): void {

		$logger  = new ColorLogger();
		$api     = $this->get_api( $logger );
		$user_id = $this->factory->user->create();
		$user    = get_user_by( 'id', $user_id );

		$this->make_the_database_fail();

		$mail_sent = false;
		add_filter(
			'pre_wp_mail',
			function ( $short_circuit ) use ( &$mail_sent ) {
				$mail_sent = true;
				return true;
			}
		);

		$result = $api->send_magic_link( $user->user_email );

		$this->assertFalse( $result['success'] );
		$this->assertTrue( $result['error'] ?? false );
		$this->assertFalse( $mail_sent, 'No email should be sent when the link would not work.' );
	}
}
