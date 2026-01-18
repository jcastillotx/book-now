<?php
/**
 * Email Log Data Retrieval
 *
 * Handles database operations for email logs including retrieval,
 * filtering, pagination, deletion, and export.
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 * @since      1.3.2
 */

class Book_Now_Email_Log {

	/**
	 * Get paginated email logs with filters
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type int    $per_page      Number of logs per page. Default 20.
	 *     @type int    $page          Current page number. Default 1.
	 *     @type string $email_type    Filter by email type. Default empty (all types).
	 *     @type string $status        Filter by status (sent/failed). Default empty (all).
	 *     @type int    $booking_id    Filter by booking ID. Default 0 (all bookings).
	 *     @type string $recipient     Filter by recipient email. Default empty.
	 *     @type string $date_from     Filter from date (Y-m-d). Default empty.
	 *     @type string $date_to       Filter to date (Y-m-d). Default empty.
	 *     @type string $search        Search term for subject/recipient. Default empty.
	 *     @type string $orderby       Order by column. Default 'sent_at'.
	 *     @type string $order         Sort order (ASC/DESC). Default 'DESC'.
	 * }
	 * @return array {
	 *     Email logs data.
	 *
	 *     @type array $logs      Array of email log objects.
	 *     @type int   $total     Total number of matching logs.
	 *     @type int   $page      Current page number.
	 *     @type int   $per_page  Logs per page.
	 *     @type int   $pages     Total number of pages.
	 * }
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'per_page'   => 20,
			'page'       => 1,
			'email_type' => '',
			'status'     => '',
			'booking_id' => 0,
			'recipient'  => '',
			'date_from'  => '',
			'date_to'    => '',
			'search'     => '',
			'orderby'    => 'sent_at',
			'order'      => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Sanitize inputs
		$per_page   = absint( $args['per_page'] );
		$page       = absint( $args['page'] );
		$booking_id = absint( $args['booking_id'] );
		$email_type = sanitize_text_field( $args['email_type'] );
		$status     = sanitize_text_field( $args['status'] );
		$recipient  = sanitize_email( $args['recipient'] );
		$search     = sanitize_text_field( $args['search'] );
		$orderby    = sanitize_sql_orderby( $args['orderby'] );
		$order      = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Ensure per_page is reasonable
		$per_page = min( max( 1, $per_page ), 100 );
		$page     = max( 1, $page );

		// Valid orderby columns
		$valid_orderby = array( 'id', 'sent_at', 'email_type', 'status', 'recipient_email', 'booking_id' );
		if ( ! in_array( $orderby, $valid_orderby, true ) ) {
			$orderby = 'sent_at';
		}

		$table = $wpdb->prefix . 'booknow_email_log';

		// Build WHERE clause
		$where_clauses = array( '1=1' );
		$where_values  = array();

		if ( ! empty( $email_type ) ) {
			$where_clauses[] = 'email_type = %s';
			$where_values[]  = $email_type;
		}

		if ( ! empty( $status ) ) {
			$where_clauses[] = 'status = %s';
			$where_values[]  = $status;
		}

		if ( ! empty( $booking_id ) ) {
			$where_clauses[] = 'booking_id = %d';
			$where_values[]  = $booking_id;
		}

		if ( ! empty( $recipient ) ) {
			$where_clauses[] = 'recipient_email = %s';
			$where_values[]  = $recipient;
		}

		if ( ! empty( $args['date_from'] ) ) {
			$date_from = sanitize_text_field( $args['date_from'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
				$where_clauses[] = 'DATE(sent_at) >= %s';
				$where_values[]  = $date_from;
			}
		}

		if ( ! empty( $args['date_to'] ) ) {
			$date_to = sanitize_text_field( $args['date_to'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
				$where_clauses[] = 'DATE(sent_at) <= %s';
				$where_values[]  = $date_to;
			}
		}

		if ( ! empty( $search ) ) {
			$where_clauses[] = '(subject LIKE %s OR recipient_email LIKE %s)';
			$search_term     = '%' . $wpdb->esc_like( $search ) . '%';
			$where_values[]  = $search_term;
			$where_values[]  = $search_term;
		}

		$where = implode( ' AND ', $where_clauses );

		// Count total records
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
		if ( ! empty( $where_values ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $where_values );
		}
		$total = (int) $wpdb->get_var( $count_sql );

		// Calculate pagination
		$total_pages = ceil( $total / $per_page );
		$offset      = ( $page - 1 ) * $per_page;

		// Get logs
		$sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$query_values   = array_merge( $where_values, array( $per_page, $offset ) );
		$prepared_query = $wpdb->prepare( $sql, $query_values );
		$logs           = $wpdb->get_results( $prepared_query );

		// Decrypt email bodies if present
		if ( ! empty( $logs ) ) {
			foreach ( $logs as $log ) {
				if ( ! empty( $log->email_body ) ) {
					$log->email_body = self::get_decrypted_body( $log );
				}
			}
		}

		return array(
			'logs'     => $logs,
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
			'pages'    => $total_pages,
		);
	}

	/**
	 * Get single email log by ID
	 *
	 * @param int $log_id Email log ID.
	 * @return object|null Email log object or null if not found.
	 */
	public static function get_log( $log_id ) {
		global $wpdb;

		$log_id = absint( $log_id );
		if ( empty( $log_id ) ) {
			return null;
		}

		$table = $wpdb->prefix . 'booknow_email_log';

		$log = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$log_id
			)
		);

		// Decrypt email body if present
		if ( $log && ! empty( $log->email_body ) ) {
			$log->email_body = self::get_decrypted_body( $log );
		}

		return $log;
	}

	/**
	 * Get email logs count by type or status
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type string $email_type  Filter by email type.
	 *     @type string $status      Filter by status.
	 *     @type string $date_from   Filter from date (Y-m-d).
	 *     @type string $date_to     Filter to date (Y-m-d).
	 * }
	 * @return int Number of matching logs.
	 */
	public static function get_count( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'email_type' => '',
			'status'     => '',
			'date_from'  => '',
			'date_to'    => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$table         = $wpdb->prefix . 'booknow_email_log';
		$where_clauses = array( '1=1' );
		$where_values  = array();

		if ( ! empty( $args['email_type'] ) ) {
			$where_clauses[] = 'email_type = %s';
			$where_values[]  = sanitize_text_field( $args['email_type'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$where_values[]  = sanitize_text_field( $args['status'] );
		}

		if ( ! empty( $args['date_from'] ) ) {
			$date_from = sanitize_text_field( $args['date_from'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
				$where_clauses[] = 'DATE(sent_at) >= %s';
				$where_values[]  = $date_from;
			}
		}

		if ( ! empty( $args['date_to'] ) ) {
			$date_to = sanitize_text_field( $args['date_to'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
				$where_clauses[] = 'DATE(sent_at) <= %s';
				$where_values[]  = $date_to;
			}
		}

		$where = implode( ' AND ', $where_clauses );
		$sql   = "SELECT COUNT(*) FROM {$table} WHERE {$where}";

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Delete old email logs
	 *
	 * @param int $days Delete logs older than this many days. Default 90.
	 * @return int|false Number of deleted rows or false on failure.
	 */
	public static function delete_old_logs( $days = 90 ) {
		global $wpdb;

		$days = absint( $days );
		if ( empty( $days ) ) {
			return false;
		}

		$table = $wpdb->prefix . 'booknow_email_log';

		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE sent_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		if ( $result ) {
			self::clear_cache();
		}

		return $result;
	}

	/**
	 * Delete specific email log
	 *
	 * @param int $log_id Email log ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_log( $log_id ) {
		global $wpdb;

		$log_id = absint( $log_id );
		if ( empty( $log_id ) ) {
			return false;
		}

		$table = $wpdb->prefix . 'booknow_email_log';

		$result = (bool) $wpdb->delete(
			$table,
			array( 'id' => $log_id ),
			array( '%d' )
		);

		if ( $result ) {
			self::clear_cache();
		}

		return $result;
	}

	/**
	 * Delete multiple email logs
	 *
	 * @param array $log_ids Array of log IDs to delete.
	 * @return int Number of deleted rows.
	 */
	public static function delete_logs( $log_ids ) {
		global $wpdb;

		if ( ! is_array( $log_ids ) || empty( $log_ids ) ) {
			return 0;
		}

		$log_ids = array_map( 'absint', $log_ids );
		$log_ids = array_filter( $log_ids );

		if ( empty( $log_ids ) ) {
			return 0;
		}

		$table       = $wpdb->prefix . 'booknow_email_log';
		$placeholders = implode( ',', array_fill( 0, count( $log_ids ), '%d' ) );

		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE id IN ({$placeholders})",
				$log_ids
			)
		);

		if ( $result ) {
			self::clear_cache();
		}

		return $result;
	}

	/**
	 * Export email logs to CSV
	 *
	 * @param array $args Same arguments as get_logs().
	 * @return string CSV content.
	 */
	public static function export_to_csv( $args = array() ) {
		// Get all logs without pagination
		$args['per_page'] = 999999;
		$args['page']     = 1;
		$result           = self::get_logs( $args );
		$logs             = $result['logs'];

		if ( empty( $logs ) ) {
			return '';
		}

		// CSV header
		$csv = array();
		$csv[] = array(
			'ID',
			'Booking ID',
			'Email Type',
			'Recipient',
			'Subject',
			'Status',
			'Error Message',
			'Sent At',
		);

		// CSV rows
		foreach ( $logs as $log ) {
			$csv[] = array(
				$log->id,
				$log->booking_id,
				$log->email_type,
				$log->recipient_email,
				$log->subject,
				$log->status,
				! empty( $log->error_message ) ? $log->error_message : '',
				$log->sent_at,
			);
		}

		// Convert to CSV string
		$output = fopen( 'php://temp', 'r+' );
		foreach ( $csv as $row ) {
			fputcsv( $output, $row );
		}
		rewind( $output );
		$csv_content = stream_get_contents( $output );
		fclose( $output );

		return $csv_content;
	}

	/**
	 * Get email statistics
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type string $date_from   Filter from date (Y-m-d).
	 *     @type string $date_to     Filter to date (Y-m-d).
	 * }
	 * @return array {
	 *     Email statistics.
	 *
	 *     @type int   $total         Total emails.
	 *     @type int   $sent          Successfully sent emails.
	 *     @type int   $failed        Failed emails.
	 *     @type array $by_type       Count by email type.
	 *     @type float $success_rate  Success rate percentage.
	 * }
	 */
	public static function get_statistics( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'date_from' => '',
			'date_to'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Generate cache key from arguments
		$cache_key = 'booknow_email_stats_' . md5( serialize( $args ) );
		$stats     = wp_cache_get( $cache_key, 'booknow' );

		if ( false !== $stats ) {
			return $stats;
		}

		$table         = $wpdb->prefix . 'booknow_email_log';
		$where_clauses = array( '1=1' );
		$where_values  = array();

		if ( ! empty( $args['date_from'] ) ) {
			$date_from = sanitize_text_field( $args['date_from'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
				$where_clauses[] = 'DATE(sent_at) >= %s';
				$where_values[]  = $date_from;
			}
		}

		if ( ! empty( $args['date_to'] ) ) {
			$date_to = sanitize_text_field( $args['date_to'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
				$where_clauses[] = 'DATE(sent_at) <= %s';
				$where_values[]  = $date_to;
			}
		}

		$where = implode( ' AND ', $where_clauses );

		// Total count
		$total_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
		if ( ! empty( $where_values ) ) {
			$total_sql = $wpdb->prepare( $total_sql, $where_values );
		}
		$total = (int) $wpdb->get_var( $total_sql );

		// Sent count
		$sent_where = $where . ' AND status = %s';
		$sent_values = array_merge( $where_values, array( 'sent' ) );
		$sent = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE {$sent_where}",
				$sent_values
			)
		);

		// Failed count
		$failed_where = $where . ' AND status = %s';
		$failed_values = array_merge( $where_values, array( 'failed' ) );
		$failed = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE {$failed_where}",
				$failed_values
			)
		);

		// By type
		$by_type_sql = "SELECT email_type, COUNT(*) as count FROM {$table} WHERE {$where} GROUP BY email_type";
		if ( ! empty( $where_values ) ) {
			$by_type_sql = $wpdb->prepare( $by_type_sql, $where_values );
		}
		$by_type_results = $wpdb->get_results( $by_type_sql );
		$by_type = array();
		foreach ( $by_type_results as $row ) {
			$by_type[ $row->email_type ] = (int) $row->count;
		}

		// Success rate
		$success_rate = $total > 0 ? round( ( $sent / $total ) * 100, 2 ) : 0;

		$stats = array(
			'total'        => $total,
			'sent'         => $sent,
			'failed'       => $failed,
			'by_type'      => $by_type,
			'success_rate' => $success_rate,
		);

		// Cache for 5 minutes (300 seconds)
		wp_cache_set( $cache_key, $stats, 'booknow', 300 );

		return $stats;
	}

	/**
	 * Get email logs for a specific booking
	 *
	 * @param int $booking_id Booking ID.
	 * @return array Array of email log objects.
	 */
	public static function get_by_booking( $booking_id ) {
		global $wpdb;

		$booking_id = absint( $booking_id );
		if ( empty( $booking_id ) ) {
			return array();
		}

		$table = $wpdb->prefix . 'booknow_email_log';

		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE booking_id = %d ORDER BY sent_at DESC",
				$booking_id
			)
		);

		// Decrypt email bodies if present
		if ( ! empty( $logs ) ) {
			foreach ( $logs as $log ) {
				if ( ! empty( $log->email_body ) ) {
					$log->email_body = self::get_decrypted_body( $log );
				}
			}
		}

		return $logs;
	}

	/**
	 * Clear email log cache
	 *
	 * Clears all cached email log data.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		wp_cache_delete_group( 'booknow' );
	}

	/**
	 * Get decrypted email body from a log entry
	 *
	 * Handles backward compatibility with unencrypted data.
	 *
	 * @param object $log Email log object with email_body field.
	 * @return string Decrypted email body or original if not encrypted.
	 */
	public static function get_decrypted_body( $log ) {
		if ( empty( $log ) || empty( $log->email_body ) ) {
			return '';
		}

		// Check if encryption class is available
		if ( ! class_exists( 'Book_Now_Encryption' ) ) {
			return $log->email_body;
		}

		// Check if body is encrypted
		if ( Book_Now_Encryption::is_encrypted( $log->email_body ) ) {
			return Book_Now_Encryption::decrypt( $log->email_body );
		}

		// Return as-is if not encrypted (backward compatibility)
		return $log->email_body;
	}
}
