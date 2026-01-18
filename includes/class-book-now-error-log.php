<?php
/**
 * Error Log Data Retrieval
 *
 * Handles database operations for error logs including retrieval,
 * filtering, pagination, deletion, and export.
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 * @since      1.3.2
 */

class Book_Now_Error_Log {

	/**
	 * Get paginated error logs with filters
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type int    $per_page      Number of logs per page. Default 20.
	 *     @type int    $page          Current page number. Default 1.
	 *     @type string $error_level   Filter by error level. Default empty (all levels).
	 *     @type string $error_source  Filter by source. Default empty.
	 *     @type int    $booking_id    Filter by booking ID. Default 0 (all bookings).
	 *     @type int    $user_id       Filter by user ID. Default 0 (all users).
	 *     @type string $date_from     Filter from date (Y-m-d). Default empty.
	 *     @type string $date_to       Filter to date (Y-m-d). Default empty.
	 *     @type string $search        Search term for error message. Default empty.
	 *     @type string $orderby       Order by column. Default 'created_at'.
	 *     @type string $order         Sort order (ASC/DESC). Default 'DESC'.
	 * }
	 * @return array {
	 *     Error logs data.
	 *
	 *     @type array $logs      Array of error log objects.
	 *     @type int   $total     Total number of matching logs.
	 *     @type int   $page      Current page number.
	 *     @type int   $per_page  Logs per page.
	 *     @type int   $pages     Total number of pages.
	 * }
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'per_page'     => 20,
			'page'         => 1,
			'error_level'  => '',
			'error_source' => '',
			'booking_id'   => 0,
			'user_id'      => 0,
			'date_from'    => '',
			'date_to'      => '',
			'search'       => '',
			'orderby'      => 'created_at',
			'order'        => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Sanitize inputs
		$per_page     = absint( $args['per_page'] );
		$page         = absint( $args['page'] );
		$booking_id   = absint( $args['booking_id'] );
		$user_id      = absint( $args['user_id'] );
		$error_level  = sanitize_text_field( $args['error_level'] );
		$error_source = sanitize_text_field( $args['error_source'] );
		$search       = sanitize_text_field( $args['search'] );
		$orderby      = sanitize_sql_orderby( $args['orderby'] );
		$order        = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Ensure per_page is reasonable
		$per_page = min( max( 1, $per_page ), 100 );
		$page     = max( 1, $page );

		// Valid orderby columns
		$valid_orderby = array( 'id', 'created_at', 'error_level', 'error_source', 'booking_id', 'user_id' );
		if ( ! in_array( $orderby, $valid_orderby, true ) ) {
			$orderby = 'created_at';
		}

		$table = $wpdb->prefix . 'booknow_error_log';

		// Build WHERE clause
		$where_clauses = array( '1=1' );
		$where_values  = array();

		if ( ! empty( $error_level ) ) {
			$where_clauses[] = 'error_level = %s';
			$where_values[]  = $error_level;
		}

		if ( ! empty( $error_source ) ) {
			$where_clauses[] = 'error_source = %s';
			$where_values[]  = $error_source;
		}

		if ( ! empty( $booking_id ) ) {
			$where_clauses[] = 'booking_id = %d';
			$where_values[]  = $booking_id;
		}

		if ( ! empty( $user_id ) ) {
			$where_clauses[] = 'user_id = %d';
			$where_values[]  = $user_id;
		}

		if ( ! empty( $args['date_from'] ) ) {
			$date_from = sanitize_text_field( $args['date_from'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
				$where_clauses[] = 'DATE(created_at) >= %s';
				$where_values[]  = $date_from;
			}
		}

		if ( ! empty( $args['date_to'] ) ) {
			$date_to = sanitize_text_field( $args['date_to'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
				$where_clauses[] = 'DATE(created_at) <= %s';
				$where_values[]  = $date_to;
			}
		}

		if ( ! empty( $search ) ) {
			$where_clauses[] = 'error_message LIKE %s';
			$where_values[]  = '%' . $wpdb->esc_like( $search ) . '%';
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

		return array(
			'logs'     => $logs,
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
			'pages'    => $total_pages,
		);
	}

	/**
	 * Get single error log by ID
	 *
	 * @param int $log_id Error log ID.
	 * @return object|null Error log object or null if not found.
	 */
	public static function get_log( $log_id ) {
		global $wpdb;

		$log_id = absint( $log_id );
		if ( empty( $log_id ) ) {
			return null;
		}

		$table = $wpdb->prefix . 'booknow_error_log';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$log_id
			)
		);
	}

	/**
	 * Get error logs count by level or source
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type string $error_level   Filter by error level.
	 *     @type string $error_source  Filter by source.
	 *     @type string $date_from     Filter from date (Y-m-d).
	 *     @type string $date_to       Filter to date (Y-m-d).
	 * }
	 * @return int Number of matching logs.
	 */
	public static function get_count( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'error_level'  => '',
			'error_source' => '',
			'date_from'    => '',
			'date_to'      => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$table         = $wpdb->prefix . 'booknow_error_log';
		$where_clauses = array( '1=1' );
		$where_values  = array();

		if ( ! empty( $args['error_level'] ) ) {
			$where_clauses[] = 'error_level = %s';
			$where_values[]  = sanitize_text_field( $args['error_level'] );
		}

		if ( ! empty( $args['error_source'] ) ) {
			$where_clauses[] = 'error_source = %s';
			$where_values[]  = sanitize_text_field( $args['error_source'] );
		}

		if ( ! empty( $args['date_from'] ) ) {
			$date_from = sanitize_text_field( $args['date_from'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
				$where_clauses[] = 'DATE(created_at) >= %s';
				$where_values[]  = $date_from;
			}
		}

		if ( ! empty( $args['date_to'] ) ) {
			$date_to = sanitize_text_field( $args['date_to'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
				$where_clauses[] = 'DATE(created_at) <= %s';
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
	 * Delete old error logs
	 *
	 * @param int $days Delete logs older than this many days. Default 30.
	 * @return int|false Number of deleted rows or false on failure.
	 */
	public static function delete_old_logs( $days = 30 ) {
		global $wpdb;

		$days = absint( $days );
		if ( empty( $days ) ) {
			return false;
		}

		$table = $wpdb->prefix . 'booknow_error_log';

		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		if ( $result ) {
			self::clear_cache();
		}

		return $result;
	}

	/**
	 * Delete specific error log
	 *
	 * @param int $log_id Error log ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_log( $log_id ) {
		global $wpdb;

		$log_id = absint( $log_id );
		if ( empty( $log_id ) ) {
			return false;
		}

		$table = $wpdb->prefix . 'booknow_error_log';

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
	 * Delete multiple error logs
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

		$table        = $wpdb->prefix . 'booknow_error_log';
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
	 * Export error logs to CSV
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
		$csv   = array();
		$csv[] = array(
			'ID',
			'Level',
			'Message',
			'Source',
			'Booking ID',
			'User ID',
			'IP Address',
			'User Agent',
			'Request URI',
			'Created At',
		);

		// CSV rows
		foreach ( $logs as $log ) {
			$csv[] = array(
				$log->id,
				$log->error_level,
				$log->error_message,
				! empty( $log->error_source ) ? $log->error_source : '',
				! empty( $log->booking_id ) ? $log->booking_id : '',
				! empty( $log->user_id ) ? $log->user_id : '',
				! empty( $log->ip_address ) ? $log->ip_address : '',
				! empty( $log->user_agent ) ? $log->user_agent : '',
				! empty( $log->request_uri ) ? $log->request_uri : '',
				$log->created_at,
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
	 * Get error statistics
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type string $date_from   Filter from date (Y-m-d).
	 *     @type string $date_to     Filter to date (Y-m-d).
	 * }
	 * @return array {
	 *     Error statistics.
	 *
	 *     @type int   $total        Total errors.
	 *     @type array $by_level     Count by error level.
	 *     @type array $by_source    Count by error source.
	 *     @type array $recent       Recent 10 errors.
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
		$cache_key = 'booknow_error_stats_' . md5( serialize( $args ) );
		$stats     = wp_cache_get( $cache_key, 'booknow' );

		if ( false !== $stats ) {
			return $stats;
		}

		$table         = $wpdb->prefix . 'booknow_error_log';
		$where_clauses = array( '1=1' );
		$where_values  = array();

		if ( ! empty( $args['date_from'] ) ) {
			$date_from = sanitize_text_field( $args['date_from'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
				$where_clauses[] = 'DATE(created_at) >= %s';
				$where_values[]  = $date_from;
			}
		}

		if ( ! empty( $args['date_to'] ) ) {
			$date_to = sanitize_text_field( $args['date_to'] );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
				$where_clauses[] = 'DATE(created_at) <= %s';
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

		// By level
		$by_level_sql = "SELECT error_level, COUNT(*) as count FROM {$table} WHERE {$where} GROUP BY error_level";
		if ( ! empty( $where_values ) ) {
			$by_level_sql = $wpdb->prepare( $by_level_sql, $where_values );
		}
		$by_level_results = $wpdb->get_results( $by_level_sql );
		$by_level         = array();
		foreach ( $by_level_results as $row ) {
			$by_level[ $row->error_level ] = (int) $row->count;
		}

		// By source
		$by_source_sql = "SELECT error_source, COUNT(*) as count FROM {$table} WHERE {$where} AND error_source IS NOT NULL GROUP BY error_source ORDER BY count DESC LIMIT 10";
		if ( ! empty( $where_values ) ) {
			$by_source_sql = $wpdb->prepare( $by_source_sql, $where_values );
		}
		$by_source_results = $wpdb->get_results( $by_source_sql );
		$by_source         = array();
		foreach ( $by_source_results as $row ) {
			$by_source[ $row->error_source ] = (int) $row->count;
		}

		// Recent errors
		$recent_sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT 10";
		if ( ! empty( $where_values ) ) {
			$recent_sql = $wpdb->prepare( $recent_sql, $where_values );
		}
		$recent = $wpdb->get_results( $recent_sql );

		$stats = array(
			'total'     => $total,
			'by_level'  => $by_level,
			'by_source' => $by_source,
			'recent'    => $recent,
		);

		// Cache for 5 minutes (300 seconds)
		wp_cache_set( $cache_key, $stats, 'booknow', 300 );

		return $stats;
	}

	/**
	 * Get error logs for a specific booking
	 *
	 * @param int $booking_id Booking ID.
	 * @return array Array of error log objects.
	 */
	public static function get_by_booking( $booking_id ) {
		global $wpdb;

		$booking_id = absint( $booking_id );
		if ( empty( $booking_id ) ) {
			return array();
		}

		$table = $wpdb->prefix . 'booknow_error_log';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE booking_id = %d ORDER BY created_at DESC",
				$booking_id
			)
		);
	}

	/**
	 * Log an error to database
	 *
	 * @param string $level    Error level (DEBUG, INFO, WARNING, ERROR, CRITICAL).
	 * @param string $message  Error message.
	 * @param array  $context  Optional. Context data including source, booking_id, etc.
	 * @return int|false Insert ID on success, false on failure.
	 */
	public static function log( $level, $message, $context = array() ) {
		global $wpdb;

		// Validate level
		$valid_levels = array( 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' );
		if ( ! in_array( $level, $valid_levels, true ) ) {
			$level = 'ERROR';
		}

		$table = $wpdb->prefix . 'booknow_error_log';

		$client_ip = self::get_client_ip();

		$data = array(
			'error_level'   => $level,
			'error_message' => sanitize_text_field( $message ),
			'error_context' => ! empty( $context ) ? wp_json_encode( $context ) : null,
			'error_source'  => isset( $context['source'] ) ? sanitize_text_field( $context['source'] ) : null,
			'booking_id'    => isset( $context['booking_id'] ) ? absint( $context['booking_id'] ) : null,
			'user_id'       => isset( $context['user_id'] ) ? absint( $context['user_id'] ) : get_current_user_id(),
			'ip_address'    => $client_ip ? self::anonymize_ip( $client_ip ) : null,
			'user_agent'    => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : null,
			'request_uri'   => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : null,
		);

		$format = array(
			'%s', // error_level
			'%s', // error_message
			'%s', // error_context
			'%s', // error_source
			'%d', // booking_id
			'%d', // user_id
			'%s', // ip_address
			'%s', // user_agent
			'%s', // request_uri
		);

		$result = $wpdb->insert( $table, $data, $format );

		if ( $result ) {
			self::clear_cache();
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Anonymize IP address for GDPR compliance
	 *
	 * @param string $ip_address IP address to anonymize.
	 * @return string Anonymized IP address.
	 */
	private static function anonymize_ip( $ip_address ) {
		if ( empty( $ip_address ) ) {
			return '';
		}

		// IPv4 - mask last octet
		if ( filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return preg_replace( '/\.\d+$/', '.xxx', $ip_address );
		}

		// IPv6 - mask last 80 bits (keep first 48 bits)
		if ( filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$parts = explode( ':', $ip_address );
			return implode( ':', array_slice( $parts, 0, 3 ) ) . ':xxxx:xxxx:xxxx:xxxx:xxxx';
		}

		return 'xxx.xxx.xxx.xxx';
	}

	/**
	 * Get client IP address
	 *
	 * @return string|null Client IP address or null.
	 */
	private static function get_client_ip() {
		$ip = null;

		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		// Validate IP
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return null;
	}

	/**
	 * Clear all error logs
	 *
	 * @return int|false Number of deleted rows or false on failure.
	 */
	public static function clear_all() {
		global $wpdb;

		$table = $wpdb->prefix . 'booknow_error_log';

		return $wpdb->query( "TRUNCATE TABLE {$table}" );
	}

	/**
	 * Get unique error sources
	 *
	 * @return array Array of unique error sources.
	 */
	public static function get_sources() {
		global $wpdb;

		$cache_key = 'booknow_error_sources';
		$sources   = wp_cache_get( $cache_key, 'booknow' );

		if ( false !== $sources ) {
			return $sources;
		}

		$table = $wpdb->prefix . 'booknow_error_log';

		$results = $wpdb->get_col(
			"SELECT DISTINCT error_source FROM {$table} WHERE error_source IS NOT NULL ORDER BY error_source ASC"
		);

		$sources = $results ? $results : array();

		// Cache for 1 hour (3600 seconds)
		wp_cache_set( $cache_key, $sources, 'booknow', 3600 );

		return $sources;
	}

	/**
	 * Clear error log cache
	 *
	 * Clears all cached error log data.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		wp_cache_delete_group( 'booknow' );
	}
}
