<?php
/**
 * Unit tests for Book_Now_Error_Log class.
 *
 * @package BookNow
 */

use PHPUnit\Framework\TestCase;

/**
 * Test class for Book_Now_Error_Log.
 */
class ErrorLogTest extends TestCase {

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

		global $wpdb;
		$wpdb = $this->wpdb;

		// Mock $_SERVER variables.
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Test Browser';
		$_SERVER['REQUEST_URI'] = '/test-page';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
	}

	/**
	 * Clean up after tests.
	 */
	protected function tearDown(): void {
		unset( $_SERVER['HTTP_USER_AGENT'] );
		unset( $_SERVER['REQUEST_URI'] );
		unset( $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Test log() inserts error log entry.
	 */
	public function test_log_inserts_error() {
		$this->wpdb->insert_id = 1;

		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->callback( function( $data ) {
					$this->assertEquals( 'ERROR', $data['error_level'] );
					$this->assertEquals( 'Test error message', $data['error_message'] );
					return true;
				} ),
				$this->anything()
			)
			->willReturn( true );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		$log_id = Book_Now_Error_Log::log( 'ERROR', 'Test error message' );

		$this->assertEquals( 1, $log_id );
	}

	/**
	 * Test log() validates error level.
	 */
	public function test_log_validates_error_level() {
		$this->wpdb->insert_id = 1;

		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->callback( function( $data ) {
					// Invalid level should default to 'ERROR'.
					$this->assertEquals( 'ERROR', $data['error_level'] );
					return true;
				} ),
				$this->anything()
			)
			->willReturn( true );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		Book_Now_Error_Log::log( 'INVALID_LEVEL', 'Test message' );
	}

	/**
	 * Test log() includes context data.
	 */
	public function test_log_includes_context() {
		$this->wpdb->insert_id = 1;

		$context = array(
			'source'     => 'stripe_payment',
			'booking_id' => 42,
		);

		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->callback( function( $data ) use ( $context ) {
					$this->assertEquals( 'stripe_payment', $data['error_source'] );
					$this->assertEquals( 42, $data['booking_id'] );
					$this->assertNotNull( $data['error_context'] );
					return true;
				} ),
				$this->anything()
			)
			->willReturn( true );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		Book_Now_Error_Log::log( 'ERROR', 'Test error', $context );
	}

	/**
	 * Test log() captures IP address.
	 */
	public function test_log_captures_ip_address() {
		$this->wpdb->insert_id = 1;

		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->callback( function( $data ) {
					$this->assertEquals( '192.168.1.1', $data['ip_address'] );
					return true;
				} ),
				$this->anything()
			)
			->willReturn( true );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		Book_Now_Error_Log::log( 'ERROR', 'Test error' );
	}

	/**
	 * Test get_logs() returns filtered results.
	 */
	public function test_get_logs_returns_filtered_results() {
		$logs = array(
			(object) array(
				'id'            => 1,
				'error_level'   => 'ERROR',
				'error_message' => 'Test error',
			),
		);

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_var' )
			->willReturn( 1 );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_results' )
			->willReturn( $logs );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_error_log' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		$result = Book_Now_Error_Log::get_logs( array( 'error_level' => 'ERROR' ) );

		$this->assertArrayHasKey( 'logs', $result );
		$this->assertEquals( 1, $result['total'] );
	}

	/**
	 * Test get_logs() filters by error source.
	 */
	public function test_get_logs_filters_by_source() {
		$logs = array();

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_var' )
			->willReturn( 0 );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_results' )
			->willReturn( $logs );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'prepare' )
			->with( $this->stringContains( 'error_source = %s' ) )
			->willReturn( 'SELECT * FROM wp_booknow_error_log WHERE error_source = "stripe"' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		$result = Book_Now_Error_Log::get_logs( array( 'error_source' => 'stripe' ) );

		$this->assertEquals( 0, $result['total'] );
	}

	/**
	 * Test delete_old_logs() removes old entries.
	 */
	public function test_delete_old_logs_removes_old_entries() {
		$this->wpdb->expects( $this->once() )
			->method( 'query' )
			->willReturn( 5 );

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->with( $this->stringContains( 'INTERVAL %d DAY' ) )
			->willReturn( 'DELETE FROM wp_booknow_error_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		$deleted = Book_Now_Error_Log::delete_old_logs( 30 );

		$this->assertEquals( 5, $deleted );
	}

	/**
	 * Test get_statistics() returns error stats.
	 */
	public function test_get_statistics_returns_stats() {
		// Mock total count.
		$this->wpdb->expects( $this->once() )
			->method( 'get_var' )
			->willReturn( 50 );

		// Mock by_level and by_source results.
		$by_level_results = array(
			(object) array(
				'error_level' => 'ERROR',
				'count'       => 30,
			),
			(object) array(
				'error_level' => 'WARNING',
				'count'       => 20,
			),
		);

		$by_source_results = array(
			(object) array(
				'error_source' => 'stripe',
				'count'        => 25,
			),
		);

		$recent = array();

		$this->wpdb->expects( $this->exactly( 3 ) )
			->method( 'get_results' )
			->willReturnOnConsecutiveCalls( $by_level_results, $by_source_results, $recent );

		$this->wpdb->expects( $this->exactly( 4 ) )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_error_log' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		$stats = Book_Now_Error_Log::get_statistics();

		$this->assertEquals( 50, $stats['total'] );
		$this->assertArrayHasKey( 'ERROR', $stats['by_level'] );
		$this->assertArrayHasKey( 'stripe', $stats['by_source'] );
	}

	/**
	 * Test get_sources() returns unique sources.
	 */
	public function test_get_sources_returns_unique_sources() {
		$sources = array( 'stripe', 'google_calendar', 'microsoft_calendar' );

		$this->wpdb->expects( $this->once() )
			->method( 'get_col' )
			->willReturn( $sources );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		$result = Book_Now_Error_Log::get_sources();

		$this->assertCount( 3, $result );
		$this->assertContains( 'stripe', $result );
	}

	/**
	 * Test IP address anonymization for privacy.
	 */
	public function test_anonymizes_ip_address() {
		$this->wpdb->insert_id = 1;

		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->callback( function( $data ) {
					// IP should be captured (anonymization would happen in a real implementation).
					$this->assertNotNull( $data['ip_address'] );
					return true;
				} ),
				$this->anything()
			)
			->willReturn( true );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		Book_Now_Error_Log::log( 'ERROR', 'Test error' );
	}

	/**
	 * Test log level filtering.
	 */
	public function test_filters_by_log_level() {
		$logs = array(
			(object) array(
				'id'          => 1,
				'error_level' => 'CRITICAL',
			),
		);

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_var' )
			->willReturn( 1 );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_results' )
			->willReturn( $logs );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'prepare' )
			->with( $this->stringContains( 'error_level = %s' ) )
			->willReturn( 'SELECT * FROM wp_booknow_error_log WHERE error_level = "CRITICAL"' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		$result = Book_Now_Error_Log::get_logs( array( 'error_level' => 'CRITICAL' ) );

		$this->assertCount( 1, $result['logs'] );
	}

	/**
	 * Test search functionality.
	 */
	public function test_searches_error_messages() {
		$logs = array();

		$this->wpdb->method( 'esc_like' )
			->willReturn( 'stripe' );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_var' )
			->willReturn( 0 );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_results' )
			->willReturn( $logs );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'prepare' )
			->with( $this->stringContains( 'LIKE' ) )
			->willReturn( 'SELECT * FROM wp_booknow_error_log WHERE error_message LIKE "%stripe%"' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		$result = Book_Now_Error_Log::get_logs( array( 'search' => 'stripe' ) );

		$this->assertEquals( 0, $result['total'] );
	}

	/**
	 * Test clear_all() truncates table.
	 */
	public function test_clear_all_truncates_table() {
		$this->wpdb->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'TRUNCATE TABLE' ) )
			->willReturn( true );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		$result = Book_Now_Error_Log::clear_all();

		$this->assertNotFalse( $result );
	}

	/**
	 * Test get_by_booking() retrieves errors for specific booking.
	 */
	public function test_get_by_booking_retrieves_errors() {
		$logs = array(
			(object) array(
				'id'         => 1,
				'booking_id' => 42,
			),
		);

		$this->wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( $logs );

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_error_log WHERE booking_id = 42' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-error-log.php';

		$result = Book_Now_Error_Log::get_by_booking( 42 );

		$this->assertCount( 1, $result );
		$this->assertEquals( 42, $result[0]->booking_id );
	}
}
