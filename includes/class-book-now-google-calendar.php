<?php
/**
 * Google Calendar Integration
 *
 * Provides OAuth 2.0 authentication and calendar synchronization
 * with Google Calendar API.
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 * @since      1.0.0
 */

class Book_Now_Google_Calendar {

    /**
     * Google Client instance.
     *
     * @var Google_Client|null
     */
    private $client;

    /**
     * Calendar service instance.
     *
     * @var Google_Service_Calendar|null
     */
    private $service;

    /**
     * OAuth2 service for user info.
     *
     * @var Google_Service_Oauth2|null
     */
    private $oauth2_service;

    /**
     * Settings array.
     *
     * @var array
     */
    private $settings;

    /**
     * OAuth scopes required for calendar integration.
     *
     * @var array
     */
    private const OAUTH_SCOPES = array(
        'https://www.googleapis.com/auth/calendar',
        'https://www.googleapis.com/auth/userinfo.email',
    );

    /**
     * Option key for storing encrypted tokens.
     *
     * @var string
     */
    private const TOKEN_OPTION_KEY = 'booknow_google_calendar_tokens';

    /**
     * Option key for storing connection metadata.
     *
     * @var string
     */
    private const CONNECTION_META_KEY = 'booknow_google_calendar_connection';

    /**
     * Initialize Google Calendar.
     */
    public function __construct() {
        $this->load_settings();
        $this->init_client();
    }

    /**
     * Load calendar settings.
     *
     * @return void
     */
    private function load_settings(): void {
        // Load integration settings (credentials) with automatic decryption
        $integration_settings = Book_Now_Encryption::get_integration_settings();

        // Load calendar settings (calendar_id, etc.)
        $calendar_settings = get_option( 'booknow_calendar_settings', array() );

        // Merge integration credentials with calendar settings
        // Integration settings take precedence for credentials
        $this->settings = array_merge( $calendar_settings, array(
            'google_client_id'     => $integration_settings['google_client_id'] ?? '',
            'google_client_secret' => $integration_settings['google_client_secret'] ?? '',
            'google_calendar_id'   => $integration_settings['google_calendar_id'] ?? ( $calendar_settings['google_calendar_id'] ?? 'primary' ),
        ) );
    }

    /**
     * Initialize Google Client.
     *
     * @return void
     */
    private function init_client(): void {
        if ( ! $this->is_configured() ) {
            return;
        }

        if ( ! class_exists( 'Google_Client' ) ) {
            $autoload_path = BOOK_NOW_PLUGIN_DIR . 'vendor/autoload.php';
            if ( file_exists( $autoload_path ) ) {
                require_once $autoload_path;
            } else {
                error_log( 'BookNow: Google API autoload file not found.' );
                return;
            }
        }

        try {
            $this->client = new Google_Client();
            $this->client->setApplicationName( 'Book Now Plugin' );
            $this->client->setScopes( self::OAUTH_SCOPES );
            $this->client->setAuthConfig( array(
                'client_id'     => $this->settings['google_client_id'],
                'client_secret' => $this->settings['google_client_secret'],
                'redirect_uris' => array( $this->get_redirect_uri() ),
            ) );
            $this->client->setAccessType( 'offline' );
            $this->client->setPrompt( 'consent' );
            $this->client->setIncludeGrantedScopes( true );

            // Load and set access token if available
            $tokens = $this->get_stored_tokens();
            if ( ! empty( $tokens ) ) {
                $this->client->setAccessToken( $tokens );

                // Refresh token if expired
                if ( $this->client->isAccessTokenExpired() ) {
                    $this->refresh_token();
                }
            }

            // Initialize Calendar service
            $this->service = new Google_Service_Calendar( $this->client );

            // Initialize OAuth2 service for user info
            $this->oauth2_service = new Google_Service_Oauth2( $this->client );

        } catch ( Exception $e ) {
            error_log( 'BookNow Google Calendar initialization error: ' . $e->getMessage() );
        }
    }

    /**
     * Get the OAuth redirect URI.
     *
     * @return string The redirect URI for OAuth callback.
     */
    public function get_redirect_uri(): string {
        return admin_url( 'admin.php?page=book-now-settings&tab=integration&google_oauth=callback' );
    }

