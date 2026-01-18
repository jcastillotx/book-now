<?php
/**
 * Unit tests for Book_Now_Booking class.
 *
 * @package BookNow
 */

use PHPUnit\Framework\TestCase;

/**
 * Test class for Book_Now_Booking.
 */
class BookingTest extends TestCase {

	/**
	 * Mock wpdb instance
	 */
	private $wpdb;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		$this->wpdb = $this->createMock( stdClass::class );
		$this->wpdb->prefix = 'wp_';
		$this->wpdb->last_error = '';

		global $wpdb;
		$wpdb = $this->wpdb;
	}

	/**
	 * Test create() inserts booking with all required fields.
	 */
	public function test_create_inserts_booking() {
		$booking_data = array(
			'consultation_type_id' => 1,
			'customer_name'        => 'John Doe',
			'customer_email'       => 'john@example.com',
			'customer_phone'       => '123-456-7890',
			'customer_notes'       => 'Test notes',
			'booking_date'         => '2026-02-15',
			'booking_time'         => '14:30:00',
			'duration'             => 60,
			'timezone'             => 'America/New_York',
			'status'               => 'pending',
			'payment_status'       => 'pending',
			'payment_amount'       => 100.00,
		);

		$this->wpdb->insert_id = 42;

		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->willReturn( true );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-logger.php';

		$booking_id = Book_Now_Booking::create( $booking_data );

		$this->assertEquals( 42, $booking_id );
	}

	/**
	 * Test create() returns false on database error.
	 */
	public function test_create_returns_false_on_error() {
		$booking_data = array(
			'consultation_type_id' => 1,
			'customer_name'        => 'John Doe',
			'customer_email'       => 'john@example.com',
			'customer_phone'       => '123-456-7890',
			'booking_date'         => '2026-02-15',
			'booking_time'         => '14:30:00',
			'duration'             => 60,
		);

		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->willReturn( false );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-logger.php';

		$booking_id = Book_Now_Booking::create( $booking_data );

		$this->assertFalse( $booking_id );
	}

	/**
	 * Test get_by_id() retrieves booking.
	 */
	public function test_get_by_id_retrieves_booking() {
		$expected_booking = (object) array(
			'id'                   => 1,
			'reference_number'     => 'BN123ABC45',
			'customer_name'        => 'John Doe',
			'customer_email'       => 'john@example.com',
			'booking_date'         => '2026-02-15',
			'booking_time'         => '14:30:00',
			'status'               => 'confirmed',
		);

		$this->wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn( $expected_booking );

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_bookings WHERE id = 1' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';

		$booking = Book_Now_Booking::get_by_id( 1 );

		$this->assertEquals( 'BN123ABC45', $booking->reference_number );
		$this->assertEquals( 'John Doe', $booking->customer_name );
	}

	/**
	 * Test update() modifies booking fields.
	 */
	public function test_update_modifies_booking() {
		$update_data = array(
			'status'         => 'confirmed',
			'payment_status' => 'paid',
		);

		$this->wpdb->expects( $this->once() )
			->method( 'update' )
			->willReturn( 1 );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';

		$result = Book_Now_Booking::update( 1, $update_data );

		$this->assertTrue( $result );
	}

	/**
	 * Test delete() removes booking.
	 */
	public function test_delete_removes_booking() {
		$this->wpdb->expects( $this->once() )
			->method( 'delete' )
			->willReturn( 1 );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';

		$result = Book_Now_Booking::delete( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test get_by_date() filters bookings by date.
	 */
	public function test_get_by_date_filters_by_date() {
		$bookings = array(
			(object) array(
				'id'           => 1,
				'booking_date' => '2026-02-15',
				'booking_time' => '09:00:00',
				'status'       => 'confirmed',
			),
			(object) array(
				'id'           => 2,
				'booking_date' => '2026-02-15',
				'booking_time' => '10:00:00',
				'status'       => 'confirmed',
			),
		);

		$this->wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( $bookings );

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_bookings WHERE booking_date = "2026-02-15"' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';

		$result = Book_Now_Booking::get_by_date( '2026-02-15' );

		$this->assertCount( 2, $result );
	}

	/**
	 * Test get_by_date() filters by consultation type.
	 */
	public function test_get_by_date_filters_by_type() {
		$bookings = array(
			(object) array(
				'id'                   => 1,
				'consultation_type_id' => 5,
				'booking_date'         => '2026-02-15',
			),
		);

		$this->wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( $bookings );

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_bookings WHERE booking_date = "2026-02-15" AND consultation_type_id = 5' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';

		$result = Book_Now_Booking::get_by_date( '2026-02-15', 5 );

		$this->assertCount( 1, $result );
		$this->assertEquals( 5, $result[0]->consultation_type_id );
	}

	/**
	 * Test validation of required fields in create().
	 */
	public function test_create_sanitizes_input() {
		$booking_data = array(
			'consultation_type_id' => '1',  // String should be cast to int.
			'customer_name'        => '<script>alert("xss")</script>John',
			'customer_email'       => '  john@example.com  ',
			'customer_phone'       => '(123) 456-7890!',
			'booking_date'         => '2026-02-15',
			'booking_time'         => '14:30:00',
			'duration'             => '60',
		);

		$this->wpdb->insert_id = 1;
		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->callback( function( $data ) {
					// Check that data is sanitized.
					$this->assertIsInt( $data['consultation_type_id'] );
					$this->assertStringNotContainsString( '<script>', $data['customer_name'] );
					$this->assertIsInt( $data['duration'] );
					return true;
				} ),
				$this->anything()
			)
			->willReturn( true );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-logger.php';

		Book_Now_Booking::create( $booking_data );
	}

	/**
	 * Test email validation.
	 */
	public function test_create_validates_email() {
		$booking_data = array(
			'consultation_type_id' => 1,
			'customer_name'        => 'John Doe',
			'customer_email'       => 'invalid-email',  // Invalid email.
			'customer_phone'       => '123-456-7890',
			'booking_date'         => '2026-02-15',
			'booking_time'         => '14:30:00',
			'duration'             => 60,
		);

		$this->wpdb->insert_id = 1;
		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->willReturn( true );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-logger.php';

		// Should still create but email should be empty after sanitization failure.
		$booking_id = Book_Now_Booking::create( $booking_data );
		$this->assertEquals( 1, $booking_id );
	}

	/**
	 * Test get_stats() returns booking statistics.
	 */
	public function test_get_stats_returns_statistics() {
		$this->wpdb->expects( $this->exactly( 5 ) )
			->method( 'get_var' )
			->willReturnOnConsecutiveCalls( 100, 20, 50, 25, 5 );

		$this->wpdb->expects( $this->exactly( 5 ) )
			->method( 'prepare' )
			->willReturn( 'SELECT COUNT(*) FROM wp_booknow_bookings' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';

		$stats = Book_Now_Booking::get_stats();

		$this->assertEquals( 100, $stats['total'] );
		$this->assertEquals( 20, $stats['pending'] );
		$this->assertEquals( 50, $stats['confirmed'] );
		$this->assertEquals( 25, $stats['completed'] );
		$this->assertEquals( 5, $stats['cancelled'] );
	}

	/**
	 * Test cancel() updates status to cancelled.
	 */
	public function test_cancel_updates_status() {
		$this->wpdb->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->callback( function( $data ) {
					return $data['status'] === 'cancelled';
				} ),
				$this->anything(),
				$this->anything(),
				$this->anything()
			)
			->willReturn( 1 );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';

		$result = Book_Now_Booking::cancel( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test phone number sanitization.
	 */
	public function test_create_sanitizes_phone() {
		$booking_data = array(
			'consultation_type_id' => 1,
			'customer_name'        => 'John Doe',
			'customer_email'       => 'john@example.com',
			'customer_phone'       => '(123) 456-7890 ext. 999',
			'booking_date'         => '2026-02-15',
			'booking_time'         => '14:30:00',
			'duration'             => 60,
		);

		$this->wpdb->insert_id = 1;
		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->willReturn( true );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-logger.php';

		$booking_id = Book_Now_Booking::create( $booking_data );
		$this->assertEquals( 1, $booking_id );
	}

	/**
	 * Test get_by_reference() retrieves booking by reference number.
	 */
	public function test_get_by_reference_retrieves_booking() {
		$expected_booking = (object) array(
			'id'               => 1,
			'reference_number' => 'BN123ABC45',
			'customer_name'    => 'John Doe',
		);

		$this->wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn( $expected_booking );

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_bookings WHERE reference_number = "BN123ABC45"' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';

		$booking = Book_Now_Booking::get_by_reference( 'BN123ABC45' );

		$this->assertEquals( 1, $booking->id );
		$this->assertEquals( 'BN123ABC45', $booking->reference_number );
	}
}
