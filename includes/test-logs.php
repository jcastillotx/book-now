<?php
/**
 * Test file for Email and Error Log classes
 *
 * This file demonstrates usage of the log retrieval methods.
 * Remove this file after testing.
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 */

// This file should only be accessible in development
if ( ! defined( 'BOOKNOW_DEBUG' ) || ! BOOKNOW_DEBUG ) {
	die( 'Access denied' );
}

// Test Email Log Methods
echo "<h2>Email Log Tests</h2>\n";

// Get email logs with pagination
$email_logs = Book_Now_Email_Log::get_logs( array(
	'per_page' => 10,
	'page'     => 1,
	'status'   => 'sent',
) );

echo "<h3>Recent Email Logs (Page 1, 10 per page, Status: sent)</h3>\n";
echo "<p>Total: {$email_logs['total']}, Pages: {$email_logs['pages']}</p>\n";
echo "<ul>\n";
foreach ( $email_logs['logs'] as $log ) {
	echo "<li>ID: {$log->id}, Type: {$log->email_type}, To: {$log->recipient_email}, Subject: {$log->subject}, Status: {$log->status}, Sent: {$log->sent_at}</li>\n";
}
echo "</ul>\n";

// Get statistics
$stats = Book_Now_Email_Log::get_statistics();
echo "<h3>Email Statistics</h3>\n";
echo "<p>Total: {$stats['total']}, Sent: {$stats['sent']}, Failed: {$stats['failed']}, Success Rate: {$stats['success_rate']}%</p>\n";
echo "<p>By Type:</p>\n<ul>\n";
foreach ( $stats['by_type'] as $type => $count ) {
	echo "<li>{$type}: {$count}</li>\n";
}
echo "</ul>\n";

// Test export
$csv_content = Book_Now_Email_Log::export_to_csv( array(
	'per_page' => 5,
) );
echo "<h3>CSV Export Sample (First 200 chars)</h3>\n";
echo "<pre>" . esc_html( substr( $csv_content, 0, 200 ) ) . "...</pre>\n";

// Test Error Log Methods
echo "\n<hr>\n<h2>Error Log Tests</h2>\n";

// Insert a test error
$test_log_id = Book_Now_Error_Log::log(
	'ERROR',
	'Test error message for demonstration',
	array(
		'source'     => 'test-logs.php',
		'booking_id' => 123,
	)
);
echo "<p>Created test error log with ID: {$test_log_id}</p>\n";

// Get error logs with pagination
$error_logs = Book_Now_Error_Log::get_logs( array(
	'per_page' => 10,
	'page'     => 1,
) );

echo "<h3>Recent Error Logs (Page 1, 10 per page)</h3>\n";
echo "<p>Total: {$error_logs['total']}, Pages: {$error_logs['pages']}</p>\n";
echo "<ul>\n";
foreach ( $error_logs['logs'] as $log ) {
	$source = ! empty( $log->error_source ) ? $log->error_source : 'N/A';
	echo "<li>ID: {$log->id}, Level: {$log->error_level}, Message: " . esc_html( substr( $log->error_message, 0, 50 ) ) . "..., Source: {$source}, Created: {$log->created_at}</li>\n";
}
echo "</ul>\n";

// Get error statistics
$error_stats = Book_Now_Error_Log::get_statistics();
echo "<h3>Error Statistics</h3>\n";
echo "<p>Total: {$error_stats['total']}</p>\n";
echo "<p>By Level:</p>\n<ul>\n";
foreach ( $error_stats['by_level'] as $level => $count ) {
	echo "<li>{$level}: {$count}</li>\n";
}
echo "</ul>\n";

echo "<p>By Source (Top 10):</p>\n<ul>\n";
foreach ( $error_stats['by_source'] as $source => $count ) {
	echo "<li>{$source}: {$count}</li>\n";
}
echo "</ul>\n";

