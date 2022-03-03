<?php
/**
 * Tests for I18n.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\Includes;

/**
 * Class I18n_Test
 */
class I18n_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Basic success test.
	 */
	public function test_load_plugin_textdomain() {

		$i18n = new I18n();

		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'load_plugin_textdomain',
			array(
				'args'  => array( 'bh-wp-autologin-urls', false, \WP_Mock\Functions::type( 'string' ) ),
				'times' => 1,
			)
		);

		$i18n->load_plugin_textdomain();
	}

}
