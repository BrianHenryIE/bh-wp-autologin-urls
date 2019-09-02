<?php
/**
 * Autoloader - Main class
 *
 * @author  Pablo dos S G Pacheco
 */

namespace BH\Pablo_Pacheco\WP_Namespace_Autoloader;

if ( ! class_exists( '\BH\Pablo_Pacheco\WP_Namespace_Autoloader\WP_Namespace_Autoloader' ) ) {
	class WP_Namespace_Autoloader {

		private $args;

		/**
		 * Autoloader constructor.
		 *
		 * Autoloads all your WordPress classes in a easy way
		 *
		 * @param array|string $args                 {
		 *                                           Array of arguments.
		 *
		 * @type string        $directory            Current directory. Use __DIR__.
		 * @type string        $namespace_prefix     Main namespace of your project . Probably use __NAMESPACE__.
		 * @type array         $lowercase            If you want to lowercase. It accepts an array with two possible values: 'file' | 'folders'
		 * @type array         $underscore_to_hyphen If you want to convert underscores to hyphens. It accepts an array with two possible values: 'file' | 'folders'
		 * @type boolean       $prepend_class        If you want to prepend 'class-' before files
		 * @type string        $classes_dir          Name of the directory containing all your classes (optional).
		 * }
		 */
		function __construct( $args = array() ) {
			$arguments = array(
					'directory'            => null,
					'namespace_prefix'     => null,
					'lowercase'            => array( 'file' ), // 'file' | folders
					'underscore_to_hyphen' => array( 'file' ), // 'file' | folders
					'prepend_class'        => true,
					'classes_dir'          => '',
					'debug'                => false,
			);

			foreach( $args as $key => $value ) {
				$arguments[$key] = $value;
			}

			$this->set_args( $arguments );
		}

		/**
		 * Register autoloader
		 *
		 * @return string
		 */
		public function init() {
			spl_autoload_register( array( $this, 'autoload' ) );
		}

		public function need_to_autoload( $class ) {
			$args      = $this->get_args();
			$namespace = $args['namespace_prefix'];

			if ( ! class_exists( $class ) && ! interface_exists( $class) ) {

				if ( false !== strpos( $class, $namespace ) ) {
					if ( ! class_exists( $class ) ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Autoloads classes
		 *
		 * @param string $class
		 */
		public function autoload( $class ) {
			if ( $this->need_to_autoload( $class ) ) {
				$file = $this->convert_class_to_file( $class );
				if ( is_string( $file ) && file_exists( $file ) ) {
					require_once $file;
				} else {
					$args = $this->get_args();
					if ( $args['debug'] ) {
						error_log( 'WP Namespace Autoloader could not load file: ' . print_r( $file, true ) );
					}
				}
			}
		}

		/**
		 * Gets full path of directory containing all classes
		 *
		 * @return string
		 */
		private function get_dir() {
			$args = $this->get_args();
			$dir  = $this->sanitize_file_path( $args['classes_dir'] );

			// Directory containing all classes
			$classes_dir = empty( $dir ) ? '' : rtrim( $dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

			return rtrim( $args['directory'], '/\\' )  . DIRECTORY_SEPARATOR . $classes_dir;
		}

		/**
		 * Gets only the path leading to final file based on namespace
		 *
		 * @param string $class
		 *
		 * @return string
		 */
		private function get_namespace_file_path( $class ) {
			$args             = $this->get_args();
			$namespace_prefix = $args['namespace_prefix'];

			// Sanitized class and namespace prefix
			$sanitized_class            = $this->sanitize_namespace( $class, false );
			$sanitized_namespace_prefix = $this->sanitize_namespace( $namespace_prefix, true );

			// Removes prefix from class namespace
			$namespace_without_prefix = str_replace( $sanitized_namespace_prefix, '', $sanitized_class );

			// Gets namespace file path
			$namespaces_without_prefix_arr = explode( '\\', $namespace_without_prefix );

			array_pop( $namespaces_without_prefix_arr );
			$namespace_file_path = implode( DIRECTORY_SEPARATOR, $namespaces_without_prefix_arr ) . DIRECTORY_SEPARATOR;

			if ( in_array( 'folders', $args['lowercase'] ) ) {
				$namespace_file_path = strtolower( $namespace_file_path );
			}

			if ( in_array( 'folders', $args['underscore_to_hyphen'] ) ) {
				$namespace_file_path = str_replace( array( '_', "\0" ), array( '-', '' ), $namespace_file_path );
			}

			if ( $namespace_file_path == '\\' || $namespace_file_path == '\/' ) {
				$namespace_file_path = '';
			}

			return $namespace_file_path;
		}

		/**
		 * Gets final file to be loaded considering WordPress coding standards
		 *
		 * @param string $class
		 *
		 * @return string
		 */
		private function get_file_applying_wp_standards( $class ) {
			$args = $this->get_args();

			// Sanitized class and namespace prefix
			$sanitized_class = $this->sanitize_namespace( $class, false );

			// Gets namespace file path
			$namespaces_arr = explode( '\\', $sanitized_class );

			$final_file = array_pop( $namespaces_arr );

			// Final file name
			if ( in_array( 'file', $args['lowercase'] ) ) {
				$final_file = strtolower( $final_file );
			}

			// Final file with underscores replaced
			if ( in_array( 'file', $args['underscore_to_hyphen'] ) ) {
				$final_file = str_replace( array( '_', "\0" ), array( '-', '' ), $final_file );
			}

			// Prepend class
			if ( $args['prepend_class'] ) {
				// Added by BH: Not WPCS!
				$prepended = preg_replace('/(.*)-interface$/', 'interface-$1', $final_file);
				$prepended = preg_replace('/(.*)-abstract$/', 'abstract-$1', $prepended);

				if( $prepended === $final_file ) {
					$final_file = 'class-' . $final_file;
				} else {
					$final_file = $prepended;
				}
			}

			$final_file .= '.php';

			return $final_file;
		}

		/**
		 * Sanitizes file path
		 *
		 * @param string $file_path
		 *
		 * @return string
		 */
		private function sanitize_file_path( $file_path ) {
			return trim( $file_path, DIRECTORY_SEPARATOR );
		}


		/**
		 * Sanitizes namespace
		 *
		 * @param string $namespace
		 * @param bool   $add_backslash
		 *
		 * @return string
		 */
		private function sanitize_namespace( $namespace, $add_backslash = false ) {
			if ( $add_backslash ) {
				return trim( $namespace, '\\' ) . '\\';
			} else {
				return trim( $namespace, '\\' );
			}
		}

		/**
		 * Converts a namespaced class in a file to be loaded
		 *
		 * @param string $class
		 * @param bool   $check_loading_need
		 *
		 * @return bool|string
		 */
		public function convert_class_to_file( $class, $check_loading_need = false ) {
			if ( $check_loading_need ) {
				if ( ! $this->need_to_autoload( $class ) ) {
					return false;
				}
			}

			$dir                 = $this->get_dir();
			$namespace_file_path = $this->get_namespace_file_path( $class );
			$final_file          = $this->get_file_applying_wp_standards( $class );

			return $dir . $namespace_file_path . $final_file;
		}

		/**
		 * @return mixed
		 */
		public function get_args() {
			return $this->args;
		}

		/**
		 * @param mixed $args
		 */
		public function set_args( $args ) {
			$this->args = $args;
		}
	}
}
