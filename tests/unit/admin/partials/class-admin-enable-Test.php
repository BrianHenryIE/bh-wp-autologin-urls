<?php
/**
 * Tests for Admin_Enable
 *
 * @see Admin_Enable
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\admin\partials;

use BrianHenryIE\WP_Autologin_URLs\api\Settings;
use BrianHenryIE\WP_Autologin_URLs\api\Settings_Interface;

/**
 * Class Admin_Enable_Test
 *
 * Tests the Settings API element for enabling the plugin for admins.
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class Admin_Enable_Test extends \Codeception\Test\Unit {

	protected function _before() {

		\WP_Mock::setUp();
	}


	/**
	 * The plugin name. Unlikely to change.
	 *
	 * @var string Plugin name.
	 */
	private $plugin_name = 'bh-wp-autologin-urls';

	/**
	 * Test the checkbox is not checked when Settings returns false.
	 */
	public function test_box_not_checked() {

		$settings_mock = $this->createMock( Settings_Interface::class );
		$settings_mock->method( 'get_add_autologin_for_admins_is_enabled' )->willReturn( false );

		\WP_Mock::userFunction(
			'wp_kses'
		);

		$sut = new Admin_Enable( 'settings_page', $settings_mock );

		$args = array(
			'helper'       => 'When enabled, emails to administrators <i>will</i> contain autologin URLs.',
			'supplemental' => 'default: false',
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

		$input = $body->getElementsByTagName( 'input' )->item( 0 );

		$checked = $input->getAttribute( 'checked' );

		$this->assertNotEquals( 'checked', $checked );
	}

	/**
	 * The plugin version, matching the version these tests were written against.
	 *
	 * @var string Plugin version.
	 */
	private $version = '1.0.0';

	/**
	 * Test the HTML output for the checkbox.
	 *
	 * Uses DOMDocument to test the text output, the box is checked and the id is correct.
	 */
	public function test_print_element() {

		$settings_mock = $this->createMock( Settings_Interface::class );
		$settings_mock->method( 'get_add_autologin_for_admins_is_enabled' )->willReturn( true );

		\WP_Mock::userFunction(
			'wp_kses',
			array(
				'return' => 'When enabled, emails to administrators will contain autologin URLs.',
			)
		);

		$sut = new Admin_Enable( 'settings_page', $settings_mock );

		$args = array(
			'helper'       => 'When enabled, emails to administrators <i>will</i> contain autologin URLs.',
			'supplemental' => 'default: false',
		);

		ob_start();

		$sut->print_field_callback( $args );

		$output = ob_get_clean();

		$dom = new \DOMDocument();

		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		@$dom->loadHtml( mb_convert_encoding( $output, 'HTML-ENTITIES', 'UTF-8' ) );

		$d = $dom->saveHTML();

		$nodes = $dom->childNodes;

		// 0 is DOCTYPE, 1 is HTML.
		$html = $nodes->item( 1 );
		$body = $html->childNodes->item( 0 );

		$fieldset  = $body->childNodes->item( 0 );
		$paragraph = $body->childNodes->item( 1 );

		$fieldset_text = $fieldset->textContent;
		$this->assertEquals( 'When enabled, emails to administrators will contain autologin URLs.', $fieldset_text );

		$input = $fieldset->getElementsByTagName( 'input' )->item( 0 );

		$checked = $input->getAttribute( 'checked' );
		$this->assertEquals( 'checked', $checked );

		$id = $input->getAttribute( 'id' );
		$this->assertEquals( Settings::ADMIN_ENABLED, $id );

		$paragraph_text = $paragraph->nodeValue;
		$this->assertEquals( 'default: false', $paragraph_text );
	}

	/**
	 * Test straightforward checked box.
	 */
	public function test_validation_callback_checked() {

		$data = 'admin_is_enabled';

		$settings_mock = $this->createMock( Settings_Interface::class );

		$sut = new Admin_Enable( 'settings_page', $settings_mock );

		$expected = 'admin_is_enabled';

		$actual = $sut->sanitize_callback( $data );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test straightforward un-checked box.
	 */
	public function test_validation_callback_unchecked() {

		$data = null;

		$settings_mock = $this->createMock( Settings_Interface::class );

		$sut = new Admin_Enable( 'settings_page', $settings_mock );

		$expected = 'admin_is_not_enabled';

		$actual = $sut->sanitize_callback( $data );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test bad data returns the saved value..
	 */
	public function test_validation_callback_bad_value() {

		$data = 'a bad value';

		$settings_mock = \Mockery::mock( 'Settings_Interface' );

		$settings_mock->shouldReceive( 'get_add_autologin_for_admins_is_enabled' )
					  ->andReturn( false );

		$sut = new Admin_Enable( 'settings_page', $settings_mock );

		$expected = 'admin_is_not_enabled';

		$actual = $sut->sanitize_callback( $data );

		$this->assertEquals( $expected, $actual );

	}
}
