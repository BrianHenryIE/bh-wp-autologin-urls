<?php
/**
 * Runs after WordPress has been initialised (after plugins are loaded) and before tests are run.
 *
 * @package           Plugin_Package_Name
 */

add_filter(
	'pre_option_siteurl',
	function(): string {
		return 'http://example.org';
	}
);
