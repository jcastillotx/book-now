<?php
/**
 * Rate Limiter for API abuse prevention.
 *
 * Uses WordPress transients to track and limit requests by IP address.
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 * @since      1.1.0
 */

class Book_Now_Rate_Limiter {

    /**
     * Transient prefix for rate limiting data.
     *
     * @var string
     */
    const TRANSIENT_PREFIX = 'booknow_rate_';

    /**
     * Predefined rate limits for common actions.
     *
     * @var array
     */
    private static $limits = array(
        'booking_create'     => array('limit' => 5, 'window' => 3600),      // 5 per hour
        'availability_check' => array('limit' => 60, 'window' => 60),       // 60 per minute
        'booking_lookup'     => array('limit' => 20, 'window' => 60),       // 20 per minute
    );

    /**
     * Check if an action is allowed under rate limits.
     *
     * Increments the request counter and checks against the limit.
     *
     * @param string $action         The action identifier (e.g., 'booking_create').
     * @param int    $limit          Maximum requests allowed in the window.
     * @param int    $window_seconds Time window in seconds.
     * @return bool True if allowed, false if rate limited.
     */
    public static function check( $action, $limit = null, $window_seconds = null ) {
        // Use predefined limits if not specified
        if ( null === $limit || null === $window_seconds ) {
            if ( isset( self::$limits[ $action ] ) ) {
                $limit = self::$limits[ $action ]['limit'];
                $window_seconds = self::$limits[ $action ]['window'];
            } else {
                // Default fallback: 100 requests per minute
                $limit = 100;
                $window_seconds = 60;
            }
        }

        $ip = self::get_client_ip();
        $transient_key = self::get_transient_key( $action, $ip );

        // Get current request data
        $data = get_transient( $transient_key );

        if ( false === $data ) {
            // First request - initialize tracking
            $data = array(
                'count'      => 1,
                'first_request' => time(),
            );
            set_transient( $transient_key, $data, $window_seconds );
            return true;
        }

        // Check if window has expired (transient should handle this, but double-check)
        $elapsed = time() - $data['first_request'];
        if ( $elapsed >= $window_seconds ) {
            // Window expired, reset counter
            $data = array(
                'count'      => 1,
                'first_request' => time(),
            );
            set_transient( $transient_key, $data, $window_seconds );
            return true;
        }

        // Check if under limit
        if ( $data['count'] < $limit ) {
            // Increment counter
            $data['count']++;
            $remaining_time = $window_seconds - $elapsed;
            set_transient( $transient_key, $data, $remaining_time );
            return true;
        }

        // Rate limit exceeded
        return false;
    }

    /**
     * Get remaining requests for an action.
     *
     * @param string $action         The action identifier.
     * @param int    $limit          Maximum requests allowed in the window (optional).
     * @param int    $window_seconds Time window in seconds (optional).
     * @return int Number of remaining requests.
     */
    public static function get_remaining( $action, $limit = null, $window_seconds = null ) {
        // Use predefined limits if not specified
        if ( null === $limit ) {
            if ( isset( self::$limits[ $action ] ) ) {
                $limit = self::$limits[ $action ]['limit'];
            } else {
                $limit = 100;
            }
        }

        $ip = self::get_client_ip();
        $transient_key = self::get_transient_key( $action, $ip );

        $data = get_transient( $transient_key );

        if ( false === $data ) {
            return $limit;
        }

        $remaining = $limit - $data['count'];
        return max( 0, $remaining );
    }

    /**
     * Check if an action is currently blocked.
     *
     * Unlike check(), this does not increment the counter.
     *
     * @param string $action         The action identifier.
     * @param int    $limit          Maximum requests allowed in the window (optional).
     * @param int    $window_seconds Time window in seconds (optional).
     * @return bool True if blocked (rate limited), false otherwise.
     */
    public static function is_blocked( $action, $limit = null, $window_seconds = null ) {
        // Use predefined limits if not specified
        if ( null === $limit || null === $window_seconds ) {
            if ( isset( self::$limits[ $action ] ) ) {
                $limit = self::$limits[ $action ]['limit'];
                $window_seconds = self::$limits[ $action ]['window'];
            } else {
                $limit = 100;
                $window_seconds = 60;
            }
        }

        $ip = self::get_client_ip();
        $transient_key = self::get_transient_key( $action, $ip );

        $data = get_transient( $transient_key );

        if ( false === $data ) {
            return false;
        }

        // Check if window has expired
        $elapsed = time() - $data['first_request'];
        if ( $elapsed >= $window_seconds ) {
            return false;
        }

        return $data['count'] >= $limit;
    }

