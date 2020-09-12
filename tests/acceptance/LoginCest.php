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

}
