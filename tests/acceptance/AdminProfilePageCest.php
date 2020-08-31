<?php 

class AdminProfilePageCest
{
    public function _before(AcceptanceTester $I)
    {
    }


	/**
	 *
	 * @param AcceptanceTester $I
	 */
    public function testPasswordIsAvailableOnProfilePage(AcceptanceTester $I) {

    	$I->loginAsAdmin();

	    $I->amOnAdminPage('user-edit.php?user_id=2');

	    $I->see('Single-use login URL');

    }

}
