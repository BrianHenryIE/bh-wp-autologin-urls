<?php
/**
 * Runs after WordPress has been initialised (after plugins are loaded) and before tests are run.
 *
 * @package           Plugin_Package_Name
 */

add_filter(
	'site_url',
	function( $site_url ) {
		return str_replace( 'localhost:8080', 'example.org', $site_url );
	}
);
