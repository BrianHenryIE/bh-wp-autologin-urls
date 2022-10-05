<?php
/**
 * Add an autologin code to the "Customer payment page" link on pending orders.
 * Change the link so it is click-to-clipboard.
 *
 * @package    brianhenryie/bh-wp-autologin-urls
 * @since      1.4.0
 */

namespace BrianHenryIE\WP_Autologin_URLs\WooCommerce;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use WC_Order;

/**
 * Filter the URL to add the code.
 * Filter the link text to remove the arrow.
 * Add CSS to display a clipboard icon beside the link.
 * Add JS to add the link to the computer clipboard.
 */
class Admin_Order_UI {

	/**
	 * The plugin settings, the version is used to cache JS and CSS in the browser.
	 *
	 * @uses Settings_Interface::get_plugin_version()
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	/**
	 * The main plugin functions, used to add the autologin code to the customer payment page url.
	 *
	 * @uses API_Interface::add_autologin_to_url()
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Constructor.
	 *
	 * @param API_Interface      $api The main plugin functions.
	 * @param Settings_Interface $settings The settings, to find the plugin version for cache versioning.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings ) {
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Adds an autologin code to "Customer payment page" link, which is displayed above the order status when
	 * the status is "pending".
	 *
	 * "http://localhost:8080/bh-wp-autologin-urls/checkout/order-pay/14/?pay_for_order=true&key=wc_order_s3TZsxvleY0uW"
	 *
	 * @hooked woocommerce_get_checkout_payment_url
	 *
	 * @param string   $payment_url The existing link to pay for the order.
	 * @param WC_Order $order       The WooCommerce order object.
	 *
	 * @return string Updated link with autologin code.
	 */
	public function add_to_payment_url( string $payment_url, WC_Order $order ): string {

		// Without this check for admin UI, autologin codes were being created on every REST request by the 3PF fulfillment.
		if ( ! is_admin() ) {
			return $payment_url;
		}

		$payment_url = $this->api->add_autologin_to_url( $payment_url, $order->get_billing_email() );

		return $payment_url;
	}

	/**
	 * The
	 *
	 * @hooked gettext_woocommerce
	 *
	 * @param string $translation The text after any existing translations applied.
	 * @param string $text The original untranslated text.
	 * @param string $domain The text domain, will always be "woocommerce" due to the hook.
	 *
	 * @return string
	 */
	public function remove_arrow_from_link_text( string $translation, string $text, string $domain ): string {

		if ( 'Customer payment page &rarr;' === $text ) {
			return str_replace( '&rarr;', '', $translation );
		}

		return $translation;
	}

	/**
	 * Register the JavaScript for copying the URL to the clipboard.
	 *
	 * @hooked admin_enqueue_scripts
	 *
	 * @since    1.4.0
	 */
	public function enqueue_script(): void {

		if ( ! $this->is_on_shop_order_edit_screen() ) {
			return;
		}

		wp_enqueue_script( 'bh-wp-autologin-urls-woocommerce-admin', plugin_dir_url( $this->settings->get_plugin_basename() ) . 'assets/bh-wp-autologin-urls-woocommerce-admin.js', array( 'jquery' ), $this->settings->get_plugin_version(), true );
	}

	/**
	 * Add the CSS for the clipboard icon after the link.
	 *
	 * @hooked admin_enqueue_scripts
	 *
	 * @since    1.4.0
	 */
	public function enqueue_styles(): void {

		if ( ! $this->is_on_shop_order_edit_screen() ) {
			return;
		}

		wp_enqueue_style( 'bh-wp-autologin-urls-woocommerce-admin', plugin_dir_url( $this->settings->get_plugin_basename() ) . 'assets/bh-wp-autologin-urls-woocommerce-admin.css', array(), $this->settings->get_plugin_version(), 'all' );
	}

	/**
	 * Use `get_current_screen()` and `global $action` to determine are we on the admin order edit screen,
	 * otherwise we won't want to enqueue the styles and script.
	 *
	 * @return bool
	 */
	protected function is_on_shop_order_edit_screen(): bool {

		$screen = get_current_screen();
		global $action;

		if ( is_null( $screen ) || 'shop_order' !== $screen->id || 'edit' !== $action ) {
			return false;
		}

		return true;
	}
}
