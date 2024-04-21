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

class REST_API extends WP_REST_Controller {

	protected API_Interface $api;

	public function __construct( API_Interface $api ) {
		$this->api       = $api;
		$this->namespace = 'bh-wp-autologin-urls/v1';
		$this->rest_base = 'autologin-codes';
	}

	/**
	 * @see WP_REST_Controller::register_routes()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
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
	 * @param \WP_REST_Request $request
	 * @return \WP_Error|\WP_HTTP_Response|WP_REST_Response
	 */
	public function create_item( $request ) {
		$user = $request->get_param( 'user' );
		if ( empty( $user ) ) {
			$user = wp_get_current_user();
		}

		$url = $request->get_param( 'url' );
		if ( ! stristr( $url, get_site_url() ) ) {
			$url = get_site_url( $url );
		}

		$expires_in = $request->get_param( 'expires_in' );
		if ( ! is_numeric( $expires_in ) || intval( $expires_in ) === 0 ) {
			$expires_in = null;
		} else {
			$expires_in = absint( $expires_in );
		}

		$url = $this->api->add_autologin_to_url(
			$url,
			$user,
			$expires_in
		);

		// Check was the URL modified at all.

		return $this->prepare_item_for_response( $url, $request );
	}

	/**
	 * Allow admins and the user themselves to create autologin codes.
	 *
	 * @see WP_REST_Controller::create_item_permissions_check()
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
			&& wp_get_current_user() instanceof WP_User
			&& $user->ID === wp_get_current_user()->ID ) {
			return true;
		}

		// Admins can create links for anyone.
		return current_user_can( 'manage_options' );
	}

	/**
	 * @see WP_REST_Controller::prepare_item_for_response()
	 *
	 * @param $item
	 * @param $request
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

	public function get_args_schema() {
		$args = array();

		$args['user'] = array(
			'description' => esc_html__( 'The user to create the code for.', 'bh-wp-autologin-urls' ),
			'required'    => true,
			'context'     => array( 'edit' ),
			'oneOf'       => array( // TODO: Is this doing anything?!
			// array(
			// 'description' => esc_html__( 'User id.', 'bh-wp-autologin-urls' ),
			// 'type'        => 'integer',
			// ),
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
			'type'     => 'int',
			'format'   => 'url',
			'context'  => array( 'edit' ),
			'required' => false,
		);

		return $args;
	}
}
