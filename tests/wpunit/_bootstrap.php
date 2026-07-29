<?php
/**
 * PHPUnit bootstrap file for wpunit tests. Since the plugin will not be otherwise autoloaded.
 *
 * @package           BH_WP_Autologin_URLs
 */

// Codeception/WP-Browser tests return localhost as the site_url, whereas WP_UnitTestCase was returning example.org.
add_filter(
	'pre_option_siteurl',
	function (): string {
		return 'http://example.org';
	}
);

/**
 * Newer MailPoet versions synchronize WordPress users to MailPoet subscribers inside their own
 * Doctrine database transactions. The explicit `START TRANSACTION`/`COMMIT` implicitly commits
 * the WP test framework's per-test wrapping transaction, breaking test isolation (rollback),
 * and users created without an email address throw a SubscriberEntity validation exception.
 * Unhook the synchronization; tests that need a MailPoet subscriber create one explicitly.
 */
if ( class_exists( \MailPoet\DI\ContainerWrapper::class ) ) {
	$mailpoet_wp_segment = \MailPoet\DI\ContainerWrapper::getInstance()->get( \MailPoet\Segments\WP::class );
	foreach ( array( 'user_register', 'added_existing_user', 'profile_update', 'add_user_role', 'set_user_role' ) as $mailpoet_synchronize_user_hook_name ) {
		remove_action( $mailpoet_synchronize_user_hook_name, array( $mailpoet_wp_segment, 'synchronizeUser' ), 6 );
	}
	unset( $mailpoet_wp_segment, $mailpoet_synchronize_user_hook_name );
}
