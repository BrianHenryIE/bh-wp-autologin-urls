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

use BH_WP_Autologin_URLs\BrianHenryIE\WP_Logger\Logs_Table;
use BH_WP_Autologin_URLs\BrianHenryIE\WP_Logger\Logger;


/** @var Logger $logger */
/** @var string $example_url */

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$active_tab = isset( $_GET['tab'] ) ? filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING ) : 'settings';
$active_tab = in_array( $active_tab, array( 'settings', 'logs' ), true ) ? $active_tab : 'settings';


?>

<div class="wrap bh-wp-autologin-urls">

	<div id="icon-themes" class="icon32"></div>

	<h2>Autologin URLs</h2>

	<?php settings_errors(); ?>

	<h3>Adds single-use passwords to URLs in WordPress emails.</h3>

	<p>With this plugin enabled, when emails are sent from WordPress (and its plugins) to users, a login code is added to each URL in the emails, which automatically logs users into the site when they visit. Modified URLs take the form <span class="url"><?php echo esc_url( $example_url ); ?></span>, and will often be transparent to users, due to HTML formatted (rich text) emails.</p>

	<h2 class="nav-tab-wrapper">
		<a href="options-general.php?page=bh-wp-autologin-urls&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
		<a href="options-general.php?page=bh-wp-autologin-urls&tab=logs" class="nav-tab <?php echo $active_tab === 'logs' ? 'nav-tab-active' : ''; ?>">Logs</a>
	</h2>

<?php if( 'settings' === $active_tab ) { ?>

	<p>To exclude emails with particular subjects from having autologin codes added, use regular expressions to match the subjects. e.g. password reset email does not need an autologin code added to its URLs.</p>

	<form method="POST" action="options.php">
		<?php
		settings_fields( 'bh-wp-autologin-urls' );
		do_settings_sections( 'bh-wp-autologin-urls' );
		submit_button();
		?>
	</form>

<?php
} elseif( 'logs' === $active_tab ) {

	$logs_table = new Logs_Table( $logger );

	$logs_table->prepare_items();
	$logs_table->display();

}
?>

	<p><a href="https://wordpress.org/support/plugin/bh-wp-autologin-urls">Support on WordPress.org</a> &#x2022; <a href="https://github.com/BrianHenryIE/BH-WP-Autologin-URLs">Code on GitHub</a> &#x2022; <a href="https://BrianHenry.ie">Plugin by BrianHenryIE</a></p>

</div>
