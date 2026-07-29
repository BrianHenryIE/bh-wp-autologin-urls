<?php

namespace BrianHenryIE\WP_Autologin_URLs;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\Psr\Log\LoggerInterface;
use Codeception\Test\Unit;
use WP_Mock;
use function Patchwork\restoreAll;

class Unit_Testcase extends Unit {

	protected LoggerInterface $logger;

	protected function setup(): void {
		WP_Mock::setUp();

		// Use the Strauss-prefixed logger interface for this project.
		$this->logger = new class() extends ColorLogger implements LoggerInterface {
		};
	}

	protected function tearDown(): void {
		parent::_tearDown();
		WP_Mock::tearDown();
		restoreAll();
	}
}
