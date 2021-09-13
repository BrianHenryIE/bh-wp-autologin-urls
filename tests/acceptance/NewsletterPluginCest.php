<?php

class NewsletterPluginCest {

	/**
	 * TODO: Does this run once per test-run or once before each individual test?
	 *
	 * @param AcceptanceTester $I
	 */
	public function _before( AcceptanceTester $I ) {

		// define( 'WP_CRON_LOCK_TIMEOUT', 1 /* second */ );

		$I->loginAsAdmin();

		$I->amOnPluginsPage();

		// Enable Newsletter plugin.

		$I->activatePlugin( 'newsletter' );

		// Configure SMTP settings from .env.secret.

		$I->amOnAdminPage( 'admin.php?page=newsletter_main_smtp' );

		$smtp_server   = $_ENV['SMTP_SERVER'];
		$tls_port      = $_ENV['SMTP_PORT'];
		$smtp_username = $_ENV['MAILTRAP_USERNAME'];
		$smtp_password = $_ENV['MAILTRAP_PASSWORD'];

		$I->submitForm(
			'#tnp-body > form',
			array(
				'act'                   => 'save',
				'options[enabled]'      => '1',
				'options[host]'         => $smtp_server,
				'options[port]'         => $tls_port,
				'options[secure]'       => 'tls',
				'options[user]'         => $smtp_username,
				'options[pass]'         => $smtp_password,
				'options[ssl_insecure]' => '0',
			)
		);

		// Set a valid sending address for email.

		$I->amOnAdminPage( 'admin.php?page=newsletter_main_main' );

		$I->submitForm(
			'#tnp-body > form',
			array(

				'act'                                => 'save',
				'options[sender_email]'              => 'bh-wp-autologin-urls@brianhenry.ie',
				'options[sender_name]'               => '',
				'options[return_path]'               => '',
				'options[reply_to]'                  => '',
				'options[page]'                      => '11',
				'options[contract_key]'              => '',
				'options[scheduler_max]'             => '100',
				'options[css_disabled]'              => '0',
				'options[css]'                       => '',
				'options[log_level]'                 => '0',
				'options[ip]'                        => '',
				'options[track]'                     => '1',
				'options[debug]'                     => '0',
				'options[content_transfer_encoding]' => '',
				'options[do_shortcodes]'             => '0',

			)
		);

		// Add our user 'bob' (userid=2) as a subscriber.

		$I->amOnAdminPage( 'admin.php?page=newsletter_users_new' );

		$I->submitForm(
			'#tnp-body > form',
			array(
				'act'            => 'save',
				'options[email]' => 'bob@example.org',
			)
		);

	}


