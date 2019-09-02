<?php
/**
 * Tests for Plugins_Page_Test. Tests the settings link is correctly added on plugins.php.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\admin;

use \DOMDocument;

/**
 * Class Plugins_Page_Test
 *
 * @see WP_Plugins_List_Table::single_row()
 */
class Plugins_Page_Test extends \WP_UnitTestCase {

	/**
	 * Verify the content of the action links.
	 *
	 * TODO: The Deactivate link isn't returned when the filter is run in the test, suggesting the test's
	 * not being run on plugins.php page as it should. set_current_screen( 'plugins' ); ?
	 *
	 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	 * phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
	 */
	public function test_plugin_action_links() {

		$expected_anchor    = get_site_url() . '/wp-admin/options-general.php?page=bh-wp-autologin-urls';
		$expected_link_text = 'Settings';

		global $plugin_basename;

		$filter_name = 'plugin_action_links_' . $plugin_basename;

		set_current_screen( 'plugins' );

		$this->go_to( get_site_url() . '/wp-admin/plugins.php' );

		$plugin_action_links = apply_filters( $filter_name, array() );

		$this->assertGreaterThan( 0, count( $plugin_action_links ), 'The plugin action link was definitely not added.' );

		$first_link = $plugin_action_links[0];

		$dom = new \DOMDocument();

		@$dom->loadHtml( mb_convert_encoding( $first_link, 'HTML-ENTITIES', 'UTF-8' ) );

		$nodes = $dom->getElementsByTagName( 'a' );

		$this->assertEquals( 1, $nodes->length );

		$node = $nodes->item( 0 );

		$actual_anchor    = $node->getAttribute( 'href' );
		$actual_link_text = $node->nodeValue;

		$this->assertEquals( $expected_anchor, $actual_anchor );
		$this->assertEquals( $expected_link_text, $actual_link_text );
	}

	/**
	 * Verify the link to the plugin's GitHub is correctly added. This was originally for
	 * a non WordPress Plugin Directory plugin, but seems OK to use here.
	 */
	public function test_plugin_meta_github_link() {

		$expected = '<a target="_blank" href="https://github.com/BrianHenryIE/BH-WP-Autologin-URLs">View plugin on GitHub</a>';

		$filter_name = 'plugin_row_meta';

		global $plugin_basename;

		$plugin_action_links = apply_filters( $filter_name, array(), $plugin_basename, array(), 'active' );

		$this->assertContains( $expected, $plugin_action_links );
	}

}
