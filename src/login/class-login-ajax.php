<?php
/**
 * Handle the button press on wp-login.php.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\Login;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Checks the nonce and forwards the message.
 * Returns a response to the client & protects privacy.
 *
 * @see API::send_magic_link()
 */
class Login_Ajax {
	use LoggerAwareTrait;

	/**
	 * This AJAX class is a UI facade.
	 *
	 * @see API_Interface::send_magic_link()
	 *
	 * @var API_Interface The plugin's main functions.
	 */
	protected API_Interface $api;

	/**
	 * Constructor.
	 *
	 * @param API_Interface   $api The plugin's core functions.
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( API_Interface $api, LoggerInterface $logger ) {
		$this->setLogger( $logger );

		$this->api = $api;
	}

	/**
	 * Handle the button press for sending the magic link.
	 *
	 * @hooked wp_ajax_nopriv_bh_wp_autologin_urls_send_magic_link
	 */
	public function email_magic_link(): void {

		if ( ! check_ajax_referer( self::class, false, false ) ) {
			wp_send_json_error( array( 'message' => 'Bad/no nonce.' ), 400 );
		}

		if ( ! isset( $_POST['username'] ) ) {
			wp_send_json_error( 'No username provided.', 400 );
		}

		$username = sanitize_user( wp_unslash( $_POST['username'] ) );

		$url = null;
		if ( ! empty( $_POST['url'] ) ) {
			$url = esc_url_raw( wp_unslash( $_POST['url'] ) );

			// WooCommerce `_wp_http_referer` is relative to the server root (rather than the site url).
			// whereas redirect_to on wp-login.php is absolute.
			if ( 0 !== strpos( $url, get_site_url() ) ) {
				$url = get_http_origin() . $url;
			}
		}

		$result = $this->api->send_magic_link( $username, $url );

		$response = array();

		// Should probably just use an exception.
		if ( isset( $result['error'] ) ) {
			$response['message'] = __( 'An error occurred when sending the magic login email.', 'bh-wp-autologin-urls' );
			wp_send_json_error( $response, 500 );
		}

		$expires_in_friendly = human_time_diff( time() - $result['expires_in'] );

		/* translators: %1$s is the length of time e.g. "15 mins". */
		$response['message'] = sprintf( __( 'Check your email for the login link. The link will expire in %1$s.', 'bh-wp-autologin-urls' ), $expires_in_friendly );
		wp_send_json( $response );
	}
}
