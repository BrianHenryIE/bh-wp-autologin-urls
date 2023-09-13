<?php
/**
 * Delete expired codes.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Defines the cron action name.
 * Schedules the cron job.
 * Handles the action when called from wp_cron.
 * Uses API class to delete codes.
 */
class Cron {
	use LoggerAwareTrait;

	const DELETE_EXPIRED_CODES_JOB_NAME = 'bh_wp_autologin_urls_delete_expired_codes';

	/**
	 * An instance of the plugin API which will delete the expired codes.
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Constructor.
	 *
	 * @param API_Interface   $api The class that will handle the actual deleting.
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( API_Interface $api, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->api = $api;
	}

	/**
	 * Schedule the delete_expired_codes job, if it is not already scheduled.
	 *
	 * @hooked plugins_loaded
	 */
	public function schedule_job(): void {

		$hook = self::DELETE_EXPIRED_CODES_JOB_NAME;

		$next_scheduled_event = wp_next_scheduled( $hook );
		if ( false === $next_scheduled_event ) {
			wp_schedule_event( time(), 'daily', $hook );
		}
	}

	/**
	 * Simple function to invoke from cron.
	 *
	 * @uses \BrianHenryIE\WP_Autologin_URLs\API_Interface::delete_expired_codes()
	 * @hooked bh_wp_autologin_urls_delete_expired_codes
	 * @see self::DELETE_EXPIRED_CODES_JOB_NAME
	 */
	public function delete_expired_codes(): void {

		$action_name = current_action();
		$this->logger->debug( 'bh-wp-autologin-urls delete_expired_codes cron jobs started', array( 'action' => $action_name ) );

		$this->api->delete_expired_codes();
	}
}
