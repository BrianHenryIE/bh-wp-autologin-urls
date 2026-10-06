<?php
/**
 * The template for the email sent when the "Email Magic Link" button is pressed.
 *
 * This can be overridden with the `bh_wp_autologin_urls_magic_link_email_template` filter.
 * Or by providing a templates/email/magic-link.php or email/magic-link.php file in your child theme.
 *
 * @see API::send_magic_link()
 *
 * @package brianhenryie/bh-wp-autologin-urls
 *
 * The following variables are available from where the parent function includes:
 *
 * @var string $autologin_url The magic login URL.
 * @var string $expires_in_friendly The length of time the link is valid for, e.g. "15 mins".
 * @var array{ip_address:string|null, browser:string|null, location:string|null} $request_details Details of the request that asked for the link, e.g. "Chrome on macOS", "1.2.3.4", "Dublin, Ireland". Each is null when unknown.
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

?>

<div style="text-align:center;">

	<div style="margin-left:auto; margin-right:auto; margin-top: 25px; margin-bottom: 25px;">

		<a style="padding: 50px;" href ="<?php echo esc_url( $autologin_url ); ?>">Log in</a>

	</div>

	<div>
		This link is valid for <?php echo esc_html( $expires_in_friendly ); ?>.
	</div>

	<?php if ( ! empty( $request_details ) ) : ?>
	<div style="margin-top: 25px; font-size: smaller; color: #555555;">
		<p>This link was requested from:</p>
		<?php if ( ! empty( $request_details['browser'] ) ) : ?>
			<div>Browser: <?php echo esc_html( $request_details['browser'] ); ?></div>
		<?php endif; ?>
		<?php if ( ! empty( $request_details['ip_address'] ) ) : ?>
			<div>IP address: <?php echo esc_html( $request_details['ip_address'] ); ?></div>
		<?php endif; ?>
		<?php if ( ! empty( $request_details['location'] ) ) : ?>
			<div>Location: <?php echo esc_html( $request_details['location'] ); ?></div>
		<?php endif; ?>
		<p>If this wasn't you, you can safely ignore this email.</p>
	</div>
	<?php endif; ?>
</div>
