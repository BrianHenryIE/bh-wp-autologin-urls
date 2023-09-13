<?php

class AdminProfilePageCest {

	/**
	 * Check the title of the new section has been added / is visible.
	 *
	 * @param AcceptanceTester $I The Codeception tester.
	 */
	public function testPasswordIsAvailableOnProfilePage( AcceptanceTester $I ) {

		$I->loginAsAdmin();

		$I->amOnAdminPage( 'user-edit.php?user_id=2' );

		$I->see( 'Single-use login URL' );
	}
}
