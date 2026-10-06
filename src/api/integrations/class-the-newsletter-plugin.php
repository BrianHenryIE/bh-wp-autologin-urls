<?php
/**
 * Identify a user from The Newsletter Plugin's tracking querystring.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\API\Integrations;

use BrianHenryIE\WP_Autologin_URLs\API\User_Finder_Interface;
use Newsletter;
use NewsletterStatistics;
use BrianHenryIE\WP_Autologin_URLs\Psr\Log\LoggerAwareInterface;
use BrianHenryIE\WP_Autologin_URLs\Psr\Log\LoggerAwareTrait;
use BrianHenryIE\WP_Autologin_URLs\Psr\Log\LoggerInterface;
use WP_User;

/**
 * The $_GET data is coming from links clicked outside WordPress; it will not have a nonce.
 *
 * phpcs:disable WordPress.Security.NonceVerification.Recommended
 */
class The_Newsletter_Plugin implements User_Finder_Interface, LoggerAwareInterface {
	use LoggerAwareTrait;

	/**
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->setLogger( $logger );
	}

	/**
	 * Determine is the querystring needed for this integration present.
	 */
	public function is_querystring_valid(): bool {
		return isset( $_GET['nltr'] );
	}

	/**
	 * Check is the URL a tracking URL for The Newsletter Plugin and if so, log in the user being tracked.
	 *
	 * @hooked plugins_loaded
	 *
	 * @see https://wordpress.org/plugins/newsletter/
	 * @see NewsletterStatistics::hook_wp_loaded()
	 *
	 * @return array{source:string, wp_user:WP_User|null, user_data?:array<string,string>}
	 */
	public function get_wp_user_array(): array {

		$result              = array();
		$result['source']    = 'The Newsletter Plugin';
		$result['wp_user']   = null;
		$result['user_data'] = array();

		if ( ! isset( $_GET['nltr'] ) ) {
			return $result;
		}

		if ( ! class_exists( NewsletterStatistics::class ) ) {
			$this->logger->debug( '`nltr` querystring parameter set but `NewsletterStatistics` class not found.' );
			return $result;
		}

		// This code mostly lifted from Newsletter plugin.

		// `FILTER_SANITIZE_STRING`/`FILTER_SANITIZE_STRIPPED` is deprecated since PHP 8.1.
		$input = sanitize_text_field( wp_unslash( $_GET['nltr'] ) );
		if ( '' === $input ) {
			return $result;
		}

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$nltr_param = base64_decode( $input );

		// e.g. "1;2;https://example.org;;0bda890bd176d3e219614dde964cb07f", or since Newsletter 9.3.7
		// "1;2;https://example.org;;1760000000.0bda890bd176d3e219614dde964cb07f".

		$parts     = explode( ';', $nltr_param );
		$email_id  = (int) array_shift( $parts );
		$user_id   = (int) array_shift( $parts );
		$signature = (string) array_pop( $parts );
		$anchor    = array_pop( $parts );

		$url = implode( ';', $parts );

		$verified = $this->verify_signature( $email_id . ';' . $user_id . ';' . $url . ';' . $anchor, $signature );

		if ( ! $verified ) {
			$this->logger->debug(
				'Could not verify Newsletter URL: ' . $nltr_param,
				array(
					'nltr_param' => $nltr_param,
					'email_id'   => $email_id,
					'user_id'    => $user_id,
					'signature'  => $signature,
					'anchor'     => $anchor,
					'url'        => $url,
				)
			);
			return $result;
		}
		// TODO: ban IP for repeated abuse.

		$tnp_user = Newsletter::instance()->get_user( $user_id );

		if ( is_null( $tnp_user ) ) {
			$this->logger->info( 'No user object returned for Newsletter user ' . $tnp_user );
			return $result;
		}

		$user_email_address = $tnp_user->email;

		$wp_user = get_user_by( 'email', $user_email_address );

		if ( $wp_user instanceof WP_User ) {

			$this->logger->info( "User `wp_user:{$wp_user->ID}` found from `tnp_user:{$tnp_user->id}` via Newsletter URL." );

			$result['wp_user'] = $wp_user;

		} else {

			// We have their email address but they have no account, record the
			// email address for WooCommerce UX and abandoned cart.
			$user_info = array(
				'email'      => $user_email_address,
				'first_name' => $tnp_user->name,
				'last_name'  => $tnp_user->surname,
			);

			$result['user_data'] = $user_info;

			$this->logger->debug(
				'No wp_user found for Newsletter user',
				array(
					'result' => $result,
				)
			);
		}

		return $result;
	}

	/**
	 * Check the tracking link's signature the same way the installed version of Newsletter does.
	 *
	 * Newsletter 9.3.7 changed the signature from `md5( $text . $key )` to
	 * `$expiry_time . '.' . md5( $text . $expiry_time . $key )`. Newsletter only identifies the subscriber
	 * (sets its own cookie) for an unexpired signature made with the current key, so only that logs in here.
	 *
	 * @see NewsletterStatistics::tracking()
	 * @see NewsletterStatistics::verify_signature()
	 *
	 * @param string $text The signed part of the `nltr` parameter: `email_id;user_id;url;anchor`.
	 * @param string $signature The signature from the `nltr` parameter.
	 */
	protected function verify_signature( string $text, string $signature ): bool {

		$newsletter_statistics = NewsletterStatistics::instance();

		// The method exists in the version phpstan scans, but not in Newsletter < 9.3.7.
		/** @phpstan-ignore function.alreadyNarrowedType */
		if ( method_exists( $newsletter_statistics, 'verify_signature' ) ) {
			// 0: invalid; 1: valid; 2: valid but expired, or without the timestamp.
			// Newsletter documents the parameters as `@param type`.
			/** @phpstan-ignore argument.type, argument.type */
			return 1 === $newsletter_statistics->verify_signature( $text, $signature );
		}

		// Newsletter < 9.3.7.
		$key = $newsletter_statistics->options['key'] ?? '';

		return '' !== $key && hash_equals( md5( $text . $key ), $signature );
	}
}
