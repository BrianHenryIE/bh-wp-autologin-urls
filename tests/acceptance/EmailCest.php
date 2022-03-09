<?php
/**
 * Test the contents of the email received at mailtrap.io.
 */
class EmailCest {

	/**
	 * There should be a post already created for user bob (bob@example.com)
	 * then anyone who replies to that post should trigger a comment.
	 *
	 * @param AcceptanceTester $I Codeception tester.
	 */
	public function _before( AcceptanceTester $I ) {

		$I->loginAsAdmin();

		$I->amOnPluginsPage();
	}

	/**
	 * Activate wp-mail-smtp and trigger an email.
	 *
	 * Verify it has autologin urls added.
	 *
	 * Uses WordPress built-in comment emails.
	 *
	 * @param AcceptanceTester $I Codeception tester.
	 */
	public function testWpMailSmtp( AcceptanceTester $I ) {

		$smtp_server   = $_ENV['SMTP_SERVER'];
		$tls_port      = $_ENV['SMTP_PORT'];
		$smtp_username = $_ENV['MAILTRAP_USERNAME'];
		$smtp_password = $_ENV['MAILTRAP_PASSWORD'];

		$I->activatePlugin( 'wp-mail-smtp' );

		$I->amOnAdminPage( 'admin.php?page=wp-mail-smtp' );

		$I->submitForm(
			'.wp-mail-smtp-page-content > form',
			array(
				'wp-mail-smtp[mail][mailer]'     => 'smtp',
				'wp-mail-smtp[smtp][host]'       => $smtp_server,
				'wp-mail-smtp[smtp][encryption]' => 'tls',
				'wp-mail-smtp[smtp][port]'       => $tls_port,
				'wp-mail-smtp[smtp][user]'       => $smtp_username,
				'wp-mail-smtp[smtp][pass]'       => $smtp_password,
			)
		);

		// Fire the email.

		$I->amOnPage( '/?page_id=2' );

		$I->canSee( 'Sample Page' );

		$I->submitForm( '#commentform', array( 'comment' => 'Some text' ) );

		// Check the email.

		$I->fetchEmails();

		$I->haveEmails();
		$I->haveUnreadEmails();

		// Set the next unread email as the email to perform operations on.
		$I->openNextUnreadEmail();

		$I->seeInOpenedEmailSubject( 'Comment' );

		$I->seeInOpenedEmailBody( 'autologin=2~' );

		$I->deleteAllEmails();

	}

}
