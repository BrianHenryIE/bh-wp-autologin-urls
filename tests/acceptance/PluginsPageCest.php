<?php

class PluginsPageCest {

	public function _before( AcceptanceTester $I ) {

		$I->loginAsAdmin();

		$I->amOnPluginsPage();
	}

	/**
	 * Check the plugin name.
	 *
	 * @param AcceptanceTester $I The Codeception tester.
	 */
	public function testPluginsPageForName( AcceptanceTester $I ) {

		$I->canSee( 'Autologin URLs' );
	}

	/**
	 * Verify the Settings link is present and correct.
	 *
	 * @param AcceptanceTester $I The Codeception tester.
	 */
	public function testSettingsLink( AcceptanceTester $I ) {

		$I->canSeeLink( 'Settings', $_ENV['TEST_SITE_WP_URL'] . 'wp-admin/options-general.php?page=bh-wp-autologin-urls' );

	}

	/**
	 * Verify the GitHub link is present and correct.
	 *
	 * @param AcceptanceTester $I The Codeception tester.
	 */
	public function testGithubLink( AcceptanceTester $I ) {

		$I->canSeeLink( 'GitHub', 'https://github.com/BrianHenryIE/bh-wp-autologin-urls' );
	}



}
