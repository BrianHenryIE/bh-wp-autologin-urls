<?php
/**
 * Tests for Expiry_Age
 *
 * @see Expiry_Age
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\admin\partials;

use BrianHenryIE\WP_Autologin_URLs\api\Settings;
use BrianHenryIE\WP_Autologin_URLs\api\Settings_Interface;

/**
 * Class Expiry_Age_Test
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class Expiry_Age_Test extends \Codeception\Test\Unit {

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
	 * Test the HTML output for the text field.
	 *
	 * Uses DOMDocument to test the text output, the box is checked and the id is correct.
	 */
	public function test_print_element() {

		$settings_mock = $this->createMock( Settings_Interface::class );
		$settings_mock->method( 'get_expiry_age' )->willReturn( 12345 );

		$sut = new Expiry_Age( 'settings_page', $settings_mock );

		$args = array(
			'helper'       => 'Number of seconds until each code expires.',
			'supplemental' => 'default: 604800 (one week)',
			'default'      => 604800,
			'placeholder'  => '',
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

		$input                 = $body->childNodes->item( 0 );
		$helper_span           = $body->childNodes->item( 1 );
		$description_paragraph = $body->childNodes->item( 2 );

		$type = $input->getAttribute( 'type' );
		$this->assertEquals( 'text', $type );

		$id = $input->getAttribute( 'id' );
		$this->assertEquals( 'bh_wp_autologin_urls_seconds_until_expiry', $id );

		$value = $input->getAttribute( 'value' );
		$this->assertEquals( 12345, $value );

		$this->assertEquals( 'Number of seconds until each code expires.', $helper_span->nodeValue );
		$this->assertEquals( 'default: 604800 (one week)', $description_paragraph->nodeValue );
	}

	/**
	 * Test straightforward number.
	 */
	public function test_validation_callback_success() {

		$data = '123456';

		$settings_mock = $this->createMock( Settings_Interface::class );

		$sut = new Expiry_Age( 'settings_page', $settings_mock );

		$expected = 123456;

		$actual = $sut->sanitize_callback( $data );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test number with characters. The sanitizer should strip all no numeric characters.
	 */
	public function test_validation_callback_with_characters() {

		$data = '123456qwerty';

		$settings_mock = $this->createMock( Settings_Interface::class );

		$sut = new Expiry_Age( 'settings_page', $settings_mock );

		$expected = 123456;

		$actual = $sut->sanitize_callback( $data );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test empty returns previously saved value
	 */
	public function test_validation_callback_empty() {

		$data = '';

		$settings_mock = \Mockery::mock( 'Settings_Interface' );

		$settings_mock->shouldReceive( 'get_expiry_age' )
					  ->andReturn( 123456 );

		$sut = new Expiry_Age( 'settings_page', $settings_mock );

		\WP_Mock::userFunction(
			'add_settings_error'
		);

		$expected = 123456;

		$actual = $sut->sanitize_callback( $data );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test arithmetic.
	 */
	public function test_validation_callback_multiply() {

		$data = '60*60*24*7';

		$settings_mock = $this->createMock( Settings_Interface::class );

		$sut = new Expiry_Age( 'settings_page', $settings_mock );

		$expected = 604800;

		$actual = $sut->sanitize_callback( $data );

		$this->assertEquals( $expected, $actual );
	}



}
