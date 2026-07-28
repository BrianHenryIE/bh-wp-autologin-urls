<?php
/**
 * Instantiates the integrations which can identify a user from the current request's querystring.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\API\Integrations;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\User_Finder_Interface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Throwable;

/**
 * Returns the first integration whose querystring is present in the request, with its constructor
 * dependencies resolved by reflection.
 */
class User_Finder_Factory {

	/**
	 * @var array<string, mixed>
	 */
	protected array $dependencies = array();

	/**
	 * The objects available to be injected into the integrations' constructors.
	 *
	 * @param API_Interface      $api      The plugin's public API.
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger   A PSR logger.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {
		$this->dependencies[ LoggerInterface::class ]    = $logger;
		$this->dependencies[ Settings_Interface::class ] = $settings;
		$this->dependencies[ API_Interface::class ]      = $api;
	}

	/**
	 * The first integration whose querystring is present, or null when this request is not an
	 * attempt to log in.
	 */
	public function get_user_finder(): ?User_Finder_Interface {

		/**
		 * @var array<string|User_Finder_Interface> $integrations
		 */
		$integrations = array(
			Autologin_URLs::class,
			Klaviyo::class,
			MailPoet::class,
			The_Newsletter_Plugin::class,
		);

		// TODO: Filter here to allow registering more.

		foreach ( $integrations as $integration ) {

			if ( $integration instanceof User_Finder_Interface ) {

				$integration_instance = $integration;

			} elseif ( class_exists( $integration ) ) {

				$class = new ReflectionClass( $integration );

				$constructor = $class->getConstructor();

				$construct_params = array();

				// This will be null if the object has no constructor, thus we have no parameters to look for/use.
				if ( ! is_null( $constructor ) ) {

					$parameters = $constructor->getParameters();

					foreach ( $parameters as $reflection_parameter ) {

						$reflection_type = $reflection_parameter->getType();

						if ( ! $reflection_type instanceof \ReflectionNamedType ) {
							continue 2;
						}

						$parameter_type = $reflection_type->getName();

						if ( isset( $this->dependencies[ $parameter_type ] ) ) {
							$construct_params[] = $this->dependencies[ $parameter_type ];
						} else {

							$default = $reflection_parameter->getDefaultValue();
							if ( $default ) {
								$construct_params[] = $default;
							} elseif ( $reflection_parameter->allowsNull() ) {
								$construct_params[] = null;
							} else {
								continue 2;
							}
							// TODO: Check for default / nullable.

						}
					}
				}

				try {
					$integration_instance = new $integration( ...$construct_params );
				} catch ( Throwable $exception ) {
					continue;
				}

				if ( ! ( $integration_instance instanceof User_Finder_Interface ) ) {
					continue;
				}
			} else {
				// TODO: Log warning it was neither an instance, or an instantiable string.
				continue;
			}

			if ( $integration_instance->is_querystring_valid() ) {
				return $integration_instance;
			}
		}

		return null;
	}
}
