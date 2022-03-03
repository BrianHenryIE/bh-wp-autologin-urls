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
 * Class Settings_Test
 */
class Settings_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Verifies the getter for get_expiry_age.
	 */
	public function test_get_expiry_age_setting_getter() {

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
	 */
	public function test_admin_setting_getter() {

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
	 */
	public function test_get_admin_setting_default_false() {

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
	 */
	public function test_get_admin_setting_default_false_bad_option() {

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
	 */
	public function test_subject_regex_filter_array_getter() {

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
	 */
	public function test_subject_regex_filter_dictionary_getter() {

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
	 */
	public function test_subject_regex_filter_bad_db() {

		// \WP_Mock::userFunction(
		// 'get_option',
		// array(
		// 'args'   => array( 'bh_wp_autologin_urls_seconds_until_expiry', 604800 ),
		// 'times'  => 1,
		// 'return' => 604800,
		// )
		// );

		// \WP_Mock::userFunction(
		// 'get_option',
		// array(
		// 'args'  => array( 'bh_wp_autologin_urls_is_admin_enabled', 'admin_is_not_enabled' ),
		// 'times' => 1,
		// )
		// );

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh_wp_autologin_urls_subject_filter_regex_dictionary', '*' ),
				'times'  => 1,
				'return' => 'corruption!invalidregex',
			)
		);

		$settings = new Settings();

		$result = $settings->get_disallowed_subjects_regex_array();

		$this->assertIsArray( $result );
	}

}
