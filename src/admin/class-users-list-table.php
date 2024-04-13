<?php
/**
 * Add a "send magic link" button to the users list table
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use WP_User;

/**
 * Hooks into WP_User_List_Table to add the link; handles sending the link on the page load.
 */
class Users_List_Table {

	protected Settings_Interface $settings;

	protected API_Interface $api;

	/**
	 * Constructor.
	 *
	 * @param API_Interface      $api For sending the magic link email.
	 * @param Settings_Interface $settings The check is the setting enabled.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings ) {
		$this->api      = $api;
		$this->settings = $settings;
	}

	/**
	 * Add the link under the user's name in the users list table.
	 *
	 * @hooked user_row_actions
	 * @see \WP_Users_List_Table::single_row()
	 *
	 * @param array<string,string> $actions An array of action links to be displayed.
	 *                   Default 'Edit', 'Delete' for single site, and 'Edit', 'Remove' for Multisite.
	 * @param WP_User              $user_object WP_User object for the currently listed user.
	 *
	 * @return array<string,string>
	 */
	public function add_magic_email_link( array $actions, WP_User $user_object ): array {

		// Add a link to send the user a reset password link by email.
		if ( get_current_user_id() !== $user_object->ID
			&& current_user_can( 'edit_user', $user_object->ID )
			&& $this->settings->is_magic_link_enabled()
		) {
			$actions['sendmagiclink'] = "<a class='sendmagiclink' href='" . wp_nonce_url( "users.php?action=sendmagiclink&amp;user=$user_object->ID", self::class ) . "'>" . __( 'Send magic login email' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Handle the page load where the magic link is sent.
	 *
	 * E.g. `/wp-admin/users.php?action=sendmagiclink&user=2&_wpnonce=20d555e577`.
	 *
	 * @hooked admin_init
	 *
	 * @see users.php:636
	 */
	public function send_magic_email_link(): void {

		global $pagenow;
		if ( 'users.php' !== $pagenow ) {
			return;
		}

		if ( ! check_ajax_referer( self::class, false, false ) ) {
			return;
		}

		if ( ! isset( $_GET['action'], $_GET['user'] ) ) {
			return;
		}

		if ( 'sendmagiclink' !== sanitize_key( $_GET['action'] ) ) {
			return;
		}

		$user_id = absint( $_GET['user'] );

		$user = get_user_by( 'id', $user_id );

		if ( ! $user instanceof WP_User ) {
			return;
		}

		$result = $this->api->send_magic_link( $user->user_email );

		add_action(
			'admin_notices',
			function () use ( $result, $user ) {
				$this->print_admin_notice( $result, $user );
			}
		);
	}

	/**
	 * Print the success/failure admin notice.
	 *
	 * @param array{username_or_email_address:string, expires_in:int, expires_in_friendly:string, wp_user?:WP_User, template_path?:string, success:bool, error?:bool, message?:string} $result
	 * @param WP_User                                                                                                                                                                  $user
	 */
	public function print_admin_notice( array $result, WP_User $user ): void {

		$notice_type = $result['success'] ? 'success' : 'error';
		echo '<div id="message" class="updated ' . esc_attr( $notice_type ) . ' is-dismissible"><p>';

		if ( $result['success'] ) {
			echo 'Magic login email sent to ';
		} else {
			echo 'Error sending magic login email to ';
		}

		printf(
			'<a href="%s">%s</a>',
			esc_url( get_edit_user_link( $user->ID ) ),
			esc_html( $user->display_name )
		);

		printf(
			' (<a href="mailto:%s">%s</a>).',
			esc_url( $user->user_email ),
			esc_html( $user->user_email ),
		);

		echo '</p></div>';
	}
}
