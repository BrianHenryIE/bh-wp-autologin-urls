<?php

namespace BrianHenryIE\WP_Autologin_URLs\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\Login;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\WooCommerce\Checkout
 */
class Checkout_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * When there is a past order associated with that email address,
	 * use its billing details in the checkout fields.
	 *
	 * @covers ::prefill_checkout_fields
	 */
	public function test_woocommerce_order(): void {

		$this->markTestSkipped( 'This functionality disabled because it was inadvertently creating WP_User objects and sending emails!' );

		$existing_order = new \WC_Order();
		$existing_order->set_billing_email( 'test@example.org' );
		$existing_order->set_billing_city( 'Sacramento' );
		$existing_order->save();

		$logger = new ColorLogger();

		$user_info = array(
			'email'      => 'test@example.org',
			'first_name' => 'Brian',
			'last_name'  => 'Henry',
		);

		$sut = new Checkout( $logger );

		$sut->prefill_checkout_fields( $user_info );

		do_action( 'woocommerce_after_register_post_type' );

		$this->assertSame( 'Sacramento', WC()->customer->get_billing_city() );
	}


	/**
	 * When there is no past order associated with that email address,
	 * use the first and last name passed from the mailing list plugin.
	 *
	 * @covers ::prefill_checkout_fields
	 */
	public function test_woocommerce_name(): void {

		$this->markTestSkipped( 'This functionality disabled because it was inadvertently creating WP_User objects and sending emails!' );

		$logger = new ColorLogger();

		$user_info = array(
			'email'      => 'test@example.org',
			'first_name' => 'Brian',
			'last_name'  => 'Henry',
		);

		$sut = new Checkout( $logger );

		$sut->prefill_checkout_fields( $user_info );

		$this->assertSame( 'Brian', WC()->customer->get_first_name() );
	}
}
