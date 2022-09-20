<?php
/**
 * @see https://developers.klaviyo.com/en/reference/exchange
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\API\Integrations;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\User_Finder_Interface;
use BrianHenryIE\WP_Autologin_URLs\Klaviyo\API\ProfilesApi;
use BrianHenryIE\WP_Autologin_URLs\Klaviyo\Client;
use Exception;
use Klaviyo\ApiException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WP_User;

/**
 * The $_GET data is coming from links clicked outside WordPress; it will not have a nonce.
 *
 * phpcs:disable WordPress.Security.NonceVerification.Recommended
 */
class Klaviyo implements User_Finder_Interface, LoggerAwareInterface {
	use LoggerAwareTrait;

	const QUERYSTRING_PARAMETER_NAME = '_kx';

	protected Client $client;

	/**
	 * Constructor.
	 *
	 * @param Settings_Interface $settings The plugin settings, for the Klaviyo private key.
	 * @param LoggerInterface    $logger A PSR logger.
	 * @param ?Client            $client The Klaviyo SDK.
	 *
	 * @throws Exception When Klaviyo private key has not been set.
	 */
	public function __construct( Settings_Interface $settings, LoggerInterface $logger, ?Client $client = null ) {

		$this->setLogger( $logger );

		$private_key = $settings->get_klaviyo_private_api_key();

		if ( empty( $private_key ) ) {
			$this->logger->debug( 'Klaviyo private key not set' );
			throw new Exception( 'Klaviyo private key not set' );
		}

		$this->setLogger( $logger );
		$this->client = $client ?? new Client(
			$private_key,
			0,
			3
		);

	}

	/**
	 * Determine is the querystring needed for this integration present.
	 */
	public function is_querystring_valid(): bool {
		return ! empty( $_GET[ self::QUERYSTRING_PARAMETER_NAME ] );
	}

	/**
	 *
	 *
	 * @return array{source:string, wp_user:\WP_User|null, user_data?:array<string,string>}
	 */
	public function get_wp_user_array(): array {

		$result              = array();
		$result['source']    = 'Klaviyo';
		$result['wp_user']   = null;
		$result['user_data'] = array();

		/**
		 * This was checked above.
		 *
		 * @see Klaviyo::is_querystring_valid()
		 */
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$klaviyo_parameter = sanitize_text_field( wp_unslash( $_GET[ self::QUERYSTRING_PARAMETER_NAME ] ) );

		$user_data = $this->get_user_data( $klaviyo_parameter );

		$result['user_data'] = $user_data;

		if ( ! isset( $user_data['email'] ) ) {
			$this->logger->debug( 'Email not returned from Klaviyo' );
			return $result;
		}

		$user_email = $user_data['email'];

		$user = get_user_by( 'email', $user_email );

		if ( ! ( $user instanceof WP_User ) ) {
			$this->logger->debug( 'No WP_User account found for Klaviyo user ' . $user_email );
			return $result;
		}

		$this->logger->info( "User {$user->user_login} found via Klaviyo Email URL." );

		$result['wp_user'] = $user;

		return $result;
	}


	protected function get_user_data( string $kx_parameter ): array {

		/** @var ProfilesApi $profiles */
		$profiles = $this->client->Profiles;

		try {
			/** @var array{id?:string} $response */
			$response = $profiles->exchange( array( 'exchange_id' => $kx_parameter ) );
		} catch ( ApiException $exception ) {
			$this->logger->error( $exception->getMessage() );
			return array();
		}

		if ( ! isset( $response['id'] ) ) {
			$this->logger->debug( 'No Klaviyo profile id found for _kx ' . $kx_parameter );
			// Throw exception?
			return array();
		}

		$klaviyo_user_id = $response['id'];

		/**
		 * @var array{'$address1':string,'$address2':string,'$city':string,'$country':string,'$region':string,'$zip':string,'$organization':string,'$first_name':string,'$email':string,'$phone_number':string,'$title':string,'$last_name':string} $klaviyo_user
		 */
		$klaviyo_user = $profiles->getProfile( $klaviyo_user_id );

		$user_data_map = array(
			'$address1'     => 'address',
			'$address2'     => 'address_2',
			'$city'         => 'city',
			'$country'      => 'country',
			'$region'       => 'state',
			'$zip'          => 'postcode',
			'$organization' => 'company',
			'$first_name'   => 'first_name',
			'$email'        => 'email',
			'$phone_number' => 'billing_phone',
			'$last_name'    => 'last_name',
		);

		$user_data = array();

		foreach ( $klaviyo_user as $key => $value ) {
			if ( isset( $user_data_map[ $key ] ) ) {
				$user_data[ $user_data_map[ $key ] ] = (string) $value;
			}
		}

		return $user_data;
	}
}
