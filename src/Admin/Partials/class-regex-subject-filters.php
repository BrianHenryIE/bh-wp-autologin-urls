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

namespace BrianHenryIE\WP_Autologin_URLs\Admin\Partials;

use BrianHenryIE\WP_Autologin_URLs\API\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\Settings;

/**
 * Class Regex_Subject_Filters
 */
class Regex_Subject_Filters extends Settings_Section_Element_Abstract {

	/**
	 * Regex_Subject_Filters constructor.
	 *
	 * @param string             $settings_page The slug of the page this setting is being displayed on.
	 * @param Settings_Interface $settings The existing settings saved in the database.
	 */
	public function __construct( $settings_page, $settings ) {

		parent::__construct( $settings_page );

		$this->value = $settings->get_disallowed_subjects_regex_dictionary();

		$this->id    = Settings::SUBJECT_FILTER_REGEX_DICTIONARY;
		$this->title = __( 'Regex subject filters:', 'bh-wp-autologin-urls' );
		$this->page  = $settings_page;

		$this->register_setting_args['type'] = 'array';

		$this->add_settings_field_args['helper']       = __( 'Emails whose subjects match these regex patterns will not have autologin codes added.', 'bh-wp-autologin-urls' );
		$this->add_settings_field_args['supplemental'] = __( 'Take care to include leading and trailing / and use ^ and $ as appropriate.', 'bh-wp-autologin-urls' ) . ' Use <a href="https://www.phpliveregex.com/#tab-preg-match">phpliveregex.com</a> to test.';
		$this->add_settings_field_args['default']      = array();
	}

	/**
	 * Prints the html text input pairs for regex/subject notes displayed in the right-hand column of the settings table.
	 *
	 * @param array{helper:string, supplemental:string, default:string} $arguments The data registered with add_settings_field().
	 */
	public function print_field_callback( $arguments ): void {

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
	protected function input_pair_table_row( $id, $iterator, $regex = '', $note = '' ): string {

		$regex_placeholder = '/^myRegex$/';
		$note_placeholder  = __( 'Sample subject the regex pattern should apply to.', 'bh-wp-autologin-urls' );

		$options_markup = '';

		$options_markup .= '<tr><td>';

		$options_markup .= sprintf( '<input class="regex" name="%1$s[%2$s][regex]" type="text" placeholder="%3$s" value="%4$s" />', $id, $iterator, $regex_placeholder, esc_attr( $regex ) );
		$options_markup .= '</td><td>';
		$options_markup .= sprintf( '<input class="note" name="%1$s[%2$s][note]" type="text" placeholder="%3$s" value="%4$s" />', $id, $iterator, $note_placeholder, esc_attr( $note ) );

		$options_markup .= '</td></tr>';

		return $options_markup;

	}


	/**
	 * Removes empty regexes before saving and changes data from array of dictionaries keyed with "regex", "note"
	 * to single dictionary using the actual regexes as keys and the notes as the values.
	 *
	 * @param array<array{note:string, regex:string}>|array<string, string> $values an array of dictionaries with the keys regex and note.
	 *
	 * @return array<string, string> $values Suitable for saving the database.
	 */
	public function sanitize_callback( $values ) {

		// If bad data was POSTed, return thet existing value.
		if ( ! is_array( $values ) ) {
			return $this->value;
		}

		/**
		 * If it's an associative array, the sanitization callback has already run.
		 * This happens when it is run for the first time (when the existing value is false).
		 *
		 * @see https://stackoverflow.com/a/652760/336146
		 */
		if ( array_keys( $values ) !== array_keys( array_keys( $values ) ) ) {
			/**
			 * Set the correct type for PhpStan.
			 *
			 * @var array<string, string> $values
			 */
			return $values;
		}

		// TODO: Run the regex match on the input and don't save if it fails...
		// ... i.e. return the old saved values so they're not overwritten.
		// Client-side validation would be better.

		// TODO: return an error if the regex field is empty.

		$tidy = array();

		/**
		 * If we reach here, the correct type is:
		 *
		 * @var array<array{note:string, regex:string}> $values
		 */
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