	/**
	 * Verify we can log in via the tracking links in Newsletter plugin.
	 *
	 * Create a basic newsletter, send it via Mailtrap.io SMTP, check the Mailtrap inbox, extract the URL in the
	 * email, then visit it and verify we're logged in.
	 *
	 * @param AcceptanceTester $I
	 */
	public function testSendANewsletter( AcceptanceTester $I ) {

		// Visit the "Create newsletter" page.
		$I->amOnAdminPage( 'admin.php?page=newsletter_emails_composer' );

		// Click the create newsletter from "Raw HTML" option.
		$I->click( 'Raw HTML' );

		// The page served is actually a JS page, but we can just visit the URL we know we'll be sent to...

		// The editor page.
		$I->amOnAdminPage( 'admin.php?page=newsletter_emails_editorhtml&id=1' );

		// For handy reference, let's set the subject to the time.
		$subject = 'Email sent at ' . date( 'D, d M Y H:i:s', time() );

		// The URL we'll ultimately be visiting to test is the login function working is.
		// Should maybe generate with~: $account_page = $I->getSiteDomain() . '/?p=10';
		$account_page_link = 'http://localhost:8080/bh-wp-autologin-urls/?p=10';

		// While on the editor page, submit the form to create the newsletter.
		$I->submitForm(
			'#tnp-wrap > form',
			array(
				'act'              => 'next',
				'options[subject]' => $subject,
				'options[message]' => "<!DOCTYPE+html>\r\n<html>\r\n<head>\r\n<title>$subject</title>\r\n</head>\r\n<body>\r\n++<a+href=\"$account_page_link\">My+Account</a>\r\n</body>\r\n</html>",
			)
		);

		// Visit the individual newsletter's overview page.
		$I->amOnAdminPage( 'admin.php?page=newsletter_emails_edit&id=1' );

		// Send the newsletter.
		$I->submitForm(
			'#tnp-body > form',
			array(
				'act'                             => 'send',
				// tnp_fields[send_on]   "datetime"
				// send_on_month "10"
				// send_on_day   "14"
				// send_on_year  "2020"
				// send_on_hour  "0"
				'options[subject]'                => $subject,
				'options[options_lists_operator]' => 'or',
				'options[options_status]'         => 'C',
				'options[options_wp_users]'       => '0',
				'options[private]'                => '0',
				'options[track]'                  => '1',
				'options[message_text]'           => '',
			)
		);

		// At this point, the Newsletter plugin has scheduled its cron.

		// One could `return;` here and check via `wp shell` what jobs are scheduled.
		// Prints the "newsletter" cron job if it is scheduled to run.
		// $a = array_filter( wp_get_ready_cron_jobs(), function( $value ) { return array_key_exists( 'newsletter', $value ) ? 'newsletter' : null; } );

		// But none of these attempts to run cron work.

		// $I->amOnCronPage();
		// $I->amOnPage( '/wp-cron.php' );
		// $I->amOnPage( '/' ); // tried with define( alternative cron )
		exec( 'wget http://localhost:8080/bh-wp-autologin-urls/wordpress/wp-cron.php  &' );

		// spawn_cron();

		// As soon as this process has ended, the cron can be run.
		// with `wget http://localhost:8080/bh-wp-autologin-urls/wp-cron.php`

		// So what is happening..?

		$I->amOnPage( '/' );
		$I->amOnCronPage( 'newsletter' );

		// running
		// sleep( 100 );
		// and in `wp shell` (again)
		// $a = array_filter( wp_get_ready_cron_jobs(), function( $value ) { return array_key_exists( 'newsletter', $value ) ? 'newsletter' : null; } );
		// does not show the job
		$output = array();
		$i      = 0;
		// while($i < 100 ) {
		// exec('wget http://localhost:8080/bh-wp-autologin-urls/wp-cron.php  &', $output);
		// $I->amOnCronPage();
		// $i++;
		// }

		echo 'lol';

		// wp_cron()

		// Log out admin.
		$I->amOnPage( '/wp-login.php?action=logout' );
		$I->click( 'log out' );
		$I->see( 'You are now logged out.' );

		// Check emails.
		$I->fetchEmails();
		$I->haveEmails();
		$I->haveUnreadEmails();
		$I->openNextUnreadEmail();

		// Check it's the email we sent.
		$I->seeInOpenedEmailSubject( $subject );
		$I->seeInOpenedEmailBody( 'bh-wp-autologin-urls/?nltr=' );

		$email_body = $I->grabBodyFromEmail();

		// Expecting:
		// <body>
		// ++<a+href="http://localhost:8080/bh-wp-autologin-urls/?nltr=MTsyO2h0dHA6Ly9sb2NhbGhvc3Q6ODA4MC9iaC13cC1hdXRvbG9naW4tdXJscy8%2FcD0xMDs7ZTRhMTc1YmMzZGExMmRjM2ZiZDE0OGY0ZTg5NTU1NTk%3D">My+Account</a>
		// <img width="1" height="1" alt="" src="http://localhost:8080/bh-wp-autologin-urls/?noti=MTsyOzUzZjE4YjZmYzY1MWI0NTlhNDlkY2JjYWE3NTE2YmY0"/></body>
		// </html>

		// Get the first link from the body.
		$output_array = array();
		if ( false !== preg_match( '/href=\"(.*)"/', $email_body, $output_array ) ) {

			$url_with_newsletter_tracking = $output_array[1];

			$I->amOnPage( $url_with_newsletter_tracking );

			$I->amOnPage( '/?p=10' );

			$I->see( 'Hello bob' );

		} else {
			// fail test

			error_log( 'FAIL' );

		}

		$I->deleteAllEmails();

	}



}
