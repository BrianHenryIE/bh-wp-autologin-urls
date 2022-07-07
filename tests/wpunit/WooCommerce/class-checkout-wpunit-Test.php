<?php

namespace BrianHenryIE\WP_Autologin_URLs\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\Settings_Interface;
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

		$existing_order = new \WC_Order();
		$existing_order->set_billing_email( 'test@example.org' );
		$existing_order->set_billing_city( 'Sacramento' );
		$existing_order->save();

		$logger = new ColorLogger();

		$email_address = 'test@example.org';
		$user_info     = array(
			'first_name' => 'Brian',
			'last_name'  => 'Henry',
		);

		$sut = new Checkout( $logger );

		$sut->prefill_checkout_fields( $email_address, $user_info );

		$this->assertSame( 'Sacramento', WC()->customer->get_billing_city() );
	}


	/**
	 * When there is no past order associated with that email address,
	 * use the first and last name passed from the mailing list plugin.
	 *
	 * @covers ::prefill_checkout_fields
	 */
	public function test_woocommerce_name(): void {

		$logger = new ColorLogger();

		$email_address = 'test@example.org';
		$user_info     = array(
			'first_name' => 'Brian',
			'last_name'  => 'Henry',
		);

		$sut = new Checkout( $logger );

		$sut->prefill_checkout_fields( $email_address, array( $email_address, $user_info ) );

		$this->assertSame( 'Brian', WC()->customer->get_first_name() );
	}

}