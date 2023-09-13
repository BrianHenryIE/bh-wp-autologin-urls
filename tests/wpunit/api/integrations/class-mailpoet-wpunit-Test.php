<?php

namespace BrianHenryIE\WP_Autologin_URLs\API\Integrations;

use BrianHenryIE\ColorLogger\ColorLogger;
use MailPoet\Models\Subscriber;


/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\API\Integrations\MailPoet
 */
class MailPoet_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::is_querystring_valid
	 */
	public function test_is_querystring_valid_present(): void {

		$logger = new ColorLogger();

		$sut = new MailPoet( $logger );

		$_GET['mailpoet_router'] = 'abc';
		$_GET['data']            = '123';

		$result = $sut->is_querystring_valid();

		$this->assertTrue( $result );
	}


	/**
	 * @covers ::is_querystring_valid
	 */
	public function test_is_querystring_valid_absent(): void {

		$logger = new ColorLogger();

		$sut = new MailPoet( $logger );

		unset( $_GET['mailpoet_router'] );
		unset( $_GET['data'] );

		$result = $sut->is_querystring_valid();

		$this->assertFalse( $result );
	}


	/**
	 * Generate a tracking URL for MailPoet and check the function response.
	 *
	 * @covers ::get_wp_user_array
	 */
	public function test_get_wp_user_array(): void {

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

		$sut = new MailPoet( $logger );

		$result = $sut->get_wp_user_array();

		$this->assertEquals( $user_id, $result['wp_user']->ID );
	}
}
