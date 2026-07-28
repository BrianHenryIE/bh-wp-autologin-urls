<?php
/**
 * Reads .wp-env.json mappings and updates .idea/workspace.xml with a PhpStorm
 * server configuration for Xdebug path mappings.
 *
 * Run from the project root or the bin/ directory.
 *
 * @package BrianHenryIE\WP_Mailboxes
 */

// TODO: How to map WP CLI?

$project_dir = is_file( dirname( __DIR__ ) . '/.wp-env.json' )
	? dirname( __DIR__ )
	: getcwd();

$wp_env_file    = $project_dir . '/.wp-env.json';
$workspace_file = $project_dir . '/.idea/workspace.xml';

if ( ! file_exists( $wp_env_file ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "Error: .wp-env.json not found at {$wp_env_file}\n";
	exit( 1 );
}

if ( ! file_exists( $workspace_file ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "Error: .idea/workspace.xml not found at {$workspace_file}\n";
	exit( 0 );
}

// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
$wp_env_json = file_get_contents( $wp_env_file );
if ( false === $wp_env_json ) {
	echo "Error: Could not read .wp-env.json\n";
	exit( 1 );
}

$wp_env = json_decode( $wp_env_json, true );
if ( ! is_array( $wp_env ) ) {
	echo "Error: Failed to parse .wp-env.json\n";
	exit( 1 );
}

$port_raw    = $wp_env['port'] ?? 8888;
$port        = is_int( $port_raw ) ? $port_raw : 8888;
$server_name = "localhost:{$port}";

$mappings_raw  = $wp_env['mappings'] ?? array();
$raw_mappings  = is_array( $mappings_raw ) ? $mappings_raw : array();
$path_mappings = array();

foreach ( $raw_mappings as $container_rel_path => $local_rel_path ) {
	if ( ! is_string( $container_rel_path ) || ! is_string( $local_rel_path ) ) {
		continue;
	}

	$remote_root = normalize_absolute_path( '/var/www/html/' . $container_rel_path );

	if ( '.' === $local_rel_path ) {
		$local_root = '$PROJECT_DIR$';
	} elseif ( str_starts_with( $local_rel_path, './' ) ) {
		$local_root = '$PROJECT_DIR$/' . substr( $local_rel_path, 2 );
	} else {
		$local_root = '$PROJECT_DIR$/' . ltrim( $local_rel_path, '/' );
	}

	$path_mappings[] = array(
		'local'  => $local_root,
		'remote' => $remote_root,
	);
}

if ( is_dir( $project_dir . '/wordpress' ) ) {
	$path_mappings[] = array(
		'local'  => '$PROJECT_DIR$/wordpress',
		'remote' => '/var/www/html',
	);
}

$dom = new DOMDocument( '1.0', 'UTF-8' );
// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
$dom->preserveWhiteSpace = true;
// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
$dom->formatOutput = false;

if ( false === $dom->load( $workspace_file ) ) {
	echo "Error: Failed to load .idea/workspace.xml\n";
	exit( 1 );
}

$xpath = new DOMXPath( $dom );

$php_servers_component = find_element_by_xpath( $xpath, '//component[@name="PhpServers"]' );
if ( null === $php_servers_component ) {
	$php_servers_component = $dom->createElement( 'component' );
	$php_servers_component->setAttribute( 'name', 'PhpServers' );
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$root = $dom->documentElement;
	if ( null === $root ) {
		echo "Error: workspace.xml has no root element\n";
		exit( 1 );
	}
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$last_child = $root->lastChild;
	if ( null !== $last_child ) {
		$root->insertBefore( $dom->createTextNode( "\n  " ), $last_child );
		$root->insertBefore( $php_servers_component, $last_child );
	} else {
		$root->appendChild( $dom->createTextNode( "\n  " ) );
		$root->appendChild( $php_servers_component );
		$root->appendChild( $dom->createTextNode( "\n" ) );
	}
}

$servers_element = find_element_by_xpath( $xpath, '//component[@name="PhpServers"]/servers' );
if ( null === $servers_element ) {
	$servers_element = $dom->createElement( 'servers' );
	$php_servers_component->appendChild( $dom->createTextNode( "\n    " ) );
	$php_servers_component->appendChild( $servers_element );
	$php_servers_component->appendChild( $dom->createTextNode( "\n  " ) );
}

$server_element = find_element_by_xpath(
	$xpath,
	"//component[@name='PhpServers']/servers/server[@name='{$server_name}']"
);
if ( null === $server_element ) {
	$server_element = $dom->createElement( 'server' );
	$server_element->setAttribute( 'host', 'localhost' );
	$server_element->setAttribute( 'id', generate_uuid() );
	$server_element->setAttribute( 'name', $server_name );
	$server_element->setAttribute( 'port', (string) $port );
	$server_element->setAttribute( 'use_path_mappings', 'true' );
	$servers_element->appendChild( $dom->createTextNode( "\n      " ) );
	$servers_element->appendChild( $server_element );
	$servers_element->appendChild( $dom->createTextNode( "\n    " ) );
} else {
	$server_element->setAttribute( 'use_path_mappings', 'true' );
}

$path_mappings_element = find_element_by_xpath(
	$xpath,
	"//component[@name='PhpServers']/servers/server[@name='{$server_name}']/path_mappings"
);
if ( null === $path_mappings_element ) {
	$path_mappings_element = $dom->createElement( 'path_mappings' );
	$server_element->appendChild( $dom->createTextNode( "\n        " ) );
	$server_element->appendChild( $path_mappings_element );
	$server_element->appendChild( $dom->createTextNode( "\n      " ) );
}

$existing_by_remote = array();
$existing_nodes     = $xpath->query(
	"//component[@name='PhpServers']/servers/server[@name='{$server_name}']/path_mappings/mapping"
);
if ( false !== $existing_nodes ) {
	foreach ( $existing_nodes as $node ) {
		if ( ! ( $node instanceof DOMElement ) ) {
			continue;
		}
		$existing_by_remote[ $node->getAttribute( 'remote-root' ) ] = $node;
	}
}

$desired_remote_roots = array_column( $path_mappings, 'remote' );
foreach ( $existing_by_remote as $remote_root => $node ) {
	if ( ! in_array( $remote_root, $desired_remote_roots, true ) ) {
		$path_mappings_element->removeChild( $node );
	}
}

foreach ( $path_mappings as $mapping ) {
	if ( isset( $existing_by_remote[ $mapping['remote'] ] ) ) {
		$existing_by_remote[ $mapping['remote'] ]->setAttribute( 'local-root', $mapping['local'] );
	} else {
		$mapping_element = $dom->createElement( 'mapping' );
		$mapping_element->setAttribute( 'local-root', $mapping['local'] );
		$mapping_element->setAttribute( 'remote-root', $mapping['remote'] );
		$path_mappings_element->appendChild( $dom->createTextNode( "\n          " ) );
		$path_mappings_element->appendChild( $mapping_element );
	}
}

$result = $dom->save( $workspace_file );
if ( false === $result ) {
	echo "Error: Failed to write .idea/workspace.xml\n";
	exit( 1 );
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo "Updated {$workspace_file} (server: {$server_name})\n";
foreach ( $path_mappings as $mapping ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "  {$mapping['local']} -> {$mapping['remote']}\n";
}

/**
 * Resolves '..' segments in an absolute path.
 *
 * @param string $path Absolute path potentially containing '..' segments.
 */
function normalize_absolute_path( string $path ): string {
	$parts      = explode( '/', $path );
	$normalized = array();
	foreach ( $parts as $part ) {
		if ( '..' === $part ) {
			array_pop( $normalized );
		} elseif ( '' !== $part && '.' !== $part ) {
			$normalized[] = $part;
		}
	}
	return '/' . implode( '/', $normalized );
}

/**
 * Finds the first DOMElement matching an XPath query, or returns null.
 *
 * @param DOMXPath $xpath The DOMXPath instance.
 * @param string   $query The XPath query string.
 */
function find_element_by_xpath( DOMXPath $xpath, string $query ): ?DOMElement {
	$nodes = $xpath->query( $query );
	if ( false === $nodes || 0 === $nodes->length ) {
		return null;
	}
	$node = $nodes->item( 0 );
	return ( $node instanceof DOMElement ) ? $node : null;
}

/**
 * Generates a random UUID v4.
 */
function generate_uuid(): string {
	return sprintf(
		'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		random_int( 0, 0xffff ),
		random_int( 0, 0xffff ),
		random_int( 0, 0xffff ),
		random_int( 0, 0x0fff ) | 0x4000,
		random_int( 0, 0x3fff ) | 0x8000,
		random_int( 0, 0xffff ),
		random_int( 0, 0xffff ),
		random_int( 0, 0xffff )
	);
}
