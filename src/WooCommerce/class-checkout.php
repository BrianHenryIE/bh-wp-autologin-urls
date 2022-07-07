<?php
/**
 * If we have the identity of someone through a Klaviyo/MailPoet/etc. email link but
 * they do not have a user account, we can still prefill their information at checkout.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\WooCommerce;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

/**
 * Uses WC()->customer (the global WC_Customer object) methods to set the values.
 */
class Checkout {
	use LoggerAwareTrait;

	/**
	 * Constructor
	 *
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->setLogger( $logger );
	}

	/**
	 * If WooCommerce is installed, when there is no WP_User, attempt to populate the user checkout
	 * fields using data from Newsletter/MailPoet and from past orders by that email address.
	 *
	 * TODO: Prefill additional fields returned by Klaviyo.
	 *
	 * @param string                                     $email_address The user's email address.
	 * @param array{first_name:string, last_name:string} $user_info Information e.g. first name, last name that might be available from MailPoet/Newsletter.
	 */
	public function prefill_checkout_fields( string $email_address, array $user_info ): void {

		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		WC()->initialize_cart();

		WC()->customer->set_billing_email( $email_address );

		if ( ! empty( $user_info['first_name'] ) ) {
			WC()->customer->set_first_name( $user_info['first_name'] );
			WC()->customer->set_billing_first_name( $user_info['first_name'] );
			WC()->customer->set_shipping_first_name( $user_info['first_name'] );
		}

		if ( ! empty( $user_info['last_name'] ) ) {
			WC()->customer->set_last_name( $user_info['last_name'] );
			WC()->customer->set_billing_last_name( $user_info['last_name'] );
			WC()->customer->set_shipping_last_name( $user_info['last_name'] );
		}

		/**
		 * Try to get one past order placed by this email address.
		 *
		 * @var WC_Order[] $customer_orders
		 */
		$customer_orders = wc_get_orders(
			array(
				'customer' => $email_address,
				'limit'    => 1,
				'order'    => 'DESC',
				'orderby'  => 'id',
				'paginate' => false,
			)
		);

		if ( count( $customer_orders ) > 0 ) {

			$order = $customer_orders[0];

			WC()->customer->set_billing_country( $order->get_billing_country() );
			WC()->customer->set_billing_postcode( $order->get_billing_postcode() );
			WC()->customer->set_billing_state( $order->get_billing_state() );
			WC()->customer->set_billing_last_name( $order->get_billing_last_name() );
			WC()->customer->set_billing_first_name( $order->get_billing_first_name() );
			WC()->customer->set_billing_address_1( $order->get_billing_address_1() );
			WC()->customer->set_billing_address_2( $order->get_billing_address_2() );
			WC()->customer->set_billing_city( $order->get_billing_city() );
			WC()->customer->set_billing_company( $order->get_billing_company() );
			WC()->customer->set_billing_phone( $order->get_billing_phone() );

			$this->logger->info( "Set customer checkout details from past order #{$order->get_id()}" );
		}

	}

}
