<?php
/**
 * Login takes care of... logging in... but when no WP User exists to log in, it checks
 * for the presence of WooCommerce and tries to fill in checkout fields.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API\API_Interface;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Subscriber;
use Psr\Log\LoggerInterface;
use function _PHPStan_76800bfb5\RingCentral\Psr7\parse_query;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\WP_Includes\Login
 */
class Login_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * When there is a past order associated with that email address,
	 * use its billing details in the checkout fields.
	 *
	 * @covers ::woocommerce_ux
	 */
	public function test_woocommerce_order(): void {

		$existing_order = new \WC_Order();
		$existing_order->set_billing_email( 'test@example.org' );
		$existing_order->set_billing_city( 'Sacramento' );
		$existing_order->save();

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$email_address = 'test@example.org';
		$user_info     = array(
			'first_name' => 'Brian',
			'last_name'  => 'Henry',
		);

		$login = new Login( $api, $logger );

		$class_name = get_class( $login );
		$reflection = new \ReflectionClass( $class_name );

		$method = $reflection->getMethod( 'woocommerce_ux' );
		$method->setAccessible( true );

		$method->invokeArgs( $login, array( $email_address, $user_info ) );

		$this->assertSame( 'Sacramento', WC()->customer->get_billing_city() );
	}


	/**
	 * When there is no past order associated with that email address,
	 * use the first and last name passed from the mailing list plugin.
	 *
	 * @covers ::woocommerce_ux
	 */
	public function test_woocommerce_name(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$email_address = 'test@example.org';
		$user_info     = array(
			'first_name' => 'Brian',
			'last_name'  => 'Henry',
		);

		$login = new Login( $api, $logger );

		$class_name = get_class( $login );
		$reflection = new \ReflectionClass( $class_name );

		$method = $reflection->getMethod( 'woocommerce_ux' );
		$method->setAccessible( true );

		$method->invokeArgs( $login, array( $email_address, $user_info ) );

		$this->assertSame( 'Brian', WC()->customer->get_first_name() );
	}

	/**
	 * Generate a tracking URL for MailPoet and check it logs the user in.
	 *
	 * @covers ::login_mailpoet_urls
	 */
	public function test_login_mailpoet_urls(): void {

		// MailPoet automatically registers the new WP User as a subscriber.
		$user_id                = wp_create_user( 'MailPoet Test User', 'mptest', 'user@example.org' );
		$subscriber             = Subscriber::where( 'email', 'user@example.org' )->findOne();
		$mailpoet_subscriber_id = $subscriber->id;

		$params                    = array();
		$params['mailpoet_router'] = '';
		$params['endpoint']        = 'track';
		$params['action']          = 'click';
		/**
		 * @see Router::decodeRequestData()
		 * @see Router::encodeRequestData()
		 *
		 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		 */
		$data_array = array(
			0 => $mailpoet_subscriber_id,
			1 => $subscriber->linkToken,
			2 => '7', // queue_id.
			3 => 'f11e2150f233', // link_hash.
			4 => false, // preview.
		);
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$params['data'] = rtrim( base64_encode( wp_json_encode( $data_array ) ), '=' );

		/**
		 * For subscriber_id 5, linkToken "6cc401c4641857061baa77d5d969b746":
		 *
		 * E.g. http://localhost:8080/bhwpie?mailpoet_router&endpoint=track&action=click&data=WyI1IiwiNmNjNDAxYzQ2NDE4NTcwNjFiYWE3N2Q1ZDk2OWI3NDYiLCI3IiwiZjExZTIxNTBmMjMzIixmYWxzZV0
		 */
		$url = add_query_arg( $params, get_site_url() );

		// This is a bit convoluted!
		$parts = wp_parse_url( $url );
		wp_parse_str( $parts['query'], $_GET );

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$login = new Login( $api, $logger );

		assert( 0 === get_current_user_id() );

		$login->login_mailpoet_urls();

		$this->assertEquals( $user_id, get_current_user_id() );

	}
}
