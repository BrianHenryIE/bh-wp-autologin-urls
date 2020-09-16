<?php
/**
 * Subclass of Klogger for configuring a singleton.
 *
 * @since             1.4.0
 * @package           BH_WP_Autologin_URLs
 */

namespace BH_WP_Autologin_URLs;

use \BH_WP_Autologin_URLs\Katzgrau\KLogger\Logger as Klogger;
use BH_WP_Autologin_URLs\Psr\Log\LogLevel;
use ReflectionClass;

/**
 * Instantiate the logger to output to wp-content/logs/bh-wp-autologin-urls-DATE.log.
 *
 * Class Logger
 *
 * @package BH_WP_Autologin_URLs
 */
class Logger extends KLogger {

	/**
	 * Class instance
	 *
	 * @var Logger $instace
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @return KLogger
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Create an instance of the logger, setting the log file filename to match the plugin slug.
	 *
	 * @param string $log_level
	 */
	protected function __construct( $log_level = LogLevel::INFO ) {

		$log_file_directory = WP_CONTENT_DIR . '/logs';

		$plugin_slug     = 'bh-wp-autologin-urls';
		$log_file_prefix = "$plugin_slug-";

		$options = array(
			'extension' => 'log',
			'prefix'    => $log_file_prefix,

		);

		parent::__construct( $log_file_directory, $log_level, $options );
	}

	public static function set_log_level( string $log_level ) {

		$reflection = new ReflectionClass( LogLevel::class );
		if ( ! in_array( $log_level, $reflection->getConstants(), true ) ) {
			$log_level = LogLevel::INFO;
		}

		self::get_instance()->setLogLevelThreshold( $log_level );

	}

}
