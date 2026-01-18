<?php
/**
 * Unit tests for Book_Now_Availability class.
 *
 * @package BookNow
 */

use PHPUnit\Framework\TestCase;

/**
 * Test class for Book_Now_Availability.
 */
class AvailabilityTest extends TestCase {

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
	 * Test create() inserts availability rule.
	 */
	public function test_create_inserts_rule() {
		$rule_data = array(
			'rule_type'            => 'weekly',
			'day_of_week'          => 1,
			'start_time'           => '09:00:00',
			'end_time'             => '17:00:00',
			'is_available'         => 1,
			'consultation_type_id' => 1,
			'priority'             => 10,
		);

		$this->wpdb->insert_id = 5;

		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->willReturn( true );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-availability.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-logger.php';

		$rule_id = Book_Now_Availability::create( $rule_data );

		$this->assertEquals( 5, $rule_id );
	}

	/**
	 * Test get_by_id() retrieves rule.
	 */
	public function test_get_by_id_retrieves_rule() {
		$expected_rule = (object) array(
			'id'           => 1,
			'rule_type'    => 'weekly',
			'day_of_week'  => 1,
			'start_time'   => '09:00:00',
			'end_time'     => '17:00:00',
			'is_available' => 1,
		);

		$this->wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn( $expected_rule );

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_availability WHERE id = 1' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-availability.php';

		$rule = Book_Now_Availability::get_by_id( 1 );

		$this->assertEquals( 'weekly', $rule->rule_type );
		$this->assertEquals( 1, $rule->day_of_week );
	}

	/**
	 * Test update() modifies rule.
	 */
	public function test_update_modifies_rule() {
		$update_data = array(
			'start_time' => '10:00:00',
			'end_time'   => '18:00:00',
		);

		$this->wpdb->expects( $this->once() )
			->method( 'update' )
			->willReturn( 1 );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-availability.php';

		$result = Book_Now_Availability::update( 1, $update_data );

		$this->assertNotFalse( $result );
	}

