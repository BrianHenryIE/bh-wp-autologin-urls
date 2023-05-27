<?php
/**
 * Template to display an autologin url on the admin user-edit/profile UI.
 *
 * @see /wp-admin/user-edit.php?user_id=2
 * @see /wp-admin/profile.php
 *
 * @package brianhenryie/bh-wp-autologin-urls
 *
 * @var string $autologin_url The generated URL.
 */

?>

<table class="form-table bh-wp-autologin-urls" role="presentation">
	<tbody><tr>
		<th><label for="autologin-url">Single-use login URL</label></th>
			<td>
				<div class="user-edit-single-use-login-url">
					<span>
						<span class="text"><?php echo esc_url( $autologin_url ); ?></span>
						<input type="text" id="autologin-url" name="autologin-url" value="<?php echo esc_url( $autologin_url ); ?>"/>
					</span>
				</div>
			</td>
		</tr>
	</tbody>
</table>

<script>
var autologin_url = '<?php echo esc_url( $autologin_url ); ?>';
</script>
