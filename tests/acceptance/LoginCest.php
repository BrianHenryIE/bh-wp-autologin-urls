<?php 

class LoginCest
{
    public function _before(AcceptanceTester $I)
    {
    }


	/**
	 *
	 * @param AcceptanceTester $I
	 */
    public function testBasicLogin(AcceptanceTester $I) {

	    $I->loginAsAdmin();
	    $I->amOnAdminPage('user-edit.php?user_id=2');

	    $url_with_password = $I->grabValueFrom('#autologin-url');

	    $I->amOnPage('/wp-login.php?action=logout');
	    $I->click('log out');
	    $I->see('You are now logged out.');

	    $I->amOnPage( $url_with_password );

	    $I->amOnPage( '/?p=10' );

	    $I->see('Hello bob' );

    }


	/**
	 * The option exists to 'bounce' users through wp.login.php?redirect_to for cases where the log in action
	 * seems to be running too late and pages not rendering as logged in until refresh.
	 *
	 * @param AcceptanceTester $I
	 */
	public function testLoginViaWpLogin(AcceptanceTester $I) {

		$I->loginAsAdmin();

		$I->amOnAdminPage('options-general.php?page=bh-wp-autologin-urls');

		$I->checkOption('#bh_wp_autologin_urls_should_use_wp_login');

		$I->click('Save Changes');

		$I->amOnAdminPage('user-edit.php?user_id=2');

		$url_with_password = $I->grabValueFrom('#autologin-url');

		// TODO: This is working, but how do $I->assert ??
// http://localhost:8080/bh-wp-autologin-urls/wp-login.php?redirect_to=http%3A%2F%2Flocalhost%3A8080%2Fbh-wp-autologin-urls%2F&autologin=2~Jd9rpCHhzXz51
//		echo( str_contains( $url_with_password, 'wp-login.php' ) );
//		$I->
//		str_contains( $url_with_password, 'wp-login.php' );

		$I->amOnPage('/wp-login.php?action=logout');
		$I->click('log out');
		$I->see('You are now logged out.');

		$I->amOnPage( $url_with_password );

		$I->amOnPage( '/?p=10' );

		$I->see('Hello bob' );

	}

	/**
	 * With the wp-login redirection, if the user was already logged in they were just left there!
	 *
	 * @param AcceptanceTester $I
	 */
	public function testAlreadyLoggedInOnWpLogin(AcceptanceTester $I) {

		$I->loginAsAdmin();

		$I->amOnAdminPage('options-general.php?page=bh-wp-autologin-urls');

		$I->checkOption('#bh_wp_autologin_urls_should_use_wp_login');

		$I->click('Save Changes');

		$I->amOnAdminPage('user-edit.php?user_id=2');

		$url_with_password = $I->grabValueFrom('#autologin-url');

		$I->amOnPage('/wp-login.php?action=logout');
		$I->click('log out');
		$I->see('You are now logged out.');

		// wp user update bob --user_pass="password"
		$I->loginAs('bob','password');

		// Update the url so it's the my-account page.
		$url_with_password = str_replace( '&autologin', '%3Fp%3D10&autologin', $url_with_password);

		$I->amOnPage( $url_with_password );
//
//		$I->amOnPage( '/?p=10' );

		$I->see('Hello bob' );

	}
}
