<?php
/**
 * Tests for Regex_Subject_Filters
 *
 * @see Regex_Subject_Filters
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\admin\partials;

use BH_WP_Autologin_URLs\includes\Settings_Interface;

/**
 * Class Regex_Subject_Filters_Test
 */
class Regex_Subject_Filters_Test extends \WP_Mock\Tools\TestCase {

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
	 * Test: a table is returned.
	 *
	 * {"uid":"bh_wp_autologin_urls_subject_filter_regex_dictionary","label":"Regex subject filters:","type":"regex-dictionary-list","supplemental":"Take care to include leading and trailing \/ and use ^ and $ as appropriate.","default":[],"sanitize_callback":[{},"validation_callback_regex_filter_list"]}
	 */
	public function test_print_table() {
		$this->markTestIncomplete( 'unimplemented' );
	}


	/**
	 * Tests the data POSTed to WordPress settings API is correctly restructured
	 * for saving in the database.
	 */
	public function test_validation_callback_regex_filter_list_restructure() {

		$posted = '[{"regex":"\/^myregex1$\/","note":"my subject 1"},{"regex":"\/^myregex2$\/","note":"my subject 2"},{"regex":"\/^myregex3$\/","note":"my subject 3"}]';

		$data = json_decode( $posted, true );

		$expected = array(
			'/^myregex1$/' => 'my subject 1',
			'/^myregex2$/' => 'my subject 2',
			'/^myregex3$/' => 'my subject 3',
		);

		$settings_mock = $this->createMock( Settings_Interface::class );

		$sut = new Regex_Subject_Filters( $this->plugin_name, $this->version, 'settings_page', $settings_mock );

		$actual = $sut->sanitize_callback( $data );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Tests empty regex form fields are discarded rather than saved.
	 */
	public function test_validation_callback_regex_filter_list_discard() {

		$posted = '[{"regex":"\/^myregex1$\/","note":"my subject 1"},{"regex":"\/^myregex2$\/","note":"my subject 2"},{"regex":"\/^myregex3$\/","note":"my subject 3"},{"regex":"","note":""}]';

		$data = json_decode( $posted, true );

		$expected = array(
			'/^myregex1$/' => 'my subject 1',
			'/^myregex2$/' => 'my subject 2',
			'/^myregex3$/' => 'my subject 3',
		);

		$settings_mock = $this->createMock( Settings_Interface::class );

		$sut = new Regex_Subject_Filters( $this->plugin_name, $this->version, 'settings_page', $settings_mock );

		$actual = $sut->sanitize_callback( $data );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Tests empty note form fields are filled with the regex string so
	 * they don't get treated as an empty array entry.
	 */
	public function test_validation_callback_regex_filter_list_empty_note() {

		$posted = '[{"regex":"\/^myregex1$\/","note":""},{"regex":"\/^myregex2$\/","note":""},{"regex":"\/^myregex3$\/","note":"my subject 3"},{"regex":"","note":""}]';

		$data = json_decode( $posted, true );

		$expected = array(
			'/^myregex1$/' => '/^myregex1$/',
			'/^myregex2$/' => '/^myregex2$/',
			'/^myregex3$/' => 'my subject 3',
		);

		$settings_mock = $this->createMock( Settings_Interface::class );

		$sut = new Regex_Subject_Filters( $this->plugin_name, $this->version, 'settings_page', $settings_mock );

		$actual = $sut->sanitize_callback( $data );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test bad data returns the previously saved value.
	 */
	public function test_validation_callback_regex_bad_value() {

		$data = 'a bad value';

		$expected = array( 'test-array' );

		$settings_mock = \Mockery::mock( 'Settings_Interface' );

		$settings_mock->shouldReceive( 'get_disallowed_subjects_regex_dictionary' )
			 ->andReturn( $expected );

		$sut = new Regex_Subject_Filters( $this->plugin_name, $this->version, 'settings_page', $settings_mock );

		$actual = $sut->sanitize_callback( $data );

		$this->assertEquals( $expected, $actual );

	}
}
