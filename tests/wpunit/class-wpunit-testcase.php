<?php

namespace BrianHenryIE\WP_Autologin_URLs;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\Psr\Log\LoggerInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

class WPUnit_Testcase extends WPTestCase {

	protected LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();

		// Use the Strauss-prefixed logger interface for this project.
		$this->logger = new class() extends ColorLogger implements LoggerInterface {
		};
	}

	protected function get_installed_major_version( string $plugin_basename ): int {
		$plugin_headers = get_plugin_data( codecept_root_dir( WP_PLUGIN_DIR . '/' . $plugin_basename ) );
		if ( 1 === preg_match( '/(\d+)/', $plugin_headers['Version'], $output_array ) ) {
			return (int) $output_array[1];
		} else {
			return -1;
		}
	}

	protected function is_activate_and_major_version( string $plugin_basename, int $major_version ): bool {
		$is_active = is_plugin_active( $plugin_basename );
		if ( ! $is_active ) {
			return false;
		}
		return $this->get_installed_major_version( $plugin_basename ) === $major_version;
	}
}
