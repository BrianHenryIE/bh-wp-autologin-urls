<?php
/**
 * CLI commands for:
 * * getting a URL with an autologin code in it
 * * sending the magic link email to a user
 *
 * Someone else has written a WP CLI package which provides the same function without needing to be installed as a site plugin.
 *
 * @see https://github.com/aaemnnosttv/wp-cli-login-command
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use WP_CLI;

/**
 * WP CLI sanitizes some input, we sanitize a little more, then call the API functions.
 */
class CLI {

	/**
	 * The CLI class is really a small wrapper on the API class.
	 */
	protected API_Interface $api;

	/**
	 * Constructor.
	 *
	 * @param API_Interface $api The main plugin functions implemented.
	 */
	public function __construct( API_Interface $api ) {
		$this->api = $api;
	}

	/**
	 * Append an autologin code to a URL.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User id, username/login, or email address.
	 *
	 * [<url>]
	 * : The URL to append to.
	 * ---
	 * default: /
	 * ---
	 *
	 * [<expires_in>]
	 * : Number of seconds the code should be valid. Default WEEK_IN_SECONDS.
	 *
	 * ## EXAMPLES
	 *
	 *   # Add an autologin code to the site root for brianhenryie which expires in one week.
	 *   $ wp autologin-urls get-url brianhenryie
	 *
	 *   # Add an autologin code to the URL /my-account for brianhenryie which expires in five minutes
	 *   $ wp autologin-urls get-url brianhenryie my-account 300
	 *
	 * @see API_Interface::add_autologin_to_url()
	 *
	 * @param string[]             $args The unlabelled command line arguments.
	 * @param array<string,string> $assoc_args The labelled command line arguments.
	 */
	public function add_autologin_to_url( array $args, array $assoc_args ): void {

		$user = $args[0];
		$url  = $args[1];

		$parsed_url = wp_parse_url( $url );

		$full_url = get_site_url();
		if ( isset( $parsed_url['path'] ) ) {
			$full_url .= '/' . $parsed_url['path'];
		}
		$full_url = rtrim( $full_url, '/' ) . '/';
		if ( isset( $parsed_url['query'] ) ) {
			$full_url .= '?' . $parsed_url['query'];
		}
		if ( isset( $parsed_url['fragment'] ) ) {
			$full_url .= '#' . $parsed_url['fragment'];
		}

		$expires_in = isset( $args[2] ) ? intval( $args[2] ) : WEEK_IN_SECONDS;

		$result = $this->api->add_autologin_to_url( $full_url, $user, $expires_in );

		WP_CLI::success( $result );
	}

	/**
	 * Send a magic login email to a user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User id, username/login, or email address.
	 *
	 * <url>
	 * : The URL the link in the email should go to.
	 * ---
	 * default: /
	 * ---
	 *
	 * [<expires_in>]
	 * : Number of seconds the code should be valid.
	 * ---
	 * default: 900
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *   # Send a magic login link to user brianhenryie, if they exist.
	 *   $ wp autologin-urls send-magic-link brianhenryie
	 *
	 * @see API_Interface::send_magic_link()
	 *
	 * @param string[]             $args The unlabelled command line arguments.
	 * @param array<string,string> $assoc_args The labelled command line arguments.
	 */
	public function send_magic_link( array $args, array $assoc_args ): void {

		$user       = $args[0];
		$url        = $args[1];
		$expires_in = intval( $args[2] );

		$result = $this->api->send_magic_link( $user, $url, $expires_in );

		if ( $result['success'] && isset( $result['wp_user'] ) ) {
			WP_CLI::success( 'Magic login link sent to user ' . $result['wp_user']->user_login . ' at email ' . $result['wp_user']->user_email );
		} else {
			WP_CLI::error( $result['message'] ?? 'Failed to send magic login link.' );
		}
	}
}
