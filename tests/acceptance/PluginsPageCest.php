<?php 

class PluginsPageCest
{
    public function _before(AcceptanceTester $I)
    {
    }


	/**
	 *
	 * @param AcceptanceTester $I
	 */
    public function testPluginsPageForName(AcceptanceTester $I) {

    	$I->loginAsAdmin();

    	$I->amOnPluginsPage();

    	$I->canSee('BH WP Autologin URLs' );
    }

}