    /**
     * Check if Google Calendar is configured with client credentials.
     *
     * @return bool True if client ID and secret are set.
     */
    public function is_configured(): bool {
        return ! empty( $this->settings['google_client_id'] ) &&
               ! empty( $this->settings['google_client_secret'] );
    }

    /**
     * Check if authenticated with valid tokens.
     *
     * @return bool True if authenticated with non-expired tokens.
     */
    public function is_authenticated(): bool {
        if ( ! $this->is_configured() || ! $this->client ) {
            return false;
        }

        $tokens = $this->get_stored_tokens();
        if ( empty( $tokens ) ) {
            return false;
        }

        // Check if token is valid and not expired (or can be refreshed)
        return ! $this->client->isAccessTokenExpired() ||
               ! empty( $this->client->getRefreshToken() );
    }

    /**
     * Check if connected with valid OAuth tokens.
     *
     * This method verifies that we have valid stored tokens
     * and can make API calls.
     *
     * @return bool True if connected with valid tokens.
     */
    public function is_connected(): bool {
        if ( ! $this->is_configured() ) {
            return false;
        }

        $tokens = $this->get_stored_tokens();
        if ( empty( $tokens ) || empty( $tokens['access_token'] ) ) {
            return false;
        }

        // If token is expired, try to refresh
        if ( $this->client && $this->client->isAccessTokenExpired() ) {
            if ( ! empty( $tokens['refresh_token'] ) ) {
                $refresh_result = $this->refresh_token();
                return $refresh_result !== false;
            }
            return false;
        }

        return true;
    }

    /**
     * Get the Google OAuth authorization URL.
     *
     * Generates the URL to redirect users to Google's OAuth consent screen.
     *
     * @return string|false The authorization URL or false if client not initialized.
     */
    public function get_auth_url() {
        if ( ! $this->client ) {
            return false;
        }

        // Set the redirect URI explicitly
        $this->client->setRedirectUri( $this->get_redirect_uri() );

        // Generate and return the auth URL
        return $this->client->createAuthUrl();
    }

