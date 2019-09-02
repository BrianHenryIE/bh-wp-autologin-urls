<?php
/**
 * Tests for Settings_Section_Element abstract class.
 *
 * @see Settings_Section_Element_Abstract
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\admin\partials;

/**
 * Class Settings_Section_Element_Test
 *
 * @phpcs:disable Squiz.Commenting.FunctionComment.EmptyThrows
 */
class Settings_Section_Element_Test extends \WP_Mock\Tools\TestCase {

	/**
	 * The plugin name. Unlikely to change.
	 *
	 * @var string Plugin name.
	 */
	private $plugin_name = 'bh-wp-autologin-urls';

	/**
	 * The plugin version, matching the version these tests were written against.
	 *
	 * @var string Plugin version.
	 */
	private $version = '1.0.0';

	/**
	 * Confirm the add_settings_field() method calls WordPress's add_settings_field().
	 *
	 * @throws \ReflectionException
	 */
	public function test_add_settings_field() {

		$stub = $this->getMockForAbstractClass(
			Settings_Section_Element_Abstract::class,
			array(
				$this->plugin_name,
				$this->version,
				'$settings_page_slug_name',
			)
		);

		\WP_Mock::userFunction(
			'add_settings_field',
			array(
				'times' => 1,
			)
		);

		$stub->add_settings_field();

		$this->assertConditionsMet();
	}


	/**
	 * Confirm the register_setting() method calls WordPress's register_setting().
	 *
	 * @throws \ReflectionException
	 */
	public function test_register_setting() {

		$stub = $this->getMockForAbstractClass(
			Settings_Section_Element_Abstract::class,
			array(
				$this->plugin_name,
				$this->version,
				'$settings_page_slug_name',
			)
		);

		\WP_Mock::userFunction(
			'register_setting',
			array(
				'times' => 1,
			)
		);

		$stub->register_setting();

		$this->assertConditionsMet();
	}

}