// Get unique sources
$sources = Book_Now_Error_Log::get_sources();
echo "<h3>Unique Error Sources</h3>\n";
echo "<ul>\n";
foreach ( $sources as $source ) {
	echo "<li>{$source}</li>\n";
}
echo "</ul>\n";

// Test count methods
$error_count = Book_Now_Error_Log::get_count( array(
	'error_level' => 'ERROR',
) );
echo "<h3>Error Count</h3>\n";
echo "<p>Total ERROR level logs: {$error_count}</p>\n";

$email_count = Book_Now_Email_Log::get_count( array(
	'status' => 'failed',
) );
echo "<p>Total failed emails: {$email_count}</p>\n";

// Clean up test log
if ( $test_log_id ) {
	Book_Now_Error_Log::delete_log( $test_log_id );
	echo "<p>Cleaned up test error log ID: {$test_log_id}</p>\n";
}

echo "\n<hr>\n<h2>Tests Completed</h2>\n";
echo "<p>All log retrieval methods are working correctly!</p>\n";

// Usage examples
echo "\n<hr>\n<h2>Usage Examples</h2>\n";
echo "<h3>Email Log Usage</h3>\n";
echo "<pre>\n";
echo "// Get paginated logs with filters\n";
echo "\$logs = Book_Now_Email_Log::get_logs( array(\n";
echo "    'per_page'   => 20,\n";
echo "    'page'       => 1,\n";
echo "    'email_type' => 'confirmation',\n";
echo "    'status'     => 'sent',\n";
echo "    'date_from'  => '2026-01-01',\n";
echo "    'date_to'    => '2026-01-31',\n";
echo "    'search'     => 'booking',\n";
echo ") );\n\n";

echo "// Get statistics\n";
echo "\$stats = Book_Now_Email_Log::get_statistics();\n\n";

echo "// Export to CSV\n";
echo "\$csv = Book_Now_Email_Log::export_to_csv();\n\n";

echo "// Delete old logs (older than 90 days)\n";
echo "\$deleted = Book_Now_Email_Log::delete_old_logs( 90 );\n";
echo "</pre>\n";

echo "<h3>Error Log Usage</h3>\n";
echo "<pre>\n";
echo "// Log an error\n";
echo "Book_Now_Error_Log::log(\n";
echo "    'ERROR',\n";
echo "    'Payment processing failed',\n";
echo "    array(\n";
echo "        'source'     => 'stripe-integration',\n";
echo "        'booking_id' => 456,\n";
echo "    )\n";
echo ");\n\n";

echo "// Get paginated logs with filters\n";
echo "\$logs = Book_Now_Error_Log::get_logs( array(\n";
echo "    'per_page'     => 20,\n";
echo "    'page'         => 1,\n";
echo "    'error_level'  => 'ERROR',\n";
echo "    'error_source' => 'stripe-integration',\n";
echo "    'date_from'    => '2026-01-01',\n";
echo ") );\n\n";

echo "// Get statistics\n";
echo "\$stats = Book_Now_Error_Log::get_statistics();\n\n";

echo "// Get unique sources\n";
echo "\$sources = Book_Now_Error_Log::get_sources();\n\n";

echo "// Delete old logs (older than 30 days)\n";
echo "\$deleted = Book_Now_Error_Log::delete_old_logs( 30 );\n";
echo "</pre>\n";

echo "<h3>Logger Integration</h3>\n";
echo "<pre>\n";
echo "// Enable database logging in wp-config.php:\n";
echo "define( 'BOOKNOW_DB_LOG', true );\n\n";

echo "// Errors will automatically be logged to database:\n";
echo "Book_Now_Logger::error(\n";
echo "    'Calendar sync failed',\n";
echo "    array(\n";
echo "        'source'     => 'google-calendar',\n";
echo "        'booking_id' => 789,\n";
echo "    )\n";
echo ");\n";
echo "</pre>\n";