    /**
     * Handle OAuth callback and exchange authorization code for tokens.
     *
     * Exchanges the authorization code received from Google for access
     * and refresh tokens, stores them encrypted, and fetches user info.
     *
     * @param string $code The authorization code from Google OAuth callback.
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    public function handle_oauth_callback( string $code ) {
        if ( ! $this->client ) {
            return new WP_Error(
                'client_not_initialized',
                __( 'Google Client not initialized. Please check your credentials.', 'book-now-kre8iv' )
            );
        }

        try {
            // Set redirect URI for token exchange
            $this->client->setRedirectUri( $this->get_redirect_uri() );

            // Exchange authorization code for tokens
            $token = $this->client->fetchAccessTokenWithAuthCode( $code );

            if ( isset( $token['error'] ) ) {
                $error_message = isset( $token['error_description'] )
                    ? $token['error_description']
                    : $token['error'];
                return new WP_Error( 'oauth_error', $error_message );
            }

            // Validate token structure
            if ( empty( $token['access_token'] ) ) {
                return new WP_Error(
                    'invalid_token',
                    __( 'Invalid token received from Google.', 'book-now-kre8iv' )
                );
            }

            // Store encrypted tokens
            $this->save_tokens( $token );

            // Set the token on the client
            $this->client->setAccessToken( $token );

            // Reinitialize services with new token
            $this->service = new Google_Service_Calendar( $this->client );
            $this->oauth2_service = new Google_Service_Oauth2( $this->client );

            // Fetch and store user info for connection metadata
            $this->update_connection_metadata();

            return true;

        } catch ( Exception $e ) {
            error_log( 'BookNow Google OAuth callback error: ' . $e->getMessage() );
            return new WP_Error( 'oauth_exception', $e->getMessage() );
        }
    }

    /**
     * Disconnect from Google Calendar.
     *
     * Revokes the OAuth tokens and clears all stored credentials
     * and connection metadata.
     *
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    public function disconnect() {
        try {
            // Revoke token if client is available and has a token
            if ( $this->client ) {
                $tokens = $this->get_stored_tokens();
                if ( ! empty( $tokens['access_token'] ) ) {
                    // Try to revoke the token with Google
                    try {
                        $this->client->revokeToken( $tokens['access_token'] );
                    } catch ( Exception $e ) {
                        // Log but continue - token may already be invalid
                        error_log( 'BookNow: Token revocation notice: ' . $e->getMessage() );
                    }
                }
            }

            // Clear stored tokens
            delete_option( self::TOKEN_OPTION_KEY );

            // Clear connection metadata
            delete_option( self::CONNECTION_META_KEY );

            // Clear legacy calendar settings tokens if they exist
            $calendar_settings = get_option( 'booknow_calendar_settings', array() );
            if ( isset( $calendar_settings['google_access_token'] ) ) {
                unset( $calendar_settings['google_access_token'] );
                update_option( 'booknow_calendar_settings', $calendar_settings );
            }

            // Reset client state
            $this->client  = null;
            $this->service = null;
            $this->oauth2_service = null;

            // Reinitialize
            $this->init_client();

            return true;

        } catch ( Exception $e ) {
            error_log( 'BookNow Google Calendar disconnect error: ' . $e->getMessage() );
            return new WP_Error( 'disconnect_failed', $e->getMessage() );
        }
    }

    /**
     * Get connection status information.
     *
     * Returns an array with connection status, connected email,
     * and selected calendar information.
     *
     * @return array {
     *     Connection status information.
     *
     *     @type bool   $connected     Whether connected to Google Calendar.
     *     @type bool   $configured    Whether client credentials are configured.
     *     @type string $email         Connected Google account email.
     *     @type string $calendar_name Name of the selected calendar.
     *     @type string $calendar_id   ID of the selected calendar.
     *     @type string $connected_at  ISO 8601 timestamp of when connected.
     * }
     */
    public function get_connection_status(): array {
        $status = array(
            'connected'     => false,
            'configured'    => $this->is_configured(),
            'email'         => '',
            'calendar_name' => '',
            'calendar_id'   => $this->settings['google_calendar_id'] ?? 'primary',
            'connected_at'  => '',
        );

        if ( ! $status['configured'] ) {
            return $status;
        }

        // Check if connected
        $status['connected'] = $this->is_connected();

        if ( $status['connected'] ) {
            // Get stored connection metadata
            $connection_meta = get_option( self::CONNECTION_META_KEY, array() );

            $status['email']         = $connection_meta['email'] ?? '';
            $status['calendar_name'] = $connection_meta['calendar_name'] ?? '';
            $status['connected_at']  = $connection_meta['connected_at'] ?? '';

            // If we don't have metadata, try to fetch it
            if ( empty( $status['email'] ) && $this->oauth2_service ) {
                $this->update_connection_metadata();
                $connection_meta = get_option( self::CONNECTION_META_KEY, array() );
                $status['email']         = $connection_meta['email'] ?? '';
                $status['calendar_name'] = $connection_meta['calendar_name'] ?? '';
                $status['connected_at']  = $connection_meta['connected_at'] ?? '';
            }
        }

        return $status;
    }

    /**
     * Get stored OAuth tokens.
     *
     * Retrieves and decrypts tokens from the database.
     *
     * @return array The decrypted token array or empty array if none stored.
     */
    private function get_stored_tokens(): array {
        $encrypted_tokens = get_option( self::TOKEN_OPTION_KEY, '' );

        if ( empty( $encrypted_tokens ) ) {
            // Check for legacy token storage
            $legacy_tokens = $this->settings['google_access_token'] ?? array();
            if ( ! empty( $legacy_tokens ) ) {
                // Migrate legacy tokens to encrypted storage
                $this->save_tokens( $legacy_tokens );
                return is_array( $legacy_tokens ) ? $legacy_tokens : array();
            }
            return array();
        }

        // Decrypt the stored tokens
        $decrypted = Book_Now_Encryption::decrypt( $encrypted_tokens );

        if ( empty( $decrypted ) ) {
            return array();
        }

        $tokens = json_decode( $decrypted, true );

        return is_array( $tokens ) ? $tokens : array();
    }

