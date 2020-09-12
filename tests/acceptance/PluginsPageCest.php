<?php 

class PluginsPageCest
{
    public function _before(AcceptanceTester $I)
    {

	    $I->loginAsAdmin();

	    $I->amOnPluginsPage();
    }

	/**
	 *
	 * @param AcceptanceTester $I
	 */
    public function testPluginsPageForName(AcceptanceTester $I) {


    	$I->canSee('Autologin URLs' );
    }

	/**
	 *
	 * @param AcceptanceTester $I
	 */
	public function testSettingsLink(AcceptanceTester $I) {

		$I->canSeeLink('Settings', $_ENV['TEST_SITE_WP_URL'] . 'wp-admin/options-general.php?page=bh-wp-autologin-urls' );

	}

	/**
	 *
	 * @param AcceptanceTester $I
	 */
	public function testGithubLink(AcceptanceTester $I) {

		$I->canSeeLink('GitHub', 'https://github.com/BrianHenryIE/bh-wp-autologin-urls' );
	}



}
