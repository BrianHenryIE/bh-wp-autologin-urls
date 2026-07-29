<?php
/**
 * Expose the create autologin url function via the REST API.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use WP_Http;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;
use WP_User;

/**
 * Registers `bh-wp-autologin-urls/v1/autologin-codes`.
 */
class REST_API extends WP_REST_Controller {

	const REST_NAMESPACE = 'bh-wp-autologin-urls/v1';
	const REST_BASE      = 'autologin-codes';

	/**
	 * Used to generate the autologin URL.
	 */
	protected API_Interface $api;

	/**
	 * @param API_Interface $api The plugin's public API.
	 */
	public function __construct( API_Interface $api ) {
		$this->api       = $api;
		$this->namespace = self::REST_NAMESPACE;
		$this->rest_base = self::REST_BASE;
	}

	/**
	 * @see WP_REST_Controller::register_routes()
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_BASE,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_args_schema(),
				),
			)
		);
	}

	/**
	 *
	 * @see WP_REST_Controller::create_item()
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_Error|\WP_HTTP_Response|WP_REST_Response
	 */
	public function create_item( $request ) {
		$user = $request->get_param( 'user' );
		if ( empty( $user ) ) {
			$user = wp_get_current_user();
		}

		$url = $request->get_param( 'url' );
		if ( ! stristr( (string) $url, (string) get_site_url() ) ) {
			$url = get_site_url( $url );
		}

		$expires_in = $request->get_param( 'expires_in' );
		if ( ! is_numeric( $expires_in ) || intval( $expires_in ) === 0 ) {
			$expires_in = null;
		} else {
			$expires_in = absint( $expires_in );
		}

		// NB: `add_autologin_to_url()` returns the URL unchanged when it cannot add a code – the
		// user does not exist, the URL is not for this site, or storing the code failed. The
		// response does not currently distinguish those.
		try {
			$url = $this->api->add_autologin_to_url(
				(string) $url,
				$user,
				$expires_in
			);
		} catch ( \Throwable $e ) {
			// The API logs the cause.
			return new \WP_Error(
				'autologin_code_failed',
				__( 'Failed to create the autologin code.', 'bh-wp-autologin-urls' ),
				array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
			);
		}

		return $this->prepare_item_for_response( $url, $request );
	}

	/**
	 * Allow admins and the user themselves to create autologin codes.
	 *
	 * @see WP_REST_Controller::create_item_permissions_check()
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return bool
	 */
	public function create_item_permissions_check( $request ) {

		$user_param = $request->get_param( 'user' );

		// If the user is not set, `wp_get_current_user()` will be used.
		if ( empty( $user_param ) ) {
			return true;
		}

		$user = $this->api->get_wp_user( $user_param );

		// If the current user is creating a link for themselves.
		if ( $user instanceof WP_User
			&& wp_get_current_user()->ID === $user->ID ) {
			return true;
		}

		// Admins can create links for anyone.
		return current_user_can( 'manage_options' );
	}

	/**
	 * @see WP_REST_Controller::prepare_item_for_response()
	 *
	 * @param string           $item    The generated autologin URL.
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_Error|\WP_HTTP_Response|WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {

		$data = array(
			'autologin_url' => $item,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->set_status( WP_Http::CREATED );

		return $response;
	}

	/**
	 * @see WP_REST_Controller::get_item_schema()
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bh-wp-autologin-codes-autologin-code',
			'type'       => 'object',
			'properties' => array(
				'autologin_url' => array(
					'type'    => 'string',
					'format'  => 'url',
					'context' => array( 'view' ),
				),
			),
		);
	}

	/**
	 * The arguments accepted by the create-item endpoint.
	 *
	 * @return array<string, mixed>
	 */
	public function get_args_schema() {
		$args = array();

		$args['user'] = array(
			'description' => esc_html__( 'The user to create the code for.', 'bh-wp-autologin-urls' ),
			'required'    => true,
			'context'     => array( 'edit' ),
			// TODO: Is `oneOf` doing anything?!
			'oneOf'       => array(
				array(
					'description' => esc_html__( 'Username.', 'bh-wp-autologin-urls' ),
					'type'        => 'string',
				),
				array(
					'description' => esc_html__( 'Email.', 'bh-wp-autologin-urls' ),
					'type'        => 'string',
					'format'      => 'email',
				),
			),
		);

		$args['url'] = array(
			'description' => 'The URL to add the login code to.',
			'type'        => 'string',
			'format'      => 'url',
			'context'     => array( 'edit' ),
			'required'    => false,
		);

		$args['expires_in'] = array(
			'description' => esc_html__( 'Number of seconds the code should be valid for.', 'bh-wp-autologin-urls' ),
			'type'        => 'integer',
			'context'     => array( 'edit' ),
			'required'    => false,
		);

		return $args;
	}
}