    /**
     * Save OAuth tokens encrypted.
     *
     * Encrypts and stores tokens in the database.
     *
     * @param array $tokens The token array from Google OAuth.
     * @return bool True on success, false on failure.
     */
    private function save_tokens( array $tokens ): bool {
        // Preserve refresh token if not in new token response
        $existing_tokens = $this->get_stored_tokens();
        if ( empty( $tokens['refresh_token'] ) && ! empty( $existing_tokens['refresh_token'] ) ) {
            $tokens['refresh_token'] = $existing_tokens['refresh_token'];
        }

        // Encode tokens as JSON
        $json_tokens = wp_json_encode( $tokens );

        if ( $json_tokens === false ) {
            error_log( 'BookNow: Failed to encode Google tokens as JSON.' );
            return false;
        }

        // Encrypt the tokens
        $encrypted_tokens = Book_Now_Encryption::encrypt( $json_tokens );

        // Store encrypted tokens
        return update_option( self::TOKEN_OPTION_KEY, $encrypted_tokens );
    }

    /**
     * Refresh access token using refresh token.
     *
     * @return array|false New token array on success, false on failure.
     */
    private function refresh_token() {
        try {
            $refresh_token = $this->client->getRefreshToken();

            if ( empty( $refresh_token ) ) {
                // Try to get from stored tokens
                $tokens = $this->get_stored_tokens();
                $refresh_token = $tokens['refresh_token'] ?? null;

                if ( empty( $refresh_token ) ) {
                    error_log( 'BookNow: No refresh token available for Google Calendar.' );
                    return false;
                }
            }

            // Fetch new access token
            $token = $this->client->fetchAccessTokenWithRefreshToken( $refresh_token );

            if ( isset( $token['error'] ) ) {
                error_log( 'BookNow: Token refresh error: ' . ( $token['error_description'] ?? $token['error'] ) );
                return false;
            }

            // Save the refreshed tokens
            $this->save_tokens( $token );

            return $token;

        } catch ( Exception $e ) {
            error_log( 'BookNow: Token refresh exception: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Update connection metadata with user info.
     *
     * Fetches the connected user's email and calendar info
     * and stores it for display in settings.
     *
     * @return bool True on success, false on failure.
     */
    private function update_connection_metadata(): bool {
        try {
            $metadata = array(
                'email'         => '',
                'calendar_name' => '',
                'connected_at'  => current_time( 'c' ),
            );

            // Get user email from OAuth2 service
            if ( $this->oauth2_service ) {
                try {
                    $user_info = $this->oauth2_service->userinfo->get();
                    $metadata['email'] = $user_info->getEmail();
                } catch ( Exception $e ) {
                    error_log( 'BookNow: Failed to get user info: ' . $e->getMessage() );
                }
            }

            // Get calendar name
            if ( $this->service ) {
                try {
                    $calendar_id = $this->settings['google_calendar_id'] ?? 'primary';
                    $calendar = $this->service->calendars->get( $calendar_id );
                    $metadata['calendar_name'] = $calendar->getSummary();
                } catch ( Exception $e ) {
                    error_log( 'BookNow: Failed to get calendar info: ' . $e->getMessage() );
                    $metadata['calendar_name'] = $this->settings['google_calendar_id'] ?? 'primary';
                }
            }

            return update_option( self::CONNECTION_META_KEY, $metadata );

        } catch ( Exception $e ) {
            error_log( 'BookNow: Failed to update connection metadata: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Create calendar event for a booking.
     *
     * @param object $booking Booking object.
     * @return string|WP_Error Event ID on success, WP_Error on failure.
     */
    public function create_event( $booking ) {
        if ( ! $this->is_authenticated() ) {
            return new WP_Error(
                'not_authenticated',
                __( 'Google Calendar not authenticated.', 'book-now-kre8iv' )
            );
        }

        try {
            $type = Book_Now_Consultation_Type::get( $booking->consultation_type_id );

            // Parse booking date and time
            $start_datetime = new DateTime( $booking->booking_date . ' ' . $booking->booking_time );
            $end_datetime   = clone $start_datetime;
            $end_datetime->add( new DateInterval( 'PT' . $type->duration . 'M' ) );

            // Create event
            $event = new Google_Service_Calendar_Event( array(
                'summary'     => $type->name . ' - ' . $booking->customer_name,
                'description' => $this->build_event_description( $booking, $type ),
                'start'       => array(
                    'dateTime' => $start_datetime->format( 'c' ),
                    'timeZone' => booknow_get_setting( 'general', 'timezone' ) ?: 'UTC',
                ),
                'end'         => array(
                    'dateTime' => $end_datetime->format( 'c' ),
                    'timeZone' => booknow_get_setting( 'general', 'timezone' ) ?: 'UTC',
                ),
                'attendees'   => array(
                    array( 'email' => $booking->customer_email ),
                ),
                'reminders'   => array(
                    'useDefault' => false,
                    'overrides'  => array(
                        array( 'method' => 'email', 'minutes' => 24 * 60 ),
                        array( 'method' => 'popup', 'minutes' => 30 ),
                    ),
                ),
            ) );

            $calendar_id   = $this->settings['google_calendar_id'] ?? 'primary';
            $created_event = $this->service->events->insert( $calendar_id, $event );

            return $created_event->getId();

        } catch ( Exception $e ) {
            error_log( 'BookNow: Google Calendar event creation failed: ' . $e->getMessage() );
            return new WP_Error( 'event_creation_failed', $e->getMessage() );
        }
    }

    /**
     * Update calendar event for a booking.
     *
     * @param string $event_id Google Calendar event ID.
     * @param object $booking  Booking object.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function update_event( string $event_id, $booking ) {
        if ( ! $this->is_authenticated() ) {
            return new WP_Error(
                'not_authenticated',
                __( 'Google Calendar not authenticated.', 'book-now-kre8iv' )
            );
        }

        try {
            $calendar_id = $this->settings['google_calendar_id'] ?? 'primary';
            $event       = $this->service->events->get( $calendar_id, $event_id );

            $type = Book_Now_Consultation_Type::get( $booking->consultation_type_id );

            $start_datetime = new DateTime( $booking->booking_date . ' ' . $booking->booking_time );
            $end_datetime   = clone $start_datetime;
            $end_datetime->add( new DateInterval( 'PT' . $type->duration . 'M' ) );

            $event->setSummary( $type->name . ' - ' . $booking->customer_name );
            $event->setDescription( $this->build_event_description( $booking, $type ) );
            $event->setStart( new Google_Service_Calendar_EventDateTime( array(
                'dateTime' => $start_datetime->format( 'c' ),
                'timeZone' => booknow_get_setting( 'general', 'timezone' ) ?: 'UTC',
            ) ) );
            $event->setEnd( new Google_Service_Calendar_EventDateTime( array(
                'dateTime' => $end_datetime->format( 'c' ),
                'timeZone' => booknow_get_setting( 'general', 'timezone' ) ?: 'UTC',
            ) ) );

            $this->service->events->update( $calendar_id, $event_id, $event );

            return true;

        } catch ( Exception $e ) {
            error_log( 'BookNow: Google Calendar event update failed: ' . $e->getMessage() );
            return new WP_Error( 'event_update_failed', $e->getMessage() );
        }
    }

    /**
     * Delete calendar event.
     *
     * @param string $event_id Google Calendar event ID.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function delete_event( string $event_id ) {
        if ( ! $this->is_authenticated() ) {
            return new WP_Error(
                'not_authenticated',
                __( 'Google Calendar not authenticated.', 'book-now-kre8iv' )
            );
        }

        try {
            $calendar_id = $this->settings['google_calendar_id'] ?? 'primary';
            $this->service->events->delete( $calendar_id, $event_id );

            return true;

        } catch ( Exception $e ) {
            error_log( 'BookNow: Google Calendar event deletion failed: ' . $e->getMessage() );
            return new WP_Error( 'event_deletion_failed', $e->getMessage() );
        }
    }

    /**
     * Build event description from booking data.
     *
     * @param object $booking Booking object.
     * @param object $type    Consultation type object.
     * @return string Event description.
     */
    private function build_event_description( $booking, $type ): string {
        $description  = "Booking Reference: {$booking->reference_number}\n\n";
        $description .= "Customer: {$booking->customer_name}\n";
        $description .= "Email: {$booking->customer_email}\n";

        if ( ! empty( $booking->customer_phone ) ) {
            $description .= "Phone: {$booking->customer_phone}\n";
        }

        if ( ! empty( $booking->customer_notes ) ) {
            $description .= "\nNotes:\n{$booking->customer_notes}";
        }

        return $description;
    }

    /**
     * Test connection to Google Calendar.
     *
     * @return array|WP_Error Connection test results or error.
     */
    public function test_connection() {
        if ( ! $this->is_authenticated() ) {
            return new WP_Error(
                'not_authenticated',
                __( 'Google Calendar not authenticated.', 'book-now-kre8iv' )
            );
        }

        try {
            $calendar_id = $this->settings['google_calendar_id'] ?? 'primary';
            $calendar    = $this->service->calendars->get( $calendar_id );

            return array(
                'success'       => true,
                'calendar_name' => $calendar->getSummary(),
                'calendar_id'   => $calendar->getId(),
            );

        } catch ( Exception $e ) {
            return new WP_Error( 'connection_failed', $e->getMessage() );
        }
    }

    /**
     * List available calendars.
     *
     * @return array|WP_Error Array of calendars or error.
     */
    public function list_calendars() {
        if ( ! $this->is_authenticated() ) {
            return new WP_Error(
                'not_authenticated',
                __( 'Google Calendar not authenticated.', 'book-now-kre8iv' )
            );
        }

        try {
            $calendar_list = $this->service->calendarList->listCalendarList();
            $calendars     = array();

            foreach ( $calendar_list->getItems() as $calendar ) {
                $calendars[] = array(
                    'id'      => $calendar->getId(),
                    'name'    => $calendar->getSummary(),
                    'primary' => $calendar->getPrimary(),
                );
            }

            return $calendars;

        } catch ( Exception $e ) {
            return new WP_Error( 'list_failed', $e->getMessage() );
        }
    }

    /**
     * Check if time slot is available (no conflicting events).
     *
     * @param string $date     Date in Y-m-d format.
     * @param string $time     Time in H:i format.
     * @param int    $duration Duration in minutes.
     * @return bool|WP_Error True if available, false if busy, WP_Error on failure.
     */
    public function is_time_available( string $date, string $time, int $duration ) {
        if ( ! $this->is_authenticated() ) {
            return new WP_Error(
                'not_authenticated',
                __( 'Google Calendar not authenticated.', 'book-now-kre8iv' )
            );
        }

        try {
            $start_datetime = new DateTime( $date . ' ' . $time );
            $end_datetime   = clone $start_datetime;
            $end_datetime->add( new DateInterval( 'PT' . $duration . 'M' ) );

            $calendar_id = $this->settings['google_calendar_id'] ?? 'primary';

            // Query for events in this time range
            $events = $this->service->events->listEvents( $calendar_id, array(
                'timeMin'      => $start_datetime->format( 'c' ),
                'timeMax'      => $end_datetime->format( 'c' ),
                'singleEvents' => true,
                'orderBy'      => 'startTime',
            ) );

            // If any events found, time slot is busy
            return count( $events->getItems() ) === 0;

        } catch ( Exception $e ) {
            return new WP_Error( 'availability_check_failed', $e->getMessage() );
        }
    }

    /**
     * Get busy times for a date range.
     *
     * @param string $date_from Start date (Y-m-d).
     * @param string $date_to   End date (Y-m-d).
     * @return array|WP_Error Array of busy time ranges or error.
     */
    public function get_busy_times( string $date_from, string $date_to ) {
        if ( ! $this->is_authenticated() ) {
            return new WP_Error(
                'not_authenticated',
                __( 'Google Calendar not authenticated.', 'book-now-kre8iv' )
            );
        }

        try {
            $start_datetime = new DateTime( $date_from . ' 00:00:00' );
            $end_datetime   = new DateTime( $date_to . ' 23:59:59' );

            $calendar_id = $this->settings['google_calendar_id'] ?? 'primary';

            $events = $this->service->events->listEvents( $calendar_id, array(
                'timeMin'      => $start_datetime->format( 'c' ),
                'timeMax'      => $end_datetime->format( 'c' ),
                'singleEvents' => true,
                'orderBy'      => 'startTime',
            ) );

            $busy_times = array();

            foreach ( $events->getItems() as $event ) {
                $start = $event->getStart();
                $end   = $event->getEnd();

                // Skip all-day events
                if ( ! $start->getDateTime() || ! $end->getDateTime() ) {
                    continue;
                }

                $busy_times[] = array(
                    'start'   => $start->getDateTime(),
                    'end'     => $end->getDateTime(),
                    'summary' => $event->getSummary(),
                );
            }

            return $busy_times;

        } catch ( Exception $e ) {
            return new WP_Error( 'busy_times_failed', $e->getMessage() );
        }
    }
}
