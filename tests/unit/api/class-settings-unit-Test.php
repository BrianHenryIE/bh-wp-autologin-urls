<?php
/**
 * Tests for Settings, using the WP_Mock framework.
 *
 * @see Settings_Interface
 * @see Settings
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\API;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\API\Settings
 */
class Settings_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Verifies the getter for get_expiry_age.
	 *
	 * @covers ::get_expiry_age
	 */
	public function test_get_expiry_age_setting_getter(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh_wp_autologin_urls_seconds_until_expiry', 604800 ),
				'times'  => 1,
				'return' => 123,
			)
		);

		$settings = new Settings();

		$settings->get_expiry_age();
	}


	/**
	 * Verifies the getter for is_admin_enabled.
	 *
	 * @covers ::get_add_autologin_for_admins_is_enabled
	 */
	public function test_admin_setting_getter(): void {

		$should_be = 'admin_is_enabled';

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh_wp_autologin_urls_is_admin_enabled', 'admin_is_not_enabled' ),
				'times'  => 1,
				'return' => $should_be,
			)
		);

		\WP_Mock::userFunction(
			'get_option'
		);

		$settings = new Settings();

		$this->assertTrue( $settings->get_add_autologin_for_admins_is_enabled() );
	}

	/**
	 * Verifies the constructor correctly defaults the admin enabled setting to false,
	 * by returning null in the get_option call.
	 *
	 * @covers ::get_add_autologin_for_admins_is_enabled
	 */
	public function test_get_admin_setting_default_false(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh_wp_autologin_urls_is_admin_enabled', 'admin_is_not_enabled' ),
				'times'  => 1,
				'return' => null,
			)
		);

		\WP_Mock::userFunction(
			'get_option'
		);

		$settings = new Settings();

		$this->assertFalse( $settings->get_add_autologin_for_admins_is_enabled() );
	}

	/**
	 * Verifies the constructor correctly defaults the admin enabled setting to false
	 * when the wp_options call returns nonsense.
	 *
	 * @covers ::get_add_autologin_for_admins_is_enabled
	 */
	public function test_get_admin_setting_default_false_bad_option(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh_wp_autologin_urls_is_admin_enabled', 'admin_is_not_enabled' ),
				'times'  => 1,
				'return' => 'wtf',
			)
		);

		\WP_Mock::userFunction(
			'get_option'
		);

		$settings = new Settings();

		$this->assertFalse( $settings->get_add_autologin_for_admins_is_enabled() );
	}


	/**
	 * Verifies on first construction, the filter is an array and the getter works.
	 *
	 * @covers ::get_disallowed_subjects_regex_array
	 */
	public function test_subject_regex_filter_array_getter(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh_wp_autologin_urls_subject_filter_regex_dictionary', '*' ),
				'times'  => 1,
				'return' => null,
			)
		);

		$settings = new Settings();

		$this->assertTrue( is_array( $settings->get_disallowed_subjects_regex_array() ) );
	}


	/**
	 * Verifies on first construction, the regex filter getter works.
	 *
	 * @covers ::get_disallowed_subjects_regex_dictionary
	 */
	public function test_subject_regex_filter_dictionary_getter(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh_wp_autologin_urls_subject_filter_regex_dictionary', '*' ),
				'times'  => 1,
				'return' => null,
			)
		);

		$settings = new Settings();

		$this->assertTrue( is_array( $settings->get_disallowed_subjects_regex_dictionary() ) );
	}


	/**
	 * Verify the regex filter will be an array, even if the database is edited directly and corrupted.
	 *
	 * @covers ::get_disallowed_subjects_regex_array
	 */
	public function test_subject_regex_filter_bad_db(): void {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh_wp_autologin_urls_subject_filter_regex_dictionary', '*' ),
				'times'  => 1,
				'return' => 'corruption!invalidregex', // This should be an array.
			)
		);

		$settings = new Settings();

		$result = $settings->get_disallowed_subjects_regex_array();

		$this->assertIsArray( $result );
	}

	/**
	 * Verify the version is a semver string.
	 *
	 * @covers ::get_plugin_version
	 */
	public function test_get_plugin_version(): void {

		$settings = new Settings();

		$result = $settings->get_plugin_version();

		$match = 1 === preg_match( '/^\d+\.\d+\.\d+$/', $result );

		$this->assertTrue( $match );
	}

	/**
	 * @covers ::get_plugin_name
	 */
	public function test_get_plugin_name(): void {

		$settings = new Settings();

		$result = $settings->get_plugin_name();

		$this->assertEquals( 'Autologin URLs', $result );
	}

	/**
	 * @covers ::get_plugin_basename
	 */
	public function test_get_plugin_basename(): void {

		$settings = new Settings();

		$result = $settings->get_plugin_basename();

		$this->assertEquals( 'bh-wp-autologin-urls/bh-wp-autologin-urls.php', $result );
	}
}
