<?php
/**
 * @package brianhenryie/bh-wp-autologin-urls
 */

$GLOBALS['project_root_dir']   = $project_root_dir  = dirname( __DIR__, 1 );
$GLOBALS['plugin_root_dir']    = $plugin_root_dir   = $project_root_dir;
$GLOBALS['plugin_name']        = $plugin_name       = basename( $project_root_dir );
$GLOBALS['plugin_name_php']    = $plugin_name_php   = $plugin_name . '.php';
$GLOBALS['plugin_path_php']    = $plugin_root_dir . '/' . $plugin_name_php;
$GLOBALS['plugin_basename']    = $plugin_name . '/' . $plugin_name_php;
$GLOBALS['wordpress_root_dir'] = $project_root_dir . '/wordpress';

// If there is a secrets file, load it here.
// Unsure how to define it in codeception.yml while also not committing to GitHub.
$env_secret = __DIR__ . '/../.env.secret';
if ( file_exists( $env_secret ) ) {

	$env_secret_fullpath      = realpath( $env_secret );
	$env_secret_relative_path = str_replace( codecept_root_dir(), '', $env_secret_fullpath );

	$secret_params = \Dotenv\Dotenv::createMutable( codecept_root_dir(), $env_secret_relative_path )->load();
}

\Alley_Interactive\Autoloader\Autoloader::generate(
	'BrianHenryIE\\WP_Autologin_URLs',
	$plugin_root_dir . '/src',
)->register();

require_once dirname( __DIR__ ) . '/vendor-prefixed/autoload.php';

/**
 * The plugin needs to be present in WP_CONTENT_DIR/plugins for templates to resolve
 * (previously handled by brianhenryie/composer-phpstorm symlinks config in composer.json).
 */
$plugin_symlink = $project_root_dir . '/wp-content/plugins/' . $plugin_name;
if ( ! file_exists( $plugin_symlink ) && is_dir( dirname( $plugin_symlink ) ) ) {
	symlink( $project_root_dir, $plugin_symlink );
}

$is_integration_test = array_reduce(
	(array) $_SERVER['argv'],
	fn( $carry, $arg ) => $carry || 'integration' === $arg,
	false
);
if ( $is_integration_test ) {
	global $arbitrary_plugins;
	$arbitrary_plugins = array(
		dirname( __DIR__, 1 ) . '/bh-wp-autologin-urls.php',
	);
}
