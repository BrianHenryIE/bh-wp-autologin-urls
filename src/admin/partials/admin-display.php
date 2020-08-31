<?php
/**
 * Provides the admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/admin/partials
 */

?>

<div class="wrap bh-wp-autologin-urls">

	<h1>Autologin URLs</h1>

	<h3>Adds single-use passwords to URLs in WordPress emails.</h3>

	<p>With this plugin enabled, when emails are sent from WordPress (and its plugins) to users, a login code is added to each URL in the emails, which automatically logs users into the site when they visit. Modified URLs take the form <span class="url"><?php echo esc_url( $example_url ); ?></span>, and will often be transparent to users, due to HTML formatted (rich text) emails.</p>

	<p>To exclude emails with particular subjects from having autologin codes added, use regular expressions to match the subjects. e.g. password reset email does not need an autologin code added to its URLs.</p>

	<form method="POST" action="options.php">
		<?php
		settings_fields( 'bh-wp-autologin-urls' );
		do_settings_sections( 'bh-wp-autologin-urls' );
		submit_button();
		?>
	</form>

	<p><a href="https://wordpress.org/support/plugin/bh-wp-autologin-urls">Support on WordPress.org</a> &#x2022; <a href="https://github.com/BrianHenryIE/BH-WP-Autologin-URLs">Code on GitHub</a> &#x2022; <a href="https://BrianHenry.ie">Plugin by BrianHenryIE</a></p>

</div>
