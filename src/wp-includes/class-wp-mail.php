<?php
/**
 * Class to filter wp_mail and add autologin codes to urls in the message body.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;

/**
 * The wp_mail hooked functionality of the plugin.
 */
class WP_Mail {

	/**
	 * The class which adds the autologin codes to the emails.
	 *
	 * @var API_Interface
	 */
	protected $api;

	/**
	 * The settings, as configured in the WordPress admin UI.
	 *
	 * @var Settings_Interface
	 */
	protected $settings;

	/**
	 * WP_Mail constructor.
	 *
	 * @param API_Interface      $api          The API class for adding the autologin code to URLs.
	 * @param Settings_Interface $settings     The settings to be used.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings ) {

		$this->api      = $api;
		$this->settings = $settings;
	}

	/**
	 * Assumed added as a filter on wp_mail(), this function uses the api class to add
	 * autologin codes to urls in emails, as appropriate with the configured settings.
	 *
	 * @hooked wp_mail
	 *
	 * @param array{to:string|array<string>, subject:string, message:string, headers?:string|array<string>, attachments:string|array<string>} $wp_mail_args The arguments passed to wp_mail() (before processing).
	 *
	 * @return array{to:string|array<string>, subject:string, message:string, headers?:string|array<string>, attachments:string|array<string>}
	 * @see wp_mail()
	 */
	public function add_autologin_links_to_email( array $wp_mail_args ): array {

		$to = $wp_mail_args['to'];

		if ( is_array( $to ) && count( $to ) !== 1 ) {
			return $wp_mail_args;
		}

		if ( is_array( $to ) ) {
			$to = array_pop( $to );
		}

		$user = get_user_by( 'email', $wp_mail_args['to'] );

		// If the email recipient does not have a user account on this site, return the message unchanged.
		if ( ! $user ) {
			return $wp_mail_args;
		}

		// If there are no links in the message to this site, return.
		if ( ! stristr( $wp_mail_args['message'], get_site_url() ) ) {
			return $wp_mail_args;
		}

		$should_add_autologin = true;

		// The default setting does not add autologin codes in emails to admins.
		if ( $user->has_cap( 'administrator' ) && ! $this->settings->get_add_autologin_for_admins_is_enabled() ) {
			$should_add_autologin = false;
		}

		/**
		 * To override for all users use:
		 * `add_filter( 'autologin_urls_for_users', '__return_true' );`
		 *
		 * @see https://codex.wordpress.org/Function_Reference/_return_true
		 * @see https://codex.wordpress.org/Function_Reference/_return_false
		 *
		 * @param bool     $should_add_autologin Variable to change to true if the urls should log in admin users.
		 * @param \WP_User  $user The WordPress user the email is being sent to.
		 * @param array    $wp_mail_args The array of values wp_mail() functions uses: subject, message etc.
		 */
		$should_add_autologin = apply_filters( 'autologin_urls_for_users', $should_add_autologin, $user, $wp_mail_args );

		$disallowed_subjects_regex_array = $this->settings->get_disallowed_subjects_regex_array();

		/**
		 * To add or remove regex filters for message subjects:
		 * `add_filter( 'autologin_urls_disallowed_subject_regexes', 'my_function', 10, 3 )`
		 *
		 * @param array    $disallowed_subjects_regex_array
		 * @param \WP_User  $user The WordPress user the email is being sent to.
		 * @param array    $wp_mail_args The array of values wp_mail() functions uses: subject, message etc.
		 */
		$disallowed_subjects_regex_array = apply_filters( 'autologin_urls_disallowed_subject_regexes', $disallowed_subjects_regex_array, $user, $wp_mail_args );

		foreach ( $disallowed_subjects_regex_array as $disallowed_subject_regex ) {

			if ( preg_match( $disallowed_subject_regex, $wp_mail_args['subject'] ) ) {

				$should_add_autologin = false;

			}
		}

		/**
		 * Exclude emails being sent to multiple recipients.
		 */
		if ( isset( $wp_mail_args['headers'] ) ) {

			$headers = $wp_mail_args['headers'];

			if ( is_string( $headers ) ) {
				$headers = array( $headers );
			}

			foreach ( $headers as $header ) {
				if ( 1 === preg_match( '/^b?cc:/i', $header ) ) {
					$should_add_autologin = false;
					break;
				}
			}
		}

		if ( ! $should_add_autologin ) {

			return $wp_mail_args;
		}

		// The heavy lifting.
		$wp_mail_args['message'] = $this->api->add_autologin_to_message( $wp_mail_args['message'], $user, null );

		return $wp_mail_args;
	}
}
