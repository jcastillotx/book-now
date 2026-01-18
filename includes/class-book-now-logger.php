<?php
/**
 * Logger utility for Book Now plugin
 *
 * Provides centralized logging with multiple severity levels.
 * Only logs when WP_DEBUG is true OR BOOKNOW_DEBUG is defined.
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 * @since      1.1.0
 */

class Book_Now_Logger {

    /**
     * Log level constants
     */
    const LEVEL_DEBUG    = 'DEBUG';
    const LEVEL_INFO     = 'INFO';
    const LEVEL_WARNING  = 'WARNING';
    const LEVEL_ERROR    = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';

    /**
     * Check if logging is enabled.
     *
     * Logging is enabled when WP_DEBUG is true OR BOOKNOW_DEBUG is defined.
     *
     * @return bool
     */
    private static function is_enabled() {
        return ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || defined( 'BOOKNOW_DEBUG' );
    }

    /**
     * Check if database logging is enabled.
     *
     * Database logging is enabled when BOOKNOW_DB_LOG is defined as true.
     *
     * @return bool
     */
    private static function is_db_logging_enabled() {
        return defined( 'BOOKNOW_DB_LOG' ) && BOOKNOW_DB_LOG;
    }

    /**
     * Format the log message.
     *
     * @param string $level   Log level.
     * @param string $message Log message.
     * @param array  $context Optional context data.
     * @return string
     */
    private static function format_message( $level, $message, $context = array() ) {
        $timestamp = current_time( 'Y-m-d H:i:s' );
        $formatted = sprintf( '[BookNow] [%s] [%s] %s', $level, $timestamp, $message );

        if ( ! empty( $context ) ) {
            $formatted .= ' - ' . wp_json_encode( $context );
        }

        return $formatted;
    }

    /**
     * Write log message.
     *
     * @param string $level   Log level.
     * @param string $message Log message.
     * @param array  $context Optional context data.
     * @return void
     */
    private static function log( $level, $message, $context = array() ) {
        if ( ! self::is_enabled() ) {
            return;
        }

        $formatted_message = self::format_message( $level, $message, $context );
        error_log( $formatted_message );

        // Also log to database for ERROR and CRITICAL levels
        if ( self::is_db_logging_enabled() && in_array( $level, array( self::LEVEL_ERROR, self::LEVEL_CRITICAL ), true ) ) {
            self::log_to_database( $level, $message, $context );
        }
    }

    /**
     * Log message to database.
     *
     * @param string $level   Log level.
     * @param string $message Log message.
     * @param array  $context Optional context data.
     * @return void
     */
    private static function log_to_database( $level, $message, $context = array() ) {
        if ( ! class_exists( 'Book_Now_Error_Log' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-book-now-error-log.php';
        }

        Book_Now_Error_Log::log( $level, $message, $context );
    }

    /**
     * Log a debug message.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     * @return void
     */
    public static function debug( $message, $context = array() ) {
        self::log( self::LEVEL_DEBUG, $message, $context );
    }

    /**
     * Log an info message.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     * @return void
     */
    public static function info( $message, $context = array() ) {
        self::log( self::LEVEL_INFO, $message, $context );
    }

    /**
     * Log a warning message.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     * @return void
     */
    public static function warning( $message, $context = array() ) {
        self::log( self::LEVEL_WARNING, $message, $context );
    }

    /**
     * Log an error message.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     * @return void
     */
    public static function error( $message, $context = array() ) {
        self::log( self::LEVEL_ERROR, $message, $context );
    }

    /**
     * Log a critical message.
     *
     * @param string $message Log message.
     * @param array  $context Optional context data.
     * @return void
     */
    public static function critical( $message, $context = array() ) {
        self::log( self::LEVEL_CRITICAL, $message, $context );
    }
}
