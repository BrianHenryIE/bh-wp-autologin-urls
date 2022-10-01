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
	 * @param array{email:string, first_name:string, last_name:string} $user_info Information e.g. first name, last name that might be available from MailPoet/Newsletter/Klaviyo.
	 */
	public function prefill_checkout_fields( array $user_info ): void {

		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		// If we're here, it means there is no WP_User object for this email address.
		// We try to pre-fill the checkout by setting the WC_Customer fields.
		// But when WooCommerce shuts down, it sends out a "your account has been created" email.
		// This function should prevent the account being created, but TODO: test does the data persist to the checkout.

		return; // Until further testing.

		/**
		 * Manually initialize the cart, so the customer save function can be overwritten, and the shutdown action
		 * is not added.
		 *
		 * Otherwise, when WooCommerce shuts down, it calls the customer save function, and will create a user account
		 * for every email address entered (and will send them an email telling them about the account they didn't
		 * really sign up for).
		 *
		 * If the customer has already been initialized, it should be safe to use the existing one.
		 *
		 * @see WooCommerce::initialize_cart()
		 */
		if ( is_null( WC()->customer ) || ! WC()->customer instanceof \WC_Customer ) {
			WC()->customer = new class( get_current_user_id(), true ) extends \WC_Customer {
				public function save() {
					if ( empty( $this->get_email() ) ) {
						return $this->get_id();
					}
					return parent::save();
				}
			};
			add_action( 'shutdown', array( WC()->customer, 'save' ), 10 );
		}
		if ( is_null( WC()->cart ) || ! WC()->cart instanceof \WC_Cart ) {
			WC()->cart = new \WC_Cart();
		}

		if ( ! empty( $user_info['email'] ) && is_email( $user_info['email'] ) ) {
			WC()->customer->set_billing_email( $user_info['email'] );
		}

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

		if ( ! isset( $user_info['email'] ) ) {
			return;
		}

		// Otherwise "wc_get_order was called incorrectly" warning is shown.
		add_action(
			'woocommerce_after_register_post_type',
			function() use ( $user_info ) {

				/**
				 * Try to get one past order placed by this email address.
				 *
				 * @var WC_Order[] $customer_orders
				 */
				$customer_orders = wc_get_orders(
					array(
						'customer' => $user_info['email'],
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

					$this->logger->info( "Set customer checkout details from past order wc_order:{$order->get_id()}" );
				}
			}
		);
	}

}
