<?php
/**
 * Unit tests for Book_Now_Email_Log class.
 *
 * @package BookNow
 */

use PHPUnit\Framework\TestCase;

/**
 * Test class for Book_Now_Email_Log.
 */
class EmailLogTest extends TestCase {

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
	}

	/**
	 * Test get_logs() returns paginated results.
	 */
	public function test_get_logs_returns_paginated_results() {
		$logs = array(
			(object) array(
				'id'              => 1,
				'booking_id'      => 10,
				'email_type'      => 'confirmation',
				'recipient_email' => 'test@example.com',
				'status'          => 'sent',
			),
		);

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_var' )
			->willReturn( 1 );  // Total count.

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_results' )
			->willReturn( $logs );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_email_log' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$result = Book_Now_Email_Log::get_logs( array( 'per_page' => 20, 'page' => 1 ) );

		$this->assertArrayHasKey( 'logs', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'page', $result );
		$this->assertArrayHasKey( 'per_page', $result );
		$this->assertArrayHasKey( 'pages', $result );
		$this->assertEquals( 1, $result['total'] );
	}

	/**
	 * Test get_logs() filters by email type.
	 */
	public function test_get_logs_filters_by_email_type() {
		$logs = array(
			(object) array(
				'id'         => 1,
				'email_type' => 'confirmation',
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
			->with( $this->stringContains( 'email_type = %s' ) )
			->willReturn( 'SELECT * FROM wp_booknow_email_log WHERE email_type = "confirmation"' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$result = Book_Now_Email_Log::get_logs( array( 'email_type' => 'confirmation' ) );

		$this->assertCount( 1, $result['logs'] );
		$this->assertEquals( 'confirmation', $result['logs'][0]->email_type );
	}

	/**
	 * Test get_logs() filters by status.
	 */
	public function test_get_logs_filters_by_status() {
		$logs = array(
			(object) array(
				'id'     => 1,
				'status' => 'sent',
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
			->with( $this->stringContains( 'status = %s' ) )
			->willReturn( 'SELECT * FROM wp_booknow_email_log WHERE status = "sent"' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$result = Book_Now_Email_Log::get_logs( array( 'status' => 'sent' ) );

		$this->assertCount( 1, $result['logs'] );
	}

	/**
	 * Test get_logs() filters by date range.
	 */
	public function test_get_logs_filters_by_date_range() {
		$logs = array();

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_var' )
			->willReturn( 0 );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_results' )
			->willReturn( $logs );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'prepare' )
			->with( $this->stringContains( 'DATE(sent_at)' ) )
			->willReturn( 'SELECT * FROM wp_booknow_email_log WHERE DATE(sent_at) >= "2026-01-01"' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$result = Book_Now_Email_Log::get_logs(
			array(
				'date_from' => '2026-01-01',
				'date_to'   => '2026-01-31',
			)
		);

		$this->assertEquals( 0, $result['total'] );
	}

	/**
	 * Test get_logs() searches by recipient or subject.
	 */
	public function test_get_logs_searches_text() {
		$logs = array(
			(object) array(
				'id'              => 1,
				'recipient_email' => 'test@example.com',
				'subject'         => 'Booking Confirmation',
			),
		);

		$this->wpdb->method( 'esc_like' )
			->willReturn( 'test' );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_var' )
			->willReturn( 1 );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_results' )
			->willReturn( $logs );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'prepare' )
			->with( $this->stringContains( 'LIKE' ) )
			->willReturn( 'SELECT * FROM wp_booknow_email_log WHERE subject LIKE "%test%"' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$result = Book_Now_Email_Log::get_logs( array( 'search' => 'test' ) );

		$this->assertCount( 1, $result['logs'] );
	}

	/**
	 * Test delete_old_logs() removes old entries.
	 */
	public function test_delete_old_logs_removes_old_entries() {
		$this->wpdb->expects( $this->once() )
			->method( 'query' )
			->willReturn( 10 );  // 10 rows deleted.

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->with( $this->stringContains( 'DATE_SUB' ) )
			->willReturn( 'DELETE FROM wp_booknow_email_log WHERE sent_at < DATE_SUB(NOW(), INTERVAL 90 DAY)' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$deleted = Book_Now_Email_Log::delete_old_logs( 90 );

		$this->assertEquals( 10, $deleted );
	}

	/**
	 * Test get_statistics() returns email stats.
	 */
	public function test_get_statistics_returns_stats() {
		// Mock total, sent, failed counts.
		$this->wpdb->expects( $this->exactly( 3 ) )
			->method( 'get_var' )
			->willReturnOnConsecutiveCalls( 100, 85, 15 );

		// Mock by_type results.
		$by_type_results = array(
			(object) array(
				'email_type' => 'confirmation',
				'count'      => 50,
			),
			(object) array(
				'email_type' => 'reminder',
				'count'      => 30,
			),
		);

		$this->wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( $by_type_results );

		$this->wpdb->expects( $this->exactly( 4 ) )
			->method( 'prepare' )
			->willReturn( 'SELECT COUNT(*) FROM wp_booknow_email_log' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$stats = Book_Now_Email_Log::get_statistics();

		$this->assertEquals( 100, $stats['total'] );
		$this->assertEquals( 85, $stats['sent'] );
		$this->assertEquals( 15, $stats['failed'] );
		$this->assertEquals( 85.0, $stats['success_rate'] );
		$this->assertArrayHasKey( 'confirmation', $stats['by_type'] );
	}

	/**
	 * Test pagination calculates pages correctly.
	 */
	public function test_pagination_calculates_pages() {
		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_var' )
			->willReturn( 45 );  // 45 total logs.

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'get_results' )
			->willReturn( array() );

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_email_log' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$result = Book_Now_Email_Log::get_logs( array( 'per_page' => 20, 'page' => 1 ) );

		$this->assertEquals( 45, $result['total'] );
		$this->assertEquals( 3, $result['pages'] );  // Ceiling of 45/20.
	}

	/**
	 * Test get_log() retrieves single log by ID.
	 */
	public function test_get_log_retrieves_single_log() {
		$expected_log = (object) array(
			'id'         => 1,
			'email_type' => 'confirmation',
		);

		$this->wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn( $expected_log );

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_email_log WHERE id = 1' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$log = Book_Now_Email_Log::get_log( 1 );

		$this->assertEquals( 1, $log->id );
	}

	/**
	 * Test delete_log() removes single log.
	 */
	public function test_delete_log_removes_single_log() {
		$this->wpdb->expects( $this->once() )
			->method( 'delete' )
			->willReturn( 1 );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$result = Book_Now_Email_Log::delete_log( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test delete_logs() removes multiple logs.
	 */
	public function test_delete_logs_removes_multiple() {
		$this->wpdb->expects( $this->once() )
			->method( 'query' )
			->willReturn( 3 );

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'DELETE FROM wp_booknow_email_log WHERE id IN (1,2,3)' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$deleted = Book_Now_Email_Log::delete_logs( array( 1, 2, 3 ) );

		$this->assertEquals( 3, $deleted );
	}

	/**
	 * Test export_to_csv() generates CSV content.
	 */
	public function test_export_to_csv_generates_content() {
		$logs = array(
			(object) array(
				'id'              => 1,
				'booking_id'      => 10,
				'email_type'      => 'confirmation',
				'recipient_email' => 'test@example.com',
				'subject'         => 'Booking Confirmed',
				'status'          => 'sent',
				'error_message'   => '',
				'sent_at'         => '2026-01-15 10:00:00',
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
			->willReturn( 'SELECT * FROM wp_booknow_email_log' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$csv = Book_Now_Email_Log::export_to_csv();

		$this->assertNotEmpty( $csv );
		$this->assertStringContainsString( 'ID', $csv );
		$this->assertStringContainsString( 'test@example.com', $csv );
	}

	/**
	 * Test get_by_booking() retrieves logs for specific booking.
	 */
	public function test_get_by_booking_retrieves_logs() {
		$logs = array(
			(object) array(
				'id'         => 1,
				'booking_id' => 10,
			),
		);

		$this->wpdb->expects( $this->once() )
			->method( 'get_results' )
			->willReturn( $logs );

		$this->wpdb->expects( $this->once() )
			->method( 'prepare' )
			->willReturn( 'SELECT * FROM wp_booknow_email_log WHERE booking_id = 10' );

		require_once dirname( __DIR__, 2 ) . '/includes/class-book-now-email-log.php';

		$result = Book_Now_Email_Log::get_by_booking( 10 );

		$this->assertCount( 1, $result );
		$this->assertEquals( 10, $result[0]->booking_id );
	}
}
