<?php
/**
 * PHPUnit bootstrap file for wpunit tests. Since the plugin will not be otherwise autoloaded.
 *
 * @package           BH_WP_Autologin_URLs
 */

global $plugin_root_dir;
require_once $plugin_root_dir . '/autoload.php';

// Codeception/WP-Browser tests return localhost as the site_url, whereas WP_UnitTestCase was returning example.org.
add_filter(
	'pre_option_siteurl',
	function (): string {
		return 'http://example.org';
	}
);
