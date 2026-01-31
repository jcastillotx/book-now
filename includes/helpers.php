<?php
/**
 * Helper functions for the Book Now plugin
 *
 * @package BookNow
 * @since   1.0.0
 */

/**
 * Get plugin settings.
 *
 * @param string $group Settings group (general, payment, email, integration).
 * @param string $key   Optional. Specific setting key.
 * @return mixed
 */
function booknow_get_setting( $group, $key = null ) {
	static $cache = array();

	$cache_key = 'booknow_' . $group . '_settings';

	if ( ! isset( $cache[ $cache_key ] ) ) {
		$cache[ $cache_key ] = get_option( $cache_key, array() );
	}

	$settings = $cache[ $cache_key ];

	if ( null !== $key ) {
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	return $settings;
}

/**
 * Generate a unique booking reference number.
 *
 * Uses cryptographically secure random bytes for unpredictable reference numbers.
 * Generates 12 hex characters (281 trillion combinations) prefixed with 'BN'.
 *
 * @return string Reference number in format BN + 12 uppercase hex chars (e.g., BN1A2B3C4D5E6F).
 */
function booknow_generate_reference_number() {
	return 'BN' . strtoupper( bin2hex( random_bytes( 6 ) ) );
}

/**
 * Format price for display.
 *
 * @param float  $amount   Amount to format.
 * @param string $currency Currency code.
 * @return string
 */
function booknow_format_price( $amount, $currency = null ) {
	if ( null === $currency ) {
		$currency = booknow_get_setting( 'general', 'currency' );
	}

	$symbols = array(
		'USD' => '$',
		'EUR' => '€',
		'GBP' => '£',
		'JPY' => '¥',
		'CAD' => 'C$',
		'AUD' => 'A$',
	);

	$symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : $currency . ' ';

	return $symbol . number_format( $amount, 2 );
}

/**
 * Format date for display.
 *
 * @param string $date Date string.
 * @return string
 */
function booknow_format_date( $date ) {
	$format = booknow_get_setting( 'general', 'date_format' );
	$format = $format ? $format : 'F j, Y';
	return gmdate( $format, strtotime( $date ) );
}

/**
 * Format time for display.
 *
 * @param string $time Time string.
 * @return string
 */
function booknow_format_time( $time ) {
	$format = booknow_get_setting( 'general', 'time_format' );
	$format = $format ? $format : 'g:i a';
	return gmdate( $format, strtotime( $time ) );
}

/**
 * Get booking status label.
 *
 * @param string $status Booking status.
 * @return string
 */
function booknow_get_status_label( $status ) {
	$labels = array(
		'pending'   => __( 'Pending', 'book-now-kre8iv' ),
		'confirmed' => __( 'Confirmed', 'book-now-kre8iv' ),
		'completed' => __( 'Completed', 'book-now-kre8iv' ),
		'cancelled' => __( 'Cancelled', 'book-now-kre8iv' ),
		'no-show'   => __( 'No Show', 'book-now-kre8iv' ),
	);

	return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
}

/**
 * Get payment status label.
 *
 * @param string $status Payment status.
 * @return string
 */
function booknow_get_payment_status_label( $status ) {
	$labels = array(
		'pending'  => __( 'Pending', 'book-now-kre8iv' ),
		'paid'     => __( 'Paid', 'book-now-kre8iv' ),
		'refunded' => __( 'Refunded', 'book-now-kre8iv' ),
		'failed'   => __( 'Failed', 'book-now-kre8iv' ),
	);

	return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
}

/**
 * Convert time to minutes.
 *
 * @param string $time Time string (HH:MM:SS or HH:MM).
 * @return int
 */
function booknow_time_to_minutes( $time ) {
	$parts = explode( ':', $time );
	if ( count( $parts ) < 2 ) {
		return 0;
	}
	return (int) $parts[0] * 60 + (int) $parts[1];
}

/**
 * Convert minutes to time string.
 *
 * @param int $minutes Number of minutes.
 * @return string
 */
function booknow_minutes_to_time( $minutes ) {
	$hours = floor( $minutes / 60 );
	$mins  = $minutes % 60;
	return sprintf( '%02d:%02d:00', $hours, $mins );
}

/**
 * Check if date is within booking window.
 *
 * @param string $date Date to check.
 * @return bool
 */
function booknow_is_date_bookable( $date ) {
	$min_hours = booknow_get_setting( 'general', 'min_booking_notice' );
	$min_hours = $min_hours ? $min_hours : 24;
	$max_days  = booknow_get_setting( 'general', 'max_booking_advance' );
	$max_days  = $max_days ? $max_days : 90;

	$min_date   = strtotime( "+{$min_hours} hours" );
	$max_date   = strtotime( "+{$max_days} days" );
	$check_date = strtotime( $date );

	return $check_date >= $min_date && $check_date <= $max_date;
}

/**
 * Sanitize and validate email.
 *
 * @param string $email Email address.
 * @return string|false
 */
function booknow_sanitize_email( $email ) {
	$email = sanitize_email( $email );
	return is_email( $email ) ? $email : false;
}

/**
 * Sanitize and validate phone number.
 *
 * @param string $phone Phone number.
 * @return string
 */
function booknow_sanitize_phone( $phone ) {
	return preg_replace( '/[^0-9+\-() ]/', '', $phone );
}

/**
 * Validate date format (Y-m-d).
 *
 * @param string $date Date string to validate.
 * @return bool
 */
function booknow_validate_date( $date ) {
	if ( empty( $date ) ) {
		return false;
	}

	// Check if date is in the past.
	$d = DateTime::createFromFormat( 'Y-m-d', $date );
	return $d && $d->format( 'Y-m-d' ) === $date;
}

/**
 * Validate time format (H:i:s or H:i).
 *
 * @param string $time Time string to validate.
 * @return bool
 */
function booknow_validate_time( $time ) {
	if ( empty( $time ) ) {
		return false;
	}

	// Try H:i:s format first.
	$t = DateTime::createFromFormat( 'H:i:s', $time );
	if ( $t && $t->format( 'H:i:s' ) === $time ) {
		return true;
	}

	// Try H:i format.
	$t = DateTime::createFromFormat( 'H:i', $time );
	return $t && $t->format( 'H:i' ) === $time;
}

/**
 * Validate and sanitize booking date.
 *
 * @param string $date Date to validate.
 * @return string|false Sanitized date or false on failure.
 */
function booknow_validate_booking_date( $date ) {
	$date = sanitize_text_field( $date );

	if ( ! booknow_validate_date( $date ) ) {
		return false;
	}

	// Check if date is in the past.
	if ( strtotime( $date ) < strtotime( 'today' ) ) {
		return false;
	}

	// Check if date is within booking window.
	if ( ! booknow_is_date_bookable( $date ) ) {
		return false;
	}

	return $date;
}

/**
 * Validate and sanitize booking time.
 *
 * @param string $time Time to validate.
 * @return string|false Sanitized time or false on failure.
 */
function booknow_validate_booking_time( $time ) {
	$time = sanitize_text_field( $time );

	if ( ! booknow_validate_time( $time ) ) {
		return false;
	}

	// Normalize to H:i:s format.
	if ( strlen( $time ) === 5 ) {
		$time .= ':00';
	}

	return $time;
}
