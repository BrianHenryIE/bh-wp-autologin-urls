<?php
/**
 * Tests for WP_Mail.
 * Tests the regex of the message replacement code using test data for logged emails.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\wp_mail;

/**
 * Class WP_Mail_Test
 *
 * WordPress automatically swaps out PHPMailer with MockPHPMailer during tests.
 *
 * get_site_url() returns http://example.org in tests.
 *
 * @see WP_Mail
 * @see MockPHPMailer
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class WP_Mail_Test extends \Codeception\TestCase\WPTestCase {

	public function _setUp() {
		parent::_setUp();

		add_filter(
			'site_url',
			function( $url, $path, $scheme, $blog_id ) {
				return str_replace( 'localhost', 'example.org', $url );
			},
			10,
			4
		);

	}


	/**
	 * A straightforward test where the user exists and there is a link
	 * to the current domain which should be augmented with a password.
	 */
	public function test_simple_success() {

		$this->factory->user->create(
			array(
				'user_email' => 'brianhenryie@gmail.com',
			)
		);

		$to      = 'brianhenryie@gmail.com';
		$subject = 'subject';
		$message = 'brian http://example.org brian';

		wp_mail( $to, $subject, $message );

		/** MockPHPMailer */
		global $phpmailer;

		$phpmailer_message = $phpmailer->Body;

		// Confirm the function !did! change the message text.
		$this->assertNotEquals( $message, $phpmailer_message );

		// brian http://example.org?autologin=5~n0KEljY4KG4h brian.
		// brian http://example.org\?autologin=\d+~\w+ brian.

		$expected_regex = '/brian http:\/\/example.org\?autologin=\d+~\w+ brian/';

		$this->assertRegExp( $expected_regex, $phpmailer_message );

	}

	/**
	 * Test overriding not adding passwords to emails to admins.
	 */
	public function test_admin_enable_with_filter() {

		$this->factory->user->create(
			array(
				'user_email' => 'brianhenryie@gmail.com',
				'role'       => 'administrator',
			)
		);

		$to      = 'brianhenryie@gmail.com';
		$subject = 'subject';
		$message = 'brian http://example.org brian';

		/**
		 * This could normally be achieved with:
		 * `add_filter( 'autologin_urls_for_users', '__return_true' );`
		 * but we want to remove the filter afterwards.
		 *
		 * @param bool $should_add_password Variable to change to true if the urls should log in admin users.
		 * @param \WP_User $user The WordPress user the email is being sent to.
		 * @param array $wp_mail_args The array of values wp_mail() functions uses: subject, message etc.
		 *
		 * @return bool
		 */
		$should_add_password_filter = function ( $should_add_password, $user, $wp_mail_args ) {
			return true;
		};

		add_filter( 'autologin_urls_for_users', $should_add_password_filter, 10, 3 );

		wp_mail( $to, $subject, $message );

		/** MockPHPMailer */
		global $phpmailer;

		$phpmailer_message = $phpmailer->Body;

		// Confirm the function !did! change the message text.
		$this->assertNotEquals( $message, $phpmailer_message );

		// brian http://example.org?autologin=5~n0KEljY4KG4h brian.
		// brian http://example.org\?autologin=\d+~\w+ brian.

		$expected_regex = '/brian http:\/\/example.org\?autologin=\d+~\w+ brian/';

		$this->assertRegExp( $expected_regex, $phpmailer_message );

		// To be sure, to be sure.
		remove_filter( 'autologin_urls_for_admins', $should_add_password_filter, 10 );
	}


}
