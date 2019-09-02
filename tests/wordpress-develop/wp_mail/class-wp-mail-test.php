<?php
/**
 * Tests for WP_Mail.
 * Tests the regex of the message replacement code using test data for logged emails.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\wp_mail;

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
class WP_Mail_Test extends \WP_UnitTestCase {

	/**
	 * Verify when the email recepient does not have an account on this
	 * site, the email remains unchanged.
	 */
	public function test_user_does_not_exist() {

		$to      = 'brianhenryie@gmail.com';
		$subject = 'subject';
		$message = 'http://example.org';

		wp_mail( $to, $subject, $message );

		/** MockPHPMailer */
		global $phpmailer;

		$phpmailer_message = $phpmailer->Body;

		// Confirm the function did not change the message text.
		$this->assertEquals( $message, $phpmailer_message );

	}

	/**
	 * Pass the user account check but don't need any edits to the message.
	 */
	public function test_site_url_not_in_message() {

		$this->factory->user->create(
			array(
				'user_email' => 'brianhenryie@gmail.com',
			)
		);

		$to      = 'brianhenryie@gmail.com';
		$subject = 'subject';
		$message = 'nothing important';

		wp_mail( $to, $subject, $message );

		/** MockPHPMailer */
		global $phpmailer;

		$phpmailer_message = $phpmailer->Body;

		// Confirm the function did not change the message text.
		$this->assertEquals( $message, $phpmailer_message );

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
	 * Test that passwords do not get added to links when the user is an admin.
	 * A mild security precaution which can be overridden.
	 */
	public function test_admin_user() {

		$this->factory->user->create(
			array(
				'user_email' => 'brianhenryie@gmail.com',
				'role'       => 'administrator',
			)
		);

		$to      = 'brianhenryie@gmail.com';
		$subject = 'subject';
		$message = 'brian http://example.org brian';

		wp_mail( $to, $subject, $message );

		/** MockPHPMailer */
		global $phpmailer;

		$phpmailer_message = $phpmailer->Body;

		// Confirm the function did not change the message text.
		$this->assertEquals( $message, $phpmailer_message );

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
		$should_add_password_filter = function( $should_add_password, $user, $wp_mail_args ) {
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


	/**
	 * Test disable adding autologin code with regex filter.
	 */
	public function test_subject_disable_with_filter() {

		$this->factory->user->create(
			array(
				'user_email' => 'brianhenryie@gmail.com',
			)
		);

		$to      = 'brianhenryie@gmail.com';
		$subject = 'subject123';
		$message = 'brian http://example.org brian';

		/**
		 * Filter the disallowed subjects regexes and add one to confirm it works.
		 *
		 * @param array    $disallowed_subjects_regex_array  An array of regex patterns to exclude email subjects from having autologin links added.
		 * @param \WP_User  $user                             The WordPress user the email is being sent to.
		 * @param array    $wp_mail_args                     The array of values wp_mail() functions uses: subject, message etc.
		 *
		 * @return array
		 */
		$autologin_urls_disallowed_subjects_regexes_filter = function( $disallowed_subjects_regex_array, $user, $wp_mail_args ) {
			return array( '/^subject\d{3}$/' );
		};

		add_filter( 'autologin_urls_disallowed_subject_regexes', $autologin_urls_disallowed_subjects_regexes_filter, 10, 3 );

		wp_mail( $to, $subject, $message );

		/** MockPHPMailer */
		global $phpmailer;

		$phpmailer_message = $phpmailer->Body;

		// Confirm the function !did! change the message text.
		$this->assertEquals( $message, $phpmailer_message );

		// To be sure, to be sure.
		remove_filter( 'autologin_urls_for_admins', $autologin_urls_disallowed_subjects_regexes_filter, 10 );
	}

	/**
	 * This method reads the testdata/unchanged directory, tests the
	 * add_autologin_to_messages() method to ensure the messages in
	 * that directory are excluded by the default regex filters.
	 */
	public function test_default_regex_exclusion_filters() {

		$user_id = $this->factory->user->create(
			array(
				'user_email' => 'brianhenryie@gmail.com',
			)
		);

		$to      = 'brianhenryie@gmail.com';


		global $project_root_dir;

		$unchanged_data_path   = $project_root_dir . '/tests/testdata/unchanged/';

		$files = array_diff( scandir( $unchanged_data_path ), array( '.', '..' ) );

		/** MockPHPMailer */
		global $phpmailer;

		foreach ( $files as $file ) {

			$subject = pathinfo($file, PATHINFO_FILENAME);

			// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$message = file_get_contents( $unchanged_data_path . $file );

			wp_mail( $to, $subject, $message );

			$phpmailer_message = $phpmailer->Body;

			// Confirm the function did not change the message text.
			$this->assertEquals( $message, $phpmailer_message );

		}
	}
}
