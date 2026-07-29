<?php
/**
 * Autologin integration for MailPoet.
 *
 * If a request has `mailpoet_router` and `data` in the URL, check against MailPoet and try to find the user
 * account or user data.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\API\Integrations;

use BrianHenryIE\WP_Autologin_URLs\API\User_Finder_Interface;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Router\Router;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\SubscribersRepository;
use BrianHenryIE\WP_Autologin_URLs\Psr\Log\LoggerAwareInterface;
use BrianHenryIE\WP_Autologin_URLs\Psr\Log\LoggerAwareTrait;
use BrianHenryIE\WP_Autologin_URLs\Psr\Log\LoggerInterface;
use WP_User;

/**
 *
 * Since all querystring parameters are coming from links in emails, they will never have nonces.
 *
 * phpcs:disable WordPress.Security.NonceVerification.Recommended
 */
class MailPoet implements User_Finder_Interface, LoggerAwareInterface {
	use LoggerAwareTrait;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->setLogger( $logger );
	}

	/**
	 * Determine is the querystring needed for this integration present.
	 */
	public function is_querystring_valid(): bool {
		return isset( $_GET['mailpoet_router'] ) && isset( $_GET['data'] );
	}

	/**
	 * Check is the URL a tracking URL for MailPoet plugin and if so, log in the user being tracked.
	 *
	 * Uses MailPoet's verification process as the autologin code.
	 *
	 * @see LinkTokens::verifyToken()
	 *
	 * TODO: The time since the newsletter was sent should be respected for the expiry time.
	 *
	 * @hooked plugins_loaded
	 *
	 * @see https://wordpress.org/plugins/mailpoet/
	 *
	 * @return array{source:string, wp_user:\WP_User|null, user_data?:array<string,string>}
	 */
	public function get_wp_user_array(): array {

		$result              = array();
		$result['source']    = 'MailPoet';
		$result['wp_user']   = null;
		$result['user_data'] = array();

		if ( ! isset( $_GET['mailpoet_router'] ) ) {
			return $result;
		}

		if ( ! isset( $_GET['data'] ) ) {
			return $result;
		}

		if ( ! class_exists( Router::class ) ) {
			return $result;
		}

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$data = Router::decodeRequestData( sanitize_text_field( wp_unslash( $_GET['data'] ) ) );

		/**
		 * The required data from the MailPoet querystring.
		 *
		 * @see Links::transformUrlDataObject()
		 */
		$subscriber_id = $data[0];
		$request_token = $data[1];

		/**
		 * The MailPoet subscriber entity, null if none found.
		 *
		 * @var ?\MailPoet\Entities\SubscriberEntity $subscriber
		 */
		$subscriber = ContainerWrapper::getInstance()->get( SubscribersRepository::class )->findOneById( $subscriber_id );

		if ( is_null( $subscriber ) ) {
			return $result;
		}

		$valid = ContainerWrapper::getInstance()->get( LinkTokens::class )->verifyToken( $subscriber, $request_token );

		if ( ! $valid ) {
			return $result;
		}

		$user_email_address = $subscriber->getEmail();

		$wp_user = get_user_by( 'email', $user_email_address );

		if ( $wp_user instanceof WP_User ) {

			$this->logger->info( "User wp_user:{$wp_user->ID} found via mailpoet_user:{$subscriber_id} from MailPoet URL." );

			$result['wp_user'] = $wp_user;

		} else {

			// We have their email address but they have no account,
			// if WooCommerce is installed, record the email address for
			// UX and abandoned cart.
			$user_info = array(
				'first_name' => (string) $subscriber->getFirstName(),
				'last_name'  => (string) $subscriber->getLastName(),
			);

			$result['user_data'] = $user_info;

		}

		return $result;
	}
}