    /**
     * Get time until rate limit resets in seconds.
     *
     * @param string $action         The action identifier.
     * @param int    $window_seconds Time window in seconds (optional).
     * @return int Seconds until reset, 0 if not limited.
     */
    public static function get_retry_after( $action, $window_seconds = null ) {
        // Use predefined limits if not specified
        if ( null === $window_seconds ) {
            if ( isset( self::$limits[ $action ] ) ) {
                $window_seconds = self::$limits[ $action ]['window'];
            } else {
                $window_seconds = 60;
            }
        }

        $ip = self::get_client_ip();
        $transient_key = self::get_transient_key( $action, $ip );

        $data = get_transient( $transient_key );

        if ( false === $data ) {
            return 0;
        }

        $elapsed = time() - $data['first_request'];
        $remaining = $window_seconds - $elapsed;

        return max( 0, $remaining );
    }

    /**
     * Reset rate limit for a specific action and IP.
     *
     * Useful for testing or administrative overrides.
     *
     * @param string      $action The action identifier.
     * @param string|null $ip     Optional IP address. Defaults to current client IP.
     * @return bool True if reset successful.
     */
    public static function reset( $action, $ip = null ) {
        if ( null === $ip ) {
            $ip = self::get_client_ip();
        }

        $transient_key = self::get_transient_key( $action, $ip );
        return delete_transient( $transient_key );
    }

    /**
     * Get the client IP address.
     *
     * Handles various proxy configurations securely.
     *
     * @return string Client IP address.
     */
    private static function get_client_ip() {
        $ip = '';

        // Check for CloudFlare
        if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
        }
        // Check for proxy headers (be cautious with these as they can be spoofed)
        elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            // X-Forwarded-For can contain multiple IPs, use the first one (client IP)
            $ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
            $ip = trim( $ips[0] );
        }
        elseif ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
        }
        elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        // Validate IP format
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            $ip = '0.0.0.0';
        }

        return $ip;
    }

    /**
     * Generate transient key for an action and IP.
     *
     * @param string $action Action identifier.
     * @param string $ip     IP address.
     * @return string Transient key.
     */
    private static function get_transient_key( $action, $ip ) {
        // Create a hash of the IP to avoid issues with IPv6 length
        $ip_hash = substr( md5( $ip ), 0, 12 );
        $action_sanitized = sanitize_key( $action );

        // Transient keys must be <= 172 characters (for wp_options name field)
        // Our format: booknow_rate_{action}_{ip_hash} should be well under that
        return self::TRANSIENT_PREFIX . $action_sanitized . '_' . $ip_hash;
    }

    /**
     * Create a WP_Error for rate limit exceeded.
     *
     * @param string $action The action that was rate limited.
     * @return WP_Error Rate limit error with appropriate message.
     */
    public static function get_rate_limit_error( $action ) {
        $retry_after = self::get_retry_after( $action );

        return new WP_Error(
            'rate_limit_exceeded',
            sprintf(
                /* translators: %d: seconds until rate limit resets */
                __( 'Too many requests. Please try again in %d seconds.', 'book-now-kre8iv' ),
                $retry_after
            ),
            array(
                'status'      => 429,
                'retry_after' => $retry_after,
            )
        );
    }

    /**
     * Add rate limit headers to response.
     *
     * @param string $action         The action identifier.
     * @param int    $limit          Maximum requests allowed (optional).
     * @param int    $window_seconds Time window in seconds (optional).
     */
    public static function send_rate_limit_headers( $action, $limit = null, $window_seconds = null ) {
        // Use predefined limits if not specified
        if ( null === $limit || null === $window_seconds ) {
            if ( isset( self::$limits[ $action ] ) ) {
                $limit = self::$limits[ $action ]['limit'];
                $window_seconds = self::$limits[ $action ]['window'];
            } else {
                $limit = 100;
                $window_seconds = 60;
            }
        }

        $remaining = self::get_remaining( $action, $limit );
        $retry_after = self::get_retry_after( $action, $window_seconds );

        header( 'X-RateLimit-Limit: ' . $limit );
        header( 'X-RateLimit-Remaining: ' . $remaining );
        header( 'X-RateLimit-Reset: ' . ( time() + $retry_after ) );

        if ( $remaining <= 0 ) {
            header( 'Retry-After: ' . $retry_after );
        }
    }

    /**
     * Get all predefined limits configuration.
     *
     * @return array Array of action => limit configuration.
     */
    public static function get_limits() {
        return self::$limits;
    }

    /**
     * Allow filtering/modifying limits via WordPress filter.
     *
     * Should be called early in the request lifecycle.
     */
    public static function init() {
        /**
         * Filter rate limits configuration.
         *
         * @param array $limits Array of action => array('limit' => int, 'window' => int).
         */
        self::$limits = apply_filters( 'booknow_rate_limits', self::$limits );
    }
}

// Initialize rate limits (allows filtering)
add_action( 'plugins_loaded', array( 'Book_Now_Rate_Limiter', 'init' ), 5 );
