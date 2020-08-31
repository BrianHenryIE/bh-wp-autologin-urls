<?php
/**
 * This settings field is a list of pairs of regex patterns and email subjects they match.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/admin/partials
 */

namespace BH_WP_Autologin_URLs\admin\partials;

use BH_WP_Autologin_URLs\includes\Settings_Interface;
use BH_WP_Autologin_URLs\includes\Settings;

/**
 * Class Regex_Subject_Filters
 */
class Regex_Subject_Filters extends Settings_Section_Element_Abstract {

	/**
	 * Regex_Subject_Filters constructor.
	 *
	 * @param string             $plugin_name The plugin slug.
	 * @param string             $version  The plugin version.
	 * @param string             $settings_page The slug of the page this setting is being displayed on.
	 * @param Settings_Interface $settings The existing settings saved in the database.
	 */
	public function __construct( $plugin_name, $version, $settings_page, $settings ) {

		parent::__construct( $plugin_name, $version, $settings_page );

		$this->value = $settings->get_disallowed_subjects_regex_dictionary();

		$this->id    = Settings::SUBJECT_FILTER_REGEX_DICTIONARY;
		$this->title = __( 'Regex subject filters:', 'bh-wp-autologin-urls' );
		$this->page  = $settings_page;

		$this->add_settings_field_args['helper']       = __( 'Emails whose subjects match these regex patterns will not have autologin codes added.', 'bh-wp-autologin-urls' );
		$this->add_settings_field_args['supplemental'] = __( 'Take care to include leading and trailing / and use ^ and $ as appropriate.', 'bh-wp-autologin-urls' ) . ' Use <a href="https://www.phpliveregex.com/#tab-preg-match">phpliveregex.com</a> to test.';
		$this->add_settings_field_args['default']      = array();
	}

	/**
	 * Prints the html text input pairs for regex/subject notes displayed in the right-hand column of the settings table.
	 *
	 * @param array $arguments The data registered with add_settings_field().
	 */
	public function print_field_callback( $arguments ) {

		$value = $this->value;

		if ( empty( $value ) ) {
			$value = $arguments['default'];
		}

		$options_markup = '';
		$iterator       = 0;

		$options_markup .= '<p>' . $arguments['helper'] . '</p>';

		$options_markup .= '<table class="regex">';

		foreach ( $value as $regex => $note ) {

			if ( $regex === $note ) {
				$note = '';
			}

			$options_markup .= $this->input_pair_table_row( $this->id, $iterator, $regex, $note );

			$iterator ++;

		}

		// And once for new input.
		$options_markup .= $this->input_pair_table_row( $this->id, $iterator );

		$options_markup .= '</table>';

		$allowed_html = array(
			'p'     => array(),
			'table' => array( 'class' => array() ),
			'tbody' => array(),
			'tr'    => array(),
			'td'    => array(),
			'input' => array(
				'class'       => array(),
				'name'        => array(),
				'type'        => array(),
				'placeholder' => array(),
				'value'       => array(),
			),
		);

		printf( '<fieldset>%s</fieldset>', wp_kses( $options_markup, $allowed_html ) );

		$allow_anchor_html = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		);

		printf( '<p class="description">%s</p>', wp_kses( $arguments['supplemental'], $allow_anchor_html ) );
	}

	/**
	 * Generate a templated table row for the input of a regex/subject pair.
	 *
	 * @param string $id The option id.
	 * @param int    $iterator An unique integer to identify the row.
	 * @param string $regex An existing regex pattern (optional).
	 * @param string $note An existing note (optional).
	 *
	 * @return string The table row HTML.
	 */
	private function input_pair_table_row( $id, $iterator, $regex = '', $note = '' ) {

		$regex_placeholder = '/^myRegex$/';
		$note_placeholder  = __( 'Sample subject the regex pattern should apply to.', 'bh-wp-autologin-urls' );

		$options_markup = '';

		$options_markup .= '<tr><td>';

		$options_markup .= sprintf( '<input class="regex" name="%1$s[%2$s][regex]" type="text" placeholder="%3$s" value="%4$s" />', $id, $iterator, $regex_placeholder, $regex );
		$options_markup .= '</td><td>';
		$options_markup .= sprintf( '<input class="note" name="%1$s[%2$s][note]" type="text" placeholder="%3$s" value="%4$s" />', $id, $iterator, $note_placeholder, $note );

		$options_markup .= '</td></tr>';

		return $options_markup;

	}


	/**
	 * Removes empty regexes before saving and changes data from array of dictionaries keyed with "regex", "note"
	 * to single dictionary using the actual regexes as keys and the notes as the values.
	 *
	 * @param array $values an array of dictionaries with the keys regex and note.
	 *
	 * @return array $values Suitable for saving the database.
	 */
	public function sanitize_callback( $values ) {

		if ( ! is_array( $values ) ) {
			return $this->value;
		}

		// TODO: Run the regex match on the input and don't save if it fails...
		// ... i.e. return the old saved values so they're not overwritten.
		// Client-side validation would be better.

		// TODO: return an error if the regex field is empty.

		$tidy = array();

		foreach ( $values as $value ) {

			if ( empty( $value['note'] ) ) {

				$value['note'] = $value['regex'];
			}

			if ( ! empty( $value['regex'] ) ) {

				$tidy[ $value['regex'] ] = $value['note'];
			}
		}

		return $tidy;
	}
}
