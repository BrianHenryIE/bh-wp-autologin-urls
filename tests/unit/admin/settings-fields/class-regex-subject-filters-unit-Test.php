<?php
/**
 * Tests for Regex Subject Filters UI field.
 *
 * @see Regex_Subject_Filters
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin\Settings_Fields;

use BrianHenryIE\WP_Autologin_URLs\API\Settings;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;

/**
 * Class Regex_Subject_Filters_Test
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class Regex_Subject_Filters_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

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
	 * Test the HTML output for the text field. -- when  there are none in the database.
	 *
	 * {"uid":"bh_wp_autologin_urls_subject_filter_regex_dictionary","label":"Regex subject filters:","type":"regex-dictionary-list","supplemental":"Take care to include leading and trailing \/ and use ^ and $ as appropriate.","default":[],"sanitize_callback":[{},"validation_callback_regex_filter_list"]}
	 *
	 * Uses DOMDocument to test the text output, check the HTML elements match those whitelisted
	 * by wp_kses and confirm the id is correct.
	 */
	public function test_print_element() {

		$disallowed_subjects_regex_dictionary = array();

		$settings_mock = $this->createMock( Settings_Interface::class );
		$settings_mock->method( 'get_disallowed_subjects_regex_dictionary' )->willReturn( $disallowed_subjects_regex_dictionary );

		$sut = new Regex_Subject_Filters( 'settings_page', $settings_mock );

		$args = array(
			'helper'       => 'Emails whose subjects match these regex patterns will not have autologin codes added.',
			'supplemental' => 'Take care to include leading and trailing / and use ^ and $ as appropriate. Use phpliveregex.comto test.',
			'default'      => array(),
		);

		// Return the HTML passed through wp_kses.
		\WP_Mock::userFunction(
			'wp_kses',
			array(
				'return_arg' => 0,
			)
		);

		ob_start();

		$sut->print_field_callback( $args );

		$output = ob_get_clean();

		$dom = new \DOMDocument();

		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		@$dom->loadHtml( mb_convert_encoding( $output, 'HTML-ENTITIES', 'UTF-8' ) );

		$nodes = $dom->childNodes;

		// 0 is DOCTYPE, 1 is HTML
		$html = $nodes->item( 1 );
		$body = $html->childNodes->item( 0 );

		// There should be two text inputs. One for a new regex and one for its subject.
		$inputs = $body->getElementsByTagName( 'input' );
		$this->assertEquals( 2, $inputs->length );

		$regex_input   = $inputs->item( 0 );
		$subject_input = $inputs->item( 1 );

		$regex_input_type = $regex_input->getAttribute( 'type' );
		$this->assertEquals( 'text', $regex_input_type );

		$subject_input_type = $regex_input->getAttribute( 'type' );
		$this->assertEquals( 'text', $subject_input_type );

		$body_text = $body->textContent;
		$this->assertEquals( $args['helper'] . $args['supplemental'], $body_text );

		// Find the name by removing the arrays at the end.
		$name = preg_replace( '/(.*)\[\d+\]\[.*\]/', '$1', $regex_input->getAttribute( 'name' ) );
		$this->assertEquals( Settings::SUBJECT_FILTER_REGEX_DICTIONARY, $name );

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

		$sut = new Regex_Subject_Filters( 'settings_page', $settings_mock );

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

		$sut = new Regex_Subject_Filters( 'settings_page', $settings_mock );

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

		$sut = new Regex_Subject_Filters( 'settings_page', $settings_mock );

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

		$settings_mock->shouldReceive( 'get_disallowed_subjects_regex_dictionary' )->andReturn( $expected );

		$sut = new Regex_Subject_Filters( 'settings_page', $settings_mock );

		$actual = $sut->sanitize_callback( $data );

		$this->assertEquals( $expected, $actual );

	}
}
