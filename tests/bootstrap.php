<?php
/**
 * PHPUnit bootstrap file for Book Now plugin tests.
 *
 * @package BookNow
 */

// Define WordPress stubs for unit testing without WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'BOOK_NOW_PLUGIN_DIR' ) ) {
	define( 'BOOK_NOW_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

// Mock WordPress functions used by helpers.
if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Mock get_option for testing.
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function get_option( $option, $default = false ) {
		global $mock_options;
		return isset( $mock_options[ $option ] ) ? $mock_options[ $option ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Mock update_option for testing.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 * @return bool
	 */
	function update_option( $option, $value ) {
		global $mock_options;
		$mock_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * Mock get_transient for testing.
	 *
	 * @param string $transient Transient name.
	 * @return mixed
	 */
	function get_transient( $transient ) {
		global $mock_transients;
		return isset( $mock_transients[ $transient ] ) ? $mock_transients[ $transient ] : false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * Mock set_transient for testing.
	 *
	 * @param string $transient  Transient name.
	 * @param mixed  $value      Transient value.
	 * @param int    $expiration Expiration time.
	 * @return bool
	 */
	function set_transient( $transient, $value, $expiration = 0 ) {
		global $mock_transients;
		$mock_transients[ $transient ] = $value;
		return true;
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	/**
	 * Mock sanitize_email for testing.
	 *
	 * @param string $email Email address.
	 * @return string
	 */
	function sanitize_email( $email ) {
		$email = trim( $email );
		return filter_var( $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'is_email' ) ) {
	/**
	 * Mock is_email for testing.
	 *
	 * @param string $email Email address.
	 * @return string|false
	 */
	function is_email( $email ) {
		return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : false;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Mock sanitize_text_field for testing.
	 *
	 * @param string $str String to sanitize.
	 * @return string
	 */
	function sanitize_text_field( $str ) {
		return htmlspecialchars( trim( $str ), ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'sanitize_sql_orderby' ) ) {
	/**
	 * Mock sanitize_sql_orderby for testing.
	 *
	 * @param string $orderby Order by clause.
	 * @return string|false
	 */
	function sanitize_sql_orderby( $orderby ) {
		// Basic validation.
		if ( preg_match( '/^[a-z_]+$/i', $orderby ) ) {
			return $orderby;
		}
		return false;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Mock wp_kses_post for testing.
	 *
	 * @param string $data Content to sanitize.
	 * @return string
	 */
	function wp_kses_post( $data ) {
		return strip_tags( $data, '<p><br><a><strong><em><ul><ol><li>' );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Mock esc_url_raw for testing.
	 *
	 * @param string $url URL to escape.
	 * @return string
	 */
	function esc_url_raw( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Mock wp_unslash for testing.
	 *
	 * @param string|array $value Value to unslash.
	 * @return string|array
	 */
	function wp_unslash( $value ) {
		return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( $value );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Mock wp_parse_args for testing.
	 *
	 * @param string|array $args     Arguments to parse.
	 * @param array        $defaults Default values.
	 * @return array
	 */
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$parsed_args = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$parsed_args =& $args;
		} else {
			parse_str( $args, $parsed_args );
		}

		if ( is_array( $defaults ) && $defaults ) {
			return array_merge( $defaults, $parsed_args );
		}
		return $parsed_args;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Mock wp_json_encode for testing.
	 *
	 * @param mixed $data    Data to encode.
	 * @param int   $options JSON options.
	 * @param int   $depth   Maximum depth.
	 * @return string|false
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	/**
	 * Mock get_current_user_id for testing.
	 *
	 * @return int
	 */
	function get_current_user_id() {
		return 1;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * Mock current_user_can for testing.
	 *
	 * @param string $capability Capability to check.
	 * @return bool
	 */
	function current_user_can( $capability ) {
		return true;
	}
}

if ( ! function_exists( 'current_time' ) ) {
	/**
	 * Mock current_time for testing.
	 *
	 * @param string $type Type of time (mysql or timestamp).
	 * @return string|int
	 */
	function current_time( $type ) {
		if ( 'mysql' === $type ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
		return time();
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Mock absint for testing.
	 *
	 * @param mixed $value Value to convert.
	 * @return int
	 */
	function absint( $value ) {
		return abs( intval( $value ) );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Mock add_action for testing.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback function.
	 * @param int      $priority Priority.
	 * @param int      $args     Number of arguments.
	 * @return bool
	 */
	function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Mock do_action for testing.
	 *
	 * @param string $hook Hook name.
	 * @return void
	 */
	function do_action( $hook ) {
		// Do nothing in tests.
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Mock __ (translation) for testing.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	/**
	 * Mock esc_html_e for testing.
	 *
	 * @param string $text   Text to escape and echo.
	 * @param string $domain Text domain.
	 * @return void
	 */
	function esc_html_e( $text, $domain = 'default' ) {
		echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Mock esc_html for testing.
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Mock WP_Error class for testing.
	 */
	class WP_Error {
		/**
		 * Error code
		 *
		 * @var string
		 */
		private $code;

		/**
		 * Error message
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Error data
		 *
		 * @var mixed
		 */
		private $data;

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 * @param mixed  $data    Error data.
		 */
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * Get error code.
		 *
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * Get error message.
		 *
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}

		/**
		 * Get error data.
		 *
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Check if variable is a WP_Error.
	 *
	 * @param mixed $thing Variable to check.
	 * @return bool
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load helpers file.
require_once dirname( __DIR__ ) . '/includes/helpers.php';

// Initialize mock options and transients.
global $mock_options, $mock_transients;
$mock_options    = array();
$mock_transients = array();
