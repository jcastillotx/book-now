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

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load helpers file.
require_once dirname( __DIR__ ) . '/includes/helpers.php';

// Initialize mock options.
$mock_options = array();
