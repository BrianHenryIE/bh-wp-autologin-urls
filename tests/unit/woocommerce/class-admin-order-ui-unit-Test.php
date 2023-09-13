<?php

namespace BrianHenryIE\WP_Autologin_URLs\WooCommerce;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\WooCommerce\Admin_Order_UI
 * phpcs:disable Squiz.Commenting.VariableComment.Missing
 */
class Admin_Order_UI_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}


	/**
	 * @covers ::remove_arrow_from_link_text
	 */
	public function test_remove_arrow_from_link_text(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );

		$sut = new Admin_Order_UI( $api, $settings );

		$result = $sut->remove_arrow_from_link_text( 'Customer payment page &rarr;', 'Customer payment page &rarr;', 'woocommerce' );

		$this->assertEquals( 'Customer payment page ', $result );
	}

	/**
	 * @covers ::remove_arrow_from_link_text
	 */
	public function test_remove_arrow_from_link_text_translated(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );

		$sut = new Admin_Order_UI( $api, $settings );

		// Norweigan.
		$result = $sut->remove_arrow_from_link_text( 'Kunde betalingsside &rarr;', 'Customer payment page &rarr;', 'woocommerce' );

		$this->assertEquals( 'Kunde betalingsside ', $result );
	}


	/**
	 * @covers ::remove_arrow_from_link_text
	 */
	public function test_remove_arrow_from_link_different_arrow(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );

		$sut = new Admin_Order_UI( $api, $settings );

		// An unlikely scenario where another sentence was translated to match the one we want to manipulate..
		$result = $sut->remove_arrow_from_link_text( 'Customer payment page &rarr;', 'Customer payment &rarr;', 'woocommerce' );

		$this->assertEquals( 'Customer payment page &rarr;', $result );
	}

	/**
	 * @covers ::enqueue_styles
	 * @covers ::is_on_shop_order_edit_screen
	 */
	public function test_enqueue_styles(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_version' => Expected::once(
					function () {
						return '1.4.0'; }
				),
			)
		);

		$sut = new Admin_Order_UI( $api, $settings );

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			array(
				'args'  => array(
					'bh-wp-autologin-urls-woocommerce-admin',
					\WP_Mock\Functions::type( 'string' ),
					array(),
					'1.4.0',
					'all',
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'times'  => 1,
				'return' => '',
			)
		);

		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => new class() {
					public $id = 'shop_order';
				},
			)
		);

		$GLOBALS['action'] = 'edit';

		$sut->enqueue_styles();
	}

	/**
	 * @covers ::enqueue_script
	 * @covers ::is_on_shop_order_edit_screen
	 */
	public function test_enqueue_script(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_version' => Expected::once(
					function () {
						return '1.4.0'; }
				),
			)
		);

		$sut = new Admin_Order_UI( $api, $settings );

		\WP_Mock::userFunction(
			'wp_enqueue_script',
			array(
				'args'  => array(
					'bh-wp-autologin-urls-woocommerce-admin',
					\WP_Mock\Functions::type( 'string' ),
					array( 'jquery' ),
					'1.4.0',
					true,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'times'  => 1,
				'return' => '',
			)
		);

		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => new class() {
					public $id = 'shop_order';
				},
			)
		);

		$GLOBALS['action'] = 'edit';

		$sut->enqueue_script();
	}


	/**
	 * @covers ::is_on_shop_order_edit_screen
	 * @covers ::enqueue_styles
	 */
	public function test_is_on_shop_order_edit_screen_not_shop_order(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );

		$sut = new Admin_Order_UI( $api, $settings );

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			array(
				'times' => 0,
			)
		);

		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'times' => 0,
			)
		);

		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => new class() {
					public $id = 'not_shop_order';
				},
			)
		);

		$GLOBALS['action'] = 'edit';

		$sut->enqueue_styles();
	}

	/**
	 * @covers ::is_on_shop_order_edit_screen
	 * @covers ::enqueue_script
	 */
	public function test_is_on_shop_order_edit_screen_not_action(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );

		$sut = new Admin_Order_UI( $api, $settings );

		\WP_Mock::userFunction(
			'wp_enqueue_script',
			array(
				'times' => 0,
			)
		);

		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'times' => 0,
			)
		);

		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'times'  => 1,
				'return' => new class() {
					public $id = 'shop_order';
				},
			)
		);

		$GLOBALS['action'] = 'not_edit';

		$sut->enqueue_script();
	}
}
