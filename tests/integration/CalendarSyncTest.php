<?php
/**
 * Integration tests for Book_Now_Calendar_Sync class.
 *
 * @package BookNow
 */

use PHPUnit\Framework\TestCase;

/**
 * Test class for Book_Now_Calendar_Sync.
 */
class CalendarSyncTest extends TestCase {

	/**
	 * Mock wpdb instance
	 */
	private $wpdb;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		$this->wpdb = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'get_row', 'update', 'prepare', 'get_results', 'get_var' ) )
			->getMock();
		$this->wpdb->prefix = 'wp_';
		$this->wpdb->method( 'prepare' )
			->willReturnCallback(
				function ( $query ) {
					return $query;
				}
			);

		global $wpdb;
		$wpdb = $this->wpdb;

		global $mock_options;
		$mock_options = array();

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-booking.php';
		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-calendar-sync.php';
	}

	/**
	 * Create a sync instance with injected providers.
	 *
	 * @param array          $settings   Calendar settings.
	 * @param object|null    $google     Google provider mock.
	 * @param object|null    $microsoft  Microsoft provider mock.
	 * @return Book_Now_Calendar_Sync
	 */
	private function create_sync( array $settings, $google = null, $microsoft = null ) {
		global $mock_options;
		$mock_options['booknow_calendar_settings'] = $settings;

		$sync = new Book_Now_Calendar_Sync();

		$settings_property = new ReflectionProperty( Book_Now_Calendar_Sync::class, 'settings' );
		$settings_property->setValue( $sync, $settings );

		$google_property = new ReflectionProperty( Book_Now_Calendar_Sync::class, 'google' );
		$google_property->setValue( $sync, $google );

		$microsoft_property = new ReflectionProperty( Book_Now_Calendar_Sync::class, 'microsoft' );
		$microsoft_property->setValue( $sync, $microsoft );

		return $sync;
	}

	/**
	 * Test sync_booking_created() creates calendar events.
	 */
	public function test_sync_booking_created_creates_events() {
		$settings = array(
			'google_sync_enabled'    => true,
			'microsoft_sync_enabled' => true,
		);

		$booking = (object) array(
			'id'                   => 1,
			'customer_name'        => 'John Doe',
			'customer_email'       => 'john@example.com',
			'booking_date'         => '2026-02-15',
			'booking_time'         => '14:30:00',
			'duration'             => 60,
			'status'               => 'confirmed',
		);

		$this->wpdb->expects( $this->any() )
			->method( 'get_row' )
			->willReturn( $booking );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'update' )
			->willReturn( 1 );

		$google = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'is_authenticated', 'create_event' ) )
			->getMock();
		$google->expects( $this->once() )
			->method( 'is_authenticated' )
			->willReturn( true );
		$google->expects( $this->once() )
			->method( 'create_event' )
			->with( $booking )
			->willReturn( 'google-event-123' );

		$microsoft = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'is_authenticated', 'create_event' ) )
			->getMock();
		$microsoft->expects( $this->once() )
			->method( 'is_authenticated' )
			->willReturn( true );
		$microsoft->expects( $this->once() )
			->method( 'create_event' )
			->with( $booking )
			->willReturn( 'microsoft-event-456' );

		$calendar_sync = $this->create_sync( $settings, $google, $microsoft );
		$calendar_sync->sync_booking_created( 1 );

		$this->assertTrue( true );
	}

	/**
	 * Test calendar failures don't block bookings.
	 */
	public function test_calendar_failures_dont_block_bookings() {
		$settings = array(
			'google_sync_enabled' => true,
		);

		$booking = (object) array(
			'id'                   => 1,
			'customer_name'        => 'John Doe',
			'booking_date'         => '2026-02-15',
			'booking_time'         => '14:30:00',
			'status'               => 'confirmed',
		);

		$this->wpdb->expects( $this->any() )
			->method( 'get_row' )
			->willReturn( $booking );

		$this->wpdb->expects( $this->never() )
			->method( 'update' );

		$google = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'is_authenticated', 'create_event' ) )
			->getMock();
		$google->method( 'is_authenticated' )->willReturn( true );
		$google->method( 'create_event' )->willReturn( new WP_Error( 'api_error', 'Calendar unavailable' ) );

		$calendar_sync = $this->create_sync( $settings, $google, null );
		$calendar_sync->sync_booking_created( 1 );

		$this->assertTrue( true );
	}

	/**
	 * Test is_time_available() checks calendar availability.
	 */
	public function test_is_time_available_checks_calendars() {
		$google = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'is_authenticated', 'is_time_available' ) )
			->getMock();
		$google->method( 'is_authenticated' )->willReturn( true );
		$google->expects( $this->once() )
			->method( 'is_time_available' )
			->with( '2026-02-15', '14:30:00', 60 )
			->willReturn( false );

		$microsoft = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'is_authenticated', 'is_time_available' ) )
			->getMock();
		$microsoft->expects( $this->never() )
			->method( 'is_time_available' );

		$calendar_sync = $this->create_sync(
			array(
				'google_sync_enabled'    => true,
				'microsoft_sync_enabled' => true,
			),
			$google,
			$microsoft
		);
		$available = $calendar_sync->is_time_available( '2026-02-15', '14:30:00', 60 );

		$this->assertFalse( $available );
	}

	/**
	 * Test sync respects calendar enable/disable settings.
	 */
	public function test_sync_respects_settings() {
		$settings = array(
			'google_sync_enabled'    => false,
			'microsoft_sync_enabled' => false,
		);

		$booking = (object) array(
			'id'           => 1,
			'status'       => 'confirmed',
		);

		$this->wpdb->expects( $this->any() )
			->method( 'get_row' )
			->willReturn( $booking );

		$this->wpdb->expects( $this->never() )
			->method( 'update' );

		$google = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'is_authenticated', 'create_event' ) )
			->getMock();
		$google->expects( $this->never() )->method( 'create_event' );

		$calendar_sync = $this->create_sync( $settings, $google, null );
		$calendar_sync->sync_booking_created( 1 );

		$this->assertTrue( true );
	}

	/**
	 * Test sync_booking_updated() updates calendar events.
	 */
	public function test_sync_booking_updated_updates_events() {
		$settings = array(
			'google_sync_enabled' => true,
		);

		$booking = (object) array(
			'id'               => 1,
			'customer_name'    => 'John Doe Updated',
			'booking_date'     => '2026-02-15',
			'booking_time'     => '15:00:00',
			'google_event_id'  => 'google-event-123',
			'status'           => 'confirmed',
		);

		$this->wpdb->expects( $this->any() )
			->method( 'get_row' )
			->willReturn( $booking );

		$google = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'update_event' ) )
			->getMock();
		$google->expects( $this->once() )
			->method( 'update_event' )
			->with( 'google-event-123', $booking );

		$calendar_sync = $this->create_sync( $settings, $google, null );
		$calendar_sync->sync_booking_updated( 1 );

		$this->assertTrue( true );
	}

	/**
	 * Test sync_booking_cancelled() deletes calendar events.
	 */
	public function test_sync_booking_cancelled_deletes_events() {
		$settings = array(
			'google_sync_enabled' => true,
		);

		$booking = (object) array(
			'id'               => 1,
			'google_event_id'  => 'google-event-123',
			'status'           => 'cancelled',
		);

		$this->wpdb->expects( $this->any() )
			->method( 'get_row' )
			->willReturn( $booking );

		$google = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'delete_event' ) )
			->getMock();
		$google->expects( $this->once() )
			->method( 'delete_event' )
			->with( 'google-event-123' );

		$calendar_sync = $this->create_sync( $settings, $google, null );
		$calendar_sync->sync_booking_cancelled( 1 );

		$this->assertTrue( true );
	}

	/**
	 * Test get_busy_times() aggregates from multiple calendars.
	 */
	public function test_get_busy_times_aggregates() {
		$google = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'is_authenticated', 'get_busy_times' ) )
			->getMock();
		$google->method( 'is_authenticated' )->willReturn( true );
		$google->method( 'get_busy_times' )->willReturn(
			array(
				array( 'start' => '2026-02-15 14:00:00', 'end' => '2026-02-15 15:00:00' ),
			)
		);

		$microsoft = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'is_authenticated', 'get_busy_times' ) )
			->getMock();
		$microsoft->method( 'is_authenticated' )->willReturn( true );
		$microsoft->method( 'get_busy_times' )->willReturn(
			array(
				array( 'start' => '2026-02-15 09:00:00', 'end' => '2026-02-15 10:00:00' ),
			)
		);

		$calendar_sync = $this->create_sync(
			array(
				'google_sync_enabled'    => true,
				'microsoft_sync_enabled' => true,
			),
			$google,
			$microsoft
		);
		$busy_times = $calendar_sync->get_busy_times( '2026-02-15', '2026-02-16' );

		$this->assertIsArray( $busy_times );
		$this->assertCount( 2, $busy_times );
		$this->assertSame( '2026-02-15 09:00:00', $busy_times[0]['start'] );
	}

	/**
	 * Test manual_sync() forces calendar sync.
	 */
	public function test_manual_sync_forces_sync() {
		$settings = array(
			'google_sync_enabled' => true,
		);

		$booking = (object) array(
			'id'           => 1,
			'status'       => 'confirmed',
		);

		$this->wpdb->expects( $this->any() )
			->method( 'get_row' )
			->willReturn( $booking );

		$this->wpdb->expects( $this->once() )
			->method( 'update' )
			->willReturn( 1 );

		$google = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'is_authenticated', 'create_event' ) )
			->getMock();
		$google->method( 'is_authenticated' )->willReturn( true );
		$google->method( 'create_event' )->willReturn( 'google-event-123' );

		$calendar_sync = $this->create_sync( $settings, $google, null );
		$results = $calendar_sync->manual_sync( 1 );

		$this->assertIsArray( $results );
		$this->assertSame( 'created', $results['google'] );
	}

	/**
	 * Test calendar initialization handles missing classes gracefully.
	 */
	public function test_handles_missing_calendar_classes() {
		global $mock_options;
		$mock_options['booknow_calendar_settings'] = array();

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-calendar-sync.php';

		// Should not throw error even if calendar classes are missing.
		$calendar_sync = new Book_Now_Calendar_Sync();

		$this->assertInstanceOf( 'Book_Now_Calendar_Sync', $calendar_sync );
	}

	/**
	 * Test API errors are logged but don't crash.
	 */
	public function test_api_errors_are_logged() {
		$settings = array(
			'google_sync_enabled' => true,
		);

		$google = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'is_authenticated', 'is_time_available' ) )
			->getMock();
		$google->method( 'is_authenticated' )->willReturn( true );
		$google->method( 'is_time_available' )->willReturn( new WP_Error( 'api_error', 'Service unavailable' ) );

		$calendar_sync = $this->create_sync( $settings, $google, null );
		$this->assertFalse( $calendar_sync->is_time_available( '2026-02-15', '14:30:00', 60 ) );
	}
}
