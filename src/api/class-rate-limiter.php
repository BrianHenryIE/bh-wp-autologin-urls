<?php
/**
 * A rate limiter which separates recording an event from checking the limit.
 *
 * `nikolaposa/rate-limit` only exposes `limit()`/`limitSilently()`, both of which increment the
 * counter as a side effect of checking it. Autologin URLs needs to count only *failed* login
 * attempts while checking the limit on every attempt, so the two operations must be independent.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\API;

use BrianHenryIE\WP_Autologin_URLs\WP_Rate_Limiter\WordPress_Rate_Limiter;

/**
 * Exposes the parent's protected count/key methods as a read-only limit check.
 */
class Rate_Limiter extends WordPress_Rate_Limiter {

	/**
	 * Has the identifier already reached the allowed number of recorded events?
	 *
	 * Read-only: unlike `limitSilently()` this does not record anything.
	 *
	 * @param string $identifier An IP address or user id to rate limit by.
	 */
	public function is_limit_exceeded( string $identifier ): bool {
		return $this->getCurrentCount( $this->key( $identifier ) ) >= $this->rate->getOperations();
	}

	/**
	 * Record one event against the identifier.
	 *
	 * @param string $identifier An IP address or user id to rate limit by.
	 *
	 * @throws \RuntimeException When the underlying transient cannot be written.
	 */
	public function record( string $identifier ): void {
		$this->limitSilently( $identifier );
	}
}
