<?php
/**
 * Login takes care of... logging in... but when no WP User exists to log in, it checks
 * for the presence of WooCommerce and tries to fill in checkout fields.
 */

namespace BH_WP_Autologin_URLs\includes;

use BH_WP_Autologin_URLs\api\API_Interface;
use BH_WP_Autologin_URLs\Psr\Log\LoggerInterface;

class Login_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * When there is a past order associated with that email address,
	 * use its billing details in the checkout fields.
	 */
	public function test_woocommerce_order() {

		$existing_order = new \WC_Order();
		$existing_order->set_billing_email( 'test@example.org' );
		$existing_order->set_billing_city( 'Sacramento' );
		$existing_order->save();

		$api_mock = $this->makeEmpty( API_Interface::class );
		$logger_mock = $this->makeEmpty( LoggerInterface::class );

		$login = new class( 'bh-wp-autologin-urls', '1.4.0', $api_mock, $logger_mock ) extends Login {
			public function test_woocommerce( $email_address, $user_info ) {
				return $this->woocommerce_ux( $email_address, $user_info );
			}
		};

		$login->test_woocommerce( 'test@example.org', array() );

		$this->assertSame( 'Sacramento', WC()->customer->get_billing_city());
	}


	/**
	 * When there is no past order associated with that email address,
	 * use the first and last name passed from the mailing list plugin.
	 */
	public function test_woocommerce_name() {

		$api_mock = $this->makeEmpty( API_Interface::class );
		$logger_mock = $this->makeEmpty( LoggerInterface::class );

		$login = new class( 'bh-wp-autologin-urls', '1.4.0', $api_mock, $logger_mock ) extends Login {
			public function test_woocommerce( $email_address, $user_info ) {
				return $this->woocommerce_ux( $email_address, $user_info );
			}
		};

		$user_info = array(
			'first_name' => 'Brian',
			'last_name' => 'Henry'
		);

		// Act.
		$login->test_woocommerce( 'test@example.org', $user_info );

		$this->assertSame( 'Brian', WC()->customer->get_first_name() );
	}
}
