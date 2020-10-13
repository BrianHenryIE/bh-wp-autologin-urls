<?php
/**
 * The additions to the admin user-edit page.
 *
 * @link       https://BrianHenry.ie
 * @since      1.2.0
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/admin
 */

namespace BH_WP_Autologin_URLs\admin;

use BH_WP_Autologin_URLs\api\API_Interface;
use BH_WP_Autologin_URLs\BrianHenryIE\WPPB\WPPB_Object;
use WP_User;


/**
 * The extra field on the user edit page.
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/admin
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class User_Edit extends WPPB_Object {

	/**
	 * Core API methods to generate password/URL.
	 *
	 * @var API_Interface
	 */
	private $api;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string        $plugin_name The name of this plugin.
	 * @param   string        $version     The version of this plugin.
	 * @param   API_Interface $api The core plugin functions.
	 *
	 * @since   1.0.0
	 */
	public function __construct( $plugin_name, $version, $api ) {

		parent::__construct( $plugin_name, $version );

		$this->api = $api;
	}

	/**
	 * Add a field on the admin view of the user profile which contains a login URL.
	 * For use e.g. in support emails. ...tests.
	 *
	 * @hooked edit_user_profile
	 *
	 * @see wordpress/wp-admin/user-edit.php
	 *
	 * @param WP_User $profileuser The current WP_User object.
	 */
	public function make_password_available_on_user_page( $profileuser ): void {

		?>

		<table class="form-table bh-wp-autologin-urls" role="presentation">

			<tbody><tr>

				<th><label for="autologin-url">Single-use login URL</label></th>

				<td>

					<?php

					// TODO: If WooCommerce is installed, this should go to my-account.
					$append        = '/';
					$autologin_url = $this->api->add_autologin_to_url( get_site_url() . $append, $profileuser, WEEK_IN_SECONDS );

					?>

					<div class="user-edit-single-use-login-url">

						<span>
							<span class="text"><?php echo $autologin_url; ?></span>
							<input type="text" id="autologin-url" name="autologin-url" value="<?php echo $autologin_url; ?>"/>
						</span>
					</div>
				</td>

			</tr>


	</tbody></table>

		<?php
	}

}
