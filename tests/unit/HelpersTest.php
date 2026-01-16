<?php
/**
 * Unit tests for helper functions.
 *
 * @package BookNow
 */

use PHPUnit\Framework\TestCase;

/**
 * Test class for Book Now helper functions.
 */
class HelpersTest extends TestCase {

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		global $mock_options;
		$mock_options = array();
	}

	/**
	 * Test booknow_generate_reference_number generates valid reference.
	 */
	public function test_generate_reference_number_format() {
		$reference = booknow_generate_reference_number();

		// Should start with BN.
		$this->assertStringStartsWith( 'BN', $reference );

		// Should be 10 characters (BN + 8 chars).
		$this->assertEquals( 10, strlen( $reference ) );

		// Should be uppercase.
		$this->assertEquals( strtoupper( $reference ), $reference );
	}

	/**
	 * Test booknow_generate_reference_number generates unique values.
	 */
	public function test_generate_reference_number_uniqueness() {
		$references = array();
		for ( $i = 0; $i < 100; $i++ ) {
			$references[] = booknow_generate_reference_number();
		}

		// All 100 should be unique.
		$unique = array_unique( $references );
		$this->assertCount( 100, $unique );
	}

	/**
	 * Test booknow_format_price with various currencies.
	 *
	 * @dataProvider price_provider
	 */
	public function test_format_price( $amount, $currency, $expected ) {
		$result = booknow_format_price( $amount, $currency );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Data provider for price formatting tests.
	 */
	public function price_provider() {
		return array(
			'usd_whole'     => array( 100.00, 'USD', '$100.00' ),
			'usd_cents'     => array( 99.99, 'USD', '$99.99' ),
			'eur'           => array( 50.00, 'EUR', '€50.00' ),
			'gbp'           => array( 75.50, 'GBP', '£75.50' ),
			'jpy'           => array( 1000.00, 'JPY', '¥1,000.00' ),
			'cad'           => array( 125.00, 'CAD', 'C$125.00' ),
			'aud'           => array( 200.00, 'AUD', 'A$200.00' ),
			'unknown'       => array( 100.00, 'XYZ', 'XYZ 100.00' ),
			'zero'          => array( 0.00, 'USD', '$0.00' ),
			'large_number'  => array( 10000.00, 'USD', '$10,000.00' ),
		);
	}

	/**
	 * Test booknow_time_to_minutes conversion.
	 *
	 * @dataProvider time_to_minutes_provider
	 */
	public function test_time_to_minutes( $time, $expected ) {
		$result = booknow_time_to_minutes( $time );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Data provider for time to minutes conversion.
	 */
	public function time_to_minutes_provider() {
		return array(
			'midnight'      => array( '00:00:00', 0 ),
			'one_am'        => array( '01:00:00', 60 ),
			'nine_thirty'   => array( '09:30:00', 570 ),
			'noon'          => array( '12:00:00', 720 ),
			'two_pm'        => array( '14:00:00', 840 ),
			'five_fifteen'  => array( '17:15:00', 1035 ),
			'eleven_pm'     => array( '23:00:00', 1380 ),
			'short_format'  => array( '09:30', 570 ),
		);
	}

	/**
	 * Test booknow_minutes_to_time conversion.
	 *
	 * @dataProvider minutes_to_time_provider
	 */
	public function test_minutes_to_time( $minutes, $expected ) {
		$result = booknow_minutes_to_time( $minutes );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Data provider for minutes to time conversion.
	 */
	public function minutes_to_time_provider() {
		return array(
			'midnight'     => array( 0, '00:00:00' ),
			'one_hour'     => array( 60, '01:00:00' ),
			'nine_thirty'  => array( 570, '09:30:00' ),
			'noon'         => array( 720, '12:00:00' ),
			'two_pm'       => array( 840, '14:00:00' ),
			'five_fifteen' => array( 1035, '17:15:00' ),
			'eleven_pm'    => array( 1380, '23:00:00' ),
		);
	}

	/**
	 * Test booknow_validate_date with valid dates.
	 *
	 * @dataProvider valid_dates_provider
	 */
	public function test_validate_date_valid( $date ) {
		$this->assertTrue( booknow_validate_date( $date ) );
	}

	/**
	 * Data provider for valid dates.
	 */
	public function valid_dates_provider() {
		return array(
			'standard'   => array( '2025-01-15' ),
			'leap_year'  => array( '2024-02-29' ),
			'end_year'   => array( '2025-12-31' ),
			'start_year' => array( '2025-01-01' ),
		);
	}

	/**
	 * Test booknow_validate_date with invalid dates.
	 *
	 * @dataProvider invalid_dates_provider
	 */
	public function test_validate_date_invalid( $date ) {
		$this->assertFalse( booknow_validate_date( $date ) );
	}

	/**
	 * Data provider for invalid dates.
	 */
	public function invalid_dates_provider() {
		return array(
			'empty'        => array( '' ),
			'wrong_format' => array( '15-01-2025' ),
			'invalid_day'  => array( '2025-01-32' ),
			'invalid_feb'  => array( '2025-02-30' ),
			'text'         => array( 'not-a-date' ),
			'partial'      => array( '2025-01' ),
		);
	}

	/**
	 * Test booknow_validate_time with valid times.
	 *
	 * @dataProvider valid_times_provider
	 */
	public function test_validate_time_valid( $time ) {
		$this->assertTrue( booknow_validate_time( $time ) );
	}

	/**
	 * Data provider for valid times.
	 */
	public function valid_times_provider() {
		return array(
			'midnight_long'  => array( '00:00:00' ),
			'noon_long'      => array( '12:00:00' ),
			'evening_long'   => array( '23:59:59' ),
			'nine_short'     => array( '09:30' ),
			'noon_short'     => array( '12:00' ),
			'afternoon'      => array( '14:30:00' ),
		);
	}

	/**
	 * Test booknow_validate_time with invalid times.
	 *
	 * @dataProvider invalid_times_provider
	 */
	public function test_validate_time_invalid( $time ) {
		$this->assertFalse( booknow_validate_time( $time ) );
	}

	/**
	 * Data provider for invalid times.
	 */
	public function invalid_times_provider() {
		return array(
			'empty'         => array( '' ),
			'invalid_hour'  => array( '25:00:00' ),
			'invalid_min'   => array( '12:60:00' ),
			'text'          => array( 'noon' ),
			'partial'       => array( '12' ),
		);
	}

	/**
	 * Test booknow_sanitize_email with valid emails.
	 */
	public function test_sanitize_email_valid() {
		$this->assertEquals( 'test@example.com', booknow_sanitize_email( 'test@example.com' ) );
		$this->assertEquals( 'user.name@domain.org', booknow_sanitize_email( 'user.name@domain.org' ) );
	}

	/**
	 * Test booknow_sanitize_email with invalid emails.
	 */
	public function test_sanitize_email_invalid() {
		$this->assertFalse( booknow_sanitize_email( 'not-an-email' ) );
		$this->assertFalse( booknow_sanitize_email( '' ) );
		$this->assertFalse( booknow_sanitize_email( 'missing@' ) );
	}

	/**
	 * Test booknow_sanitize_phone removes invalid characters.
	 *
	 * @dataProvider phone_sanitize_provider
	 */
	public function test_sanitize_phone( $input, $expected ) {
		$result = booknow_sanitize_phone( $input );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Data provider for phone sanitization.
	 */
	public function phone_sanitize_provider() {
		return array(
			'simple'       => array( '1234567890', '1234567890' ),
			'with_dashes'  => array( '123-456-7890', '123-456-7890' ),
			'with_parens'  => array( '(123) 456-7890', '(123) 456-7890' ),
			'with_plus'    => array( '+1-234-567-8900', '+1-234-567-8900' ),
			'with_letters' => array( '123-CALL-NOW', '123--' ),
			'with_special' => array( '123!@#456', '123456' ),
		);
	}

	/**
	 * Test booknow_get_status_label returns correct labels.
	 *
	 * @dataProvider status_label_provider
	 */
	public function test_get_status_label( $status, $expected ) {
		$result = booknow_get_status_label( $status );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Data provider for status labels.
	 */
	public function status_label_provider() {
		return array(
			'pending'   => array( 'pending', 'Pending' ),
			'confirmed' => array( 'confirmed', 'Confirmed' ),
			'completed' => array( 'completed', 'Completed' ),
			'cancelled' => array( 'cancelled', 'Cancelled' ),
			'no_show'   => array( 'no-show', 'No Show' ),
			'unknown'   => array( 'custom', 'Custom' ),
		);
	}

	/**
	 * Test booknow_get_payment_status_label returns correct labels.
	 *
	 * @dataProvider payment_status_label_provider
	 */
	public function test_get_payment_status_label( $status, $expected ) {
		$result = booknow_get_payment_status_label( $status );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Data provider for payment status labels.
	 */
	public function payment_status_label_provider() {
		return array(
			'pending'  => array( 'pending', 'Pending' ),
			'paid'     => array( 'paid', 'Paid' ),
			'refunded' => array( 'refunded', 'Refunded' ),
			'failed'   => array( 'failed', 'Failed' ),
			'unknown'  => array( 'processing', 'Processing' ),
		);
	}

	/**
	 * Test booknow_get_setting returns null for missing keys.
	 */
	public function test_get_setting_missing() {
		$result = booknow_get_setting( 'general', 'nonexistent_key' );
		$this->assertNull( $result );
	}

	/**
	 * Test booknow_get_setting returns configured values.
	 */
	public function test_get_setting_exists() {
		global $mock_options;
		$mock_options['booknow_general_settings'] = array(
			'currency'    => 'USD',
			'date_format' => 'Y-m-d',
		);

		$this->assertEquals( 'USD', booknow_get_setting( 'general', 'currency' ) );
		$this->assertEquals( 'Y-m-d', booknow_get_setting( 'general', 'date_format' ) );
	}

	/**
	 * Test booknow_get_setting returns entire group when no key specified.
	 */
	public function test_get_setting_returns_group() {
		global $mock_options;
		$settings = array(
			'currency' => 'EUR',
			'timezone' => 'UTC',
		);
		$mock_options['booknow_general_settings'] = $settings;

		$result = booknow_get_setting( 'general' );
		$this->assertEquals( $settings, $result );
	}

	/**
	 * Test booknow_validate_booking_time normalizes short format.
	 */
	public function test_validate_booking_time_normalizes() {
		$result = booknow_validate_booking_time( '09:30' );
		$this->assertEquals( '09:30:00', $result );
	}

	/**
	 * Test booknow_validate_booking_time with invalid time.
	 */
	public function test_validate_booking_time_invalid() {
		$result = booknow_validate_booking_time( 'invalid' );
		$this->assertFalse( $result );
	}
}
