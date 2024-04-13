<?php
/**
 * @see \BrianHenryIE\WP_Autologin_URLs\Includes\REST_API
 */

namespace BrianHenryIE\WP_Autologin_URLs\Includes;

use WP_REST_Request;


class REST_API_Integration_Test extends \Codeception\TestCase\WPTestCase {

	public function tearDown(): void {
		wp_set_current_user( 0 );
	}

	public function test_route_exists(): void {
		$rest_server = rest_get_server();

		self::assertArrayHasKey( '/bh-wp-autologin-urls/v1', $rest_server->get_routes() );
		self::assertArrayHasKey( '/bh-wp-autologin-urls/v1/autologin-codes', $rest_server->get_routes() );
	}
	public function test_unauthorized(): void {
		$wp_user_id = wp_create_user( uniqid( 'test' ), wp_generate_password() );

		$request = new WP_REST_Request(
			'POST',
			'/bh-wp-autologin-urls/v1/autologin-codes',
		);

		$request->set_body_params(
			array(
				'user'       => 123,
				'url'        => 'my-account',
				'expires_in' => 300,
			)
		);

		wp_set_current_user( $wp_user_id );

		$response = rest_do_request( $request );

		self::assertEquals( 403, $response->get_status() );
	}

	public function test_unauthenticated(): void {

		$request = new WP_REST_Request(
			'POST',
			'/bh-wp-autologin-urls/v1/autologin-codes',
		);

		$request->set_body_params(
			array(
				'user'       => 123,
				'url'        => 'my-account',
				'expires_in' => 300,
			)
		);

		wp_set_current_user( 0 );

		$response = rest_do_request( $request );

		self::assertEquals( 401, $response->get_status() );
	}
	public function test_admin_authenticated(): void {

		$request = new WP_REST_Request(
			'POST',
			'/bh-wp-autologin-urls/v1/autologin-codes',
		);

		$request->set_body_params(
			array(
				'user'       => 123,
				'url'        => 'my-account',
				'expires_in' => 300,
			)
		);

		wp_set_current_user( 1 );

		$response = rest_do_request( $request );

		self::assertEquals( 201, $response->get_status() );
	}

	public function test_user_authenticated(): void {
		$wp_user_id = wp_create_user( uniqid( 'test' ), wp_generate_password() );

		$request = new WP_REST_Request(
			'POST',
			'/bh-wp-autologin-urls/v1/autologin-codes',
		);

		$request->set_body_params(
			array(
				'user'       => $wp_user_id,
				'url'        => 'my-account',
				'expires_in' => 300,
			)
		);

		wp_set_current_user( $wp_user_id );

		$response = rest_do_request( $request );

		self::assertEquals( 201, $response->get_status() );
	}


	public function test_create_item(): void {

		$wp_user_id = wp_create_user( uniqid( 'test' ), wp_generate_password() );

		$rest_server = rest_get_server();

		$request = new WP_REST_Request(
			'POST',
			'/bh-wp-autologin-urls/v1/autologin-codes',
		);

		$request->set_body_params(
			array(
				'user'       => $wp_user_id,
				'url'        => 'my-account',
				'expires_in' => 300,
			)
		);

		wp_set_current_user( 1 );

		$response = rest_do_request( $request );

		$result = $rest_server->response_to_data( $response, false );

		self::assertArrayHasKey( 'autologin_url', $result );
	}

	public function test_create_item_user_is_username(): void {

		$username = uniqid( 'test' );
		wp_create_user( $username, wp_generate_password() );

		$request = new WP_REST_Request(
			'POST',
			'/bh-wp-autologin-urls/v1/autologin-codes',
		);

		$request->set_body_params(
			array(
				'user'       => $username,
				'url'        => 'my-account',
				'expires_in' => 300,
			)
		);

		wp_set_current_user( 1 );

		$response = rest_do_request( $request );

		self::assertEquals( 201, $response->get_status() );
	}

	public function test_create_item_user_is_email(): void {

		$email = 'brian@example.org';
		wp_create_user( uniqid( 'test' ), wp_generate_password(), $email );

		$request = new WP_REST_Request(
			'POST',
			'/bh-wp-autologin-urls/v1/autologin-codes',
		);

		$request->set_body_params(
			array(
				'user'       => $email,
				'url'        => 'my-account',
				'expires_in' => 300,
			)
		);

		wp_set_current_user( 1 );

		$response = rest_do_request( $request );

		self::assertEquals( 201, $response->get_status() );
	}
}
