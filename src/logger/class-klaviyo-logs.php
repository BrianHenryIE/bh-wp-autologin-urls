<?php
/**
 * When printing the logs, replace instances of the Klaviyo user with links to the user profile.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\Logger;

use BrianHenryIE\WP_Autologin_URLs\WP_Logger\Admin\Logs_List_Table;
use BrianHenryIE\WP_Autologin_URLs\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Autologin_URLs\WP_Logger\Logger_Settings_Interface;

/**
 * Filters the column output of the WP_List_Table.
 *
 * @see Logs_List_Table::column_default()
 */
class Klaviyo_Logs {

	/**
	 * Update `klaviyo:a1b2c3` with links Klaviyo.com search.
	 *
	 * `klaviyo:{$wp_user->ID} {$klaviyo_user_id}`
	 *
	 * There didn't seem to be any way to link directly to the profile.
	 *
	 * @hooked bh-wp-autologin-urls_bh_wp_logger_column
	 *
	 * @param string                                                                        $column_output The column output so far.
	 * @param array{time:string, level:string, message:string, context:array<string,mixed>} $item The log entry row.
	 * @param string                                                                        $column_name The current column name.
	 * @param Logger_Settings_Interface                                                     $logger_settings The logger settings.
	 * @param BH_WP_PSR_Logger                                                              $logger The logger API instance.
	 *
	 * @return string
	 */
	public function link_to_klaviyo_profile_search( string $column_output, array $item, string $column_name, Logger_Settings_Interface $logger_settings, BH_WP_PSR_Logger $logger ): string {

		if ( 'message' !== $column_name ) {
			return $column_output;
		}

		$callback = function ( array $matches ): string {

			$wp_user = get_user_by( 'id', $matches[1] );

			if ( false === $wp_user ) {
				return $matches[0];
			}

			$url  = 'https://www.klaviyo.com/search?q=' . $wp_user->user_email;
			$link = "<a href=\"{$url}\">Klaviyo user {$matches[2]}</a>";

			return $link;
		};

		$message = preg_replace_callback( '/klaviyo:(\d+)\s([^\s]+)/', $callback, $column_output ) ?? $column_output;

		return $message;
	}
}