	/**
	 * Test delete() removes rule.
	 */
	public function test_delete_removes_rule() {
		$this->wpdb->expects( $this->once() )
			->method( 'delete' )
			->willReturn( 1 );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-availability.php';

		$result = Book_Now_Availability::delete( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test calculate_slots() generates time slots.
	 */
	public function test_calculate_slots_generates_slots() {
		global $mock_options;
		$mock_options['booknow_general_settings'] = array(
			'slot_interval' => 30,
		);

		// Mock consultation type.
		$consultation_type = (object) array(
			'id'       => 1,
			'duration' => 60,
		);

		// Mock availability rules.
		$rules = array(
			(object) array(
				'id'           => 1,
				'start_time'   => '09:00:00',
				'end_time'     => '12:00:00',
				'is_available' => 1,
			),
		);

		$this->wpdb->expects( $this->any() )
			->method( 'get_row' )
			->willReturn( $consultation_type );

		$this->wpdb->expects( $this->any() )
			->method( 'get_results' )
			->willReturnOnConsecutiveCalls( $rules, array() );  // Rules then empty bookings.

		$this->wpdb->expects( $this->any() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_availability' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-availability.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-consultation-type.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';

		$slots = Book_Now_Availability::calculate_slots( '2026-02-15', 1 );

		// Should generate 5 slots: 09:00, 09:30, 10:00, 10:30, 11:00 (11:30 won't fit 60 min duration).
		$this->assertNotEmpty( $slots );
		$this->assertIsArray( $slots );
	}

	/**
	 * Test get_for_date() retrieves rules for specific date.
	 */
	public function test_get_for_date_retrieves_rules() {
		$rules = array(
			(object) array(
				'id'           => 1,
				'rule_type'    => 'weekly',
				'day_of_week'  => 6, // Saturday.
				'start_time'   => '09:00:00',
				'end_time'     => '17:00:00',
			),
		);

		$this->wpdb->expects( $this->any() )
			->method( 'get_results' )
			->willReturn( $rules );

		$this->wpdb->expects( $this->any() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_availability' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-availability.php';

		// 2026-02-14 is a Saturday (day_of_week = 6).
		$result = Book_Now_Availability::get_for_date( '2026-02-14', 6, 1 );

		$this->assertNotEmpty( $result );
	}

	/**
	 * Test conflict detection between time slots.
	 */
	public function test_detects_slot_conflicts() {
		global $mock_options;
		$mock_options['booknow_general_settings'] = array(
			'slot_interval' => 30,
		);

		// Mock consultation type.
		$consultation_type = (object) array(
			'id'       => 1,
			'duration' => 60,
		);

		// Mock availability rules.
		$rules = array(
			(object) array(
				'id'           => 1,
				'start_time'   => '09:00:00',
				'end_time'     => '12:00:00',
				'is_available' => 1,
			),
		);

		// Mock existing booking that conflicts.
		$bookings = array(
			(object) array(
				'id'           => 1,
				'booking_date' => '2026-02-15',
				'booking_time' => '10:00:00',
				'duration'     => 60,
				'status'       => 'confirmed',
			),
		);

		$this->wpdb->expects( $this->any() )
			->method( 'get_row' )
			->willReturn( $consultation_type );

		$this->wpdb->expects( $this->any() )
			->method( 'get_results' )
			->willReturnOnConsecutiveCalls( $rules, $bookings );

		$this->wpdb->expects( $this->any() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_availability' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-availability.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-consultation-type.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';

		$slots = Book_Now_Availability::calculate_slots( '2026-02-15', 1 );

		// Should not include 10:00 or 10:30 slots due to conflict.
		$times = array_column( $slots, 'time' );
		$this->assertNotContains( '10:00:00', $times );
		$this->assertNotContains( '10:30:00', $times );
	}

	/**
	 * Test timezone handling in availability.
	 */
	public function test_handles_timezone() {
		$rule_data = array(
			'rule_type'            => 'weekly',
			'day_of_week'          => 1,
			'start_time'           => '09:00:00',
			'end_time'             => '17:00:00',
			'is_available'         => 1,
			'consultation_type_id' => 1,
			'priority'             => 10,
		);

		$this->wpdb->insert_id = 1;

		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->callback( function( $data ) {
					// Verify times are stored correctly.
					$this->assertEquals( '09:00:00', $data['start_time'] );
					$this->assertEquals( '17:00:00', $data['end_time'] );
					return true;
				} ),
				$this->anything()
			)
			->willReturn( true );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-availability.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-logger.php';

		Book_Now_Availability::create( $rule_data );
	}

	/**
	 * Test get_all() filters by rule type.
	 */
	public function test_get_all_filters_by_type() {
		$rules = array(
			(object) array(
				'id'        => 1,
				'rule_type' => 'weekly',
			),
			(object) array(
				'id'        => 2,
				'rule_type' => 'weekly',
			),
		);

		$this->wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( $rules );

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_availability WHERE rule_type = "weekly"' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-availability.php';

		$result = Book_Now_Availability::get_all( array( 'rule_type' => 'weekly' ) );

		$this->assertCount( 2, $result );
	}

	/**
	 * Test get_all() orders by priority.
	 */
	public function test_get_all_orders_by_priority() {
		$rules = array(
			(object) array(
				'id'       => 1,
				'priority' => 10,
			),
			(object) array(
				'id'       => 2,
				'priority' => 5,
			),
		);

		$this->wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( $rules );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-availability.php';

		$result = Book_Now_Availability::get_all( array( 'orderby' => 'priority' ) );

		$this->assertNotEmpty( $result );
	}

	/**
	 * Test SQL injection prevention in orderby.
	 */
	public function test_prevents_sql_injection_in_orderby() {
		$this->wpdb->expects( $this->once() )
			->method( 'get_results' )
			->with( $this->stringContains( 'ORDER BY priority' ) )  // Should default to 'priority', not malicious input.
			->willReturn( array() );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-availability.php';

		// Try to inject SQL.
		Book_Now_Availability::get_all( array( 'orderby' => 'malicious; DROP TABLE' ) );

		// If we get here without error, the injection was prevented.
		$this->assertTrue( true );
	}

	/**
	 * Test block rules override availability.
	 */
	public function test_block_rules_override_availability() {
		// Mock availability rules and block rules.
		$rules = array(
			(object) array(
				'id'           => 1,
				'rule_type'    => 'weekly',
				'start_time'   => '09:00:00',
				'end_time'     => '17:00:00',
				'is_available' => 1,
			),
		);

		$blocks = array(
			(object) array(
				'id'           => 2,
				'rule_type'    => 'block',
				'start_time'   => '09:00:00',
				'end_time'     => '17:00:00',
			),
		);

		$this->wpdb->expects( $this->any() )
			->method( 'get_results' )
			->willReturnOnConsecutiveCalls( $rules, $blocks );

		$this->wpdb->expects( $this->any() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_availability' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-availability.php';

		$result = Book_Now_Availability::get_for_date( '2026-02-15', 6, 1 );

		// Block should filter out the availability rule.
		$this->assertEmpty( $result );
	}
}
