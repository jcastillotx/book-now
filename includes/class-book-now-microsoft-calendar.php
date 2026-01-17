<?php
/**
 * Microsoft Calendar Integration
 *
 * Provides OAuth 2.0 authentication and calendar synchronization with Microsoft 365/Outlook
 * using Microsoft Graph API v1.0 and Microsoft identity platform v2.0.
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 * @since      1.0.0
 */

class Book_Now_Microsoft_Calendar {

    /**
     * Microsoft Graph API endpoint.
     *
     * @var string
     */
    private string $graph_url = 'https://graph.microsoft.com/v1.0';

    /**
     * Microsoft OAuth authorization endpoint.
     *
     * @var string
     */
    private string $auth_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';

    /**
     * Microsoft OAuth token endpoint.
     *
     * @var string
     */
    private string $token_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

    /**
     * OAuth scopes required for calendar access.
     *
     * @var string
     */
    private string $scopes = 'Calendars.ReadWrite User.Read offline_access';

    /**
     * Settings array.
     *
     * @var array<string, mixed>
     */
    private array $settings;

    /**
     * Access token for API requests.
     *
     * @var string|null
     */
    private ?string $access_token;

    /**
     * Initialize Microsoft Calendar.
     */
    public function __construct() {
        $this->load_settings();
        $this->register_oauth_callback_handler();
    }

    /**
     * Load calendar settings.
     *
     * @return void
     */
    private function load_settings(): void {
        // Load integration settings (credentials) with automatic decryption
        $integration_settings = Book_Now_Encryption::get_integration_settings();

        // Load calendar settings (tokens, etc.)
        $calendar_settings = get_option('booknow_calendar_settings', array());

        // Load Microsoft-specific token settings with decryption
        $microsoft_tokens = get_option('booknow_microsoft_tokens', array());
        if (!empty($microsoft_tokens)) {
            $microsoft_tokens = $this->decrypt_tokens($microsoft_tokens);
        }

        // Merge integration credentials with calendar settings
        $this->settings = array_merge($calendar_settings, $microsoft_tokens, array(
            'microsoft_client_id'     => $integration_settings['microsoft_client_id'] ?? '',
            'microsoft_client_secret' => $integration_settings['microsoft_client_secret'] ?? '',
            'microsoft_tenant_id'     => $integration_settings['microsoft_tenant_id'] ?? '',
        ));

        $this->access_token = $this->settings['microsoft_access_token'] ?? null;
    }

    /**
     * Register OAuth callback handler for admin.
     *
     * @return void
     */
    private function register_oauth_callback_handler(): void {
        add_action('admin_init', array($this, 'process_oauth_callback'));
    }

    /**
     * Process OAuth callback from Microsoft.
     *
     * @return void
     */
    public function process_oauth_callback(): void {
        // Check if this is a Microsoft OAuth callback
        if (!isset($_GET['page']) || $_GET['page'] !== 'book-now-settings') {
            return;
        }
        // Support both 'integration' and 'integrations' tab names for compatibility
        $tab = $_GET['tab'] ?? '';
        if ($tab !== 'integration' && $tab !== 'integrations') {
            return;
        }
        if (!isset($_GET['microsoft_oauth']) || $_GET['microsoft_oauth'] !== 'callback') {
            return;
        }

        // Verify user capability
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check for authorization code
        if (isset($_GET['code'])) {
            $code = sanitize_text_field(wp_unslash($_GET['code']));
            $result = $this->handle_oauth_callback($code);

            if (is_wp_error($result)) {
                // Store error message for display
                set_transient('booknow_microsoft_oauth_error', $result->get_error_message(), 60);
            } else {
                // Store success message
                set_transient('booknow_microsoft_oauth_success', __('Successfully connected to Microsoft Calendar.', 'book-now-kre8iv'), 60);
            }

            // Redirect to remove OAuth parameters from URL
            wp_safe_redirect(admin_url('admin.php?page=book-now-settings&tab=integration'));
            exit;
        }

        // Check for error from Microsoft
        if (isset($_GET['error'])) {
            $error = sanitize_text_field(wp_unslash($_GET['error']));
            $error_description = isset($_GET['error_description'])
                ? sanitize_text_field(wp_unslash($_GET['error_description']))
                : $error;
            set_transient('booknow_microsoft_oauth_error', $error_description, 60);

            wp_safe_redirect(admin_url('admin.php?page=book-now-settings&tab=integrations'));
            exit;
        }
    }

    /**
     * Check if Microsoft Calendar is configured with credentials.
     *
     * @return bool True if credentials are configured.
     */
    public function is_configured(): bool {
        return !empty($this->settings['microsoft_client_id']) &&
               !empty($this->settings['microsoft_client_secret']);
    }

    /**
     * Check if authenticated with valid tokens.
     *
     * @return bool True if authenticated.
     */
    public function is_authenticated(): bool {
        return $this->is_configured() && !empty($this->access_token);
    }

    /**
     * Check if connected with valid, non-expired tokens.
     *
     * @return bool True if connected.
     */
    public function is_connected(): bool {
        if (!$this->is_authenticated()) {
            return false;
        }

        // Check if token is expired
        $expires = $this->settings['microsoft_token_expires'] ?? 0;
        if ($expires > 0 && time() >= $expires) {
            // Try to refresh the token
            if (!$this->refresh_token()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the OAuth authorization URL.
     *
     * @return string|false The authorization URL or false if not configured.
     */
    public function get_auth_url() {
        if (!$this->is_configured()) {
            return false;
        }

        // Generate state parameter for CSRF protection
        $state = wp_create_nonce('booknow_microsoft_oauth');
        set_transient('booknow_microsoft_oauth_state', $state, 600);

        $params = array(
            'client_id'     => $this->settings['microsoft_client_id'],
            'response_type' => 'code',
            'redirect_uri'  => $this->get_redirect_uri(),
            'response_mode' => 'query',
            'scope'         => $this->scopes,
            'state'         => $state,
            'prompt'        => 'consent',
        );

        return $this->auth_url . '?' . http_build_query($params);
    }

    /**
     * Get the OAuth redirect URI.
     *
     * @return string The redirect URI.
     */
    private function get_redirect_uri(): string {
        return admin_url('admin.php?page=book-now-settings&tab=integration&microsoft_oauth=callback');
    }

    /**
     * Handle OAuth callback and exchange authorization code for tokens.
     *
     * @param string $code The authorization code from Microsoft.
     * @return true|\WP_Error True on success, WP_Error on failure.
     */
    public function handle_oauth_callback(string $code) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('Microsoft Calendar not configured.', 'book-now-kre8iv'));
        }

        // Verify state parameter for CSRF protection
        $stored_state = get_transient('booknow_microsoft_oauth_state');
        $received_state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';

        if ($stored_state && $received_state && !wp_verify_nonce($received_state, 'booknow_microsoft_oauth')) {
            delete_transient('booknow_microsoft_oauth_state');
            return new WP_Error('invalid_state', __('Invalid state parameter. Please try again.', 'book-now-kre8iv'));
        }
        delete_transient('booknow_microsoft_oauth_state');

        $response = wp_remote_post($this->token_url, array(
            'timeout' => 30,
            'body'    => array(
                'client_id'     => $this->settings['microsoft_client_id'],
                'client_secret' => $this->settings['microsoft_client_secret'],
                'code'          => $code,
                'redirect_uri'  => $this->get_redirect_uri(),
                'grant_type'    => 'authorization_code',
                'scope'         => $this->scopes,
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            $error_message = $body['error_description'] ?? $body['error'];
            return new WP_Error('oauth_error', $error_message);
        }

        if (empty($body['access_token'])) {
            return new WP_Error('no_token', __('No access token received from Microsoft.', 'book-now-kre8iv'));
        }

        // Fetch user info to get email
        $user_info = $this->fetch_user_info($body['access_token']);

        // Prepare tokens for storage
        $tokens = array(
            'microsoft_access_token'  => $body['access_token'],
            'microsoft_refresh_token' => $body['refresh_token'] ?? '',
            'microsoft_token_expires' => time() + (int) ($body['expires_in'] ?? 3600),
            'microsoft_user_email'    => '',
            'microsoft_user_name'     => '',
        );

        if (!is_wp_error($user_info)) {
            $tokens['microsoft_user_email'] = $user_info['mail'] ?? $user_info['userPrincipalName'] ?? '';
            $tokens['microsoft_user_name'] = $user_info['displayName'] ?? '';
        }

        // Encrypt and save tokens
        $this->save_tokens($tokens);

        // Update instance properties
        $this->access_token = $body['access_token'];
        $this->settings = array_merge($this->settings, $tokens);

        return true;
    }

    /**
     * Fetch user information from Microsoft Graph.
     *
     * @param string $access_token The access token.
     * @return array|\WP_Error User info array or WP_Error.
     */
    private function fetch_user_info(string $access_token) {
        $response = wp_remote_get($this->graph_url . '/me', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code >= 400) {
            return new WP_Error('api_error', $body['error']['message'] ?? 'Failed to fetch user info');
        }

        return $body;
    }

    /**
     * Disconnect from Microsoft Calendar by revoking tokens and clearing credentials.
     *
     * @return true|\WP_Error True on success, WP_Error on failure.
     */
    public function disconnect() {
        if (!current_user_can('manage_options')) {
            return new WP_Error('permission_denied', __('You do not have permission to disconnect.', 'book-now-kre8iv'));
        }

        // Note: Microsoft OAuth does not have a standard revoke endpoint.
        // We clear the stored tokens, which effectively disconnects the app.
        // The user can revoke access through their Microsoft account settings:
        // https://account.live.com/consent/Manage

        // Clear stored tokens
        delete_option('booknow_microsoft_tokens');

        // Clear calendar settings related to Microsoft
        $calendar_settings = get_option('booknow_calendar_settings', array());
        unset($calendar_settings['microsoft_access_token']);
        unset($calendar_settings['microsoft_refresh_token']);
        unset($calendar_settings['microsoft_token_expires']);
        unset($calendar_settings['microsoft_user_email']);
        unset($calendar_settings['microsoft_user_name']);
        update_option('booknow_calendar_settings', $calendar_settings);

        // Reset instance properties
        $this->access_token = null;
        unset($this->settings['microsoft_access_token']);
        unset($this->settings['microsoft_refresh_token']);
        unset($this->settings['microsoft_token_expires']);
        unset($this->settings['microsoft_user_email']);
        unset($this->settings['microsoft_user_name']);

        return true;
    }

    /**
     * Get connection status with details.
     *
     * @return array{connected: bool, email: string, calendar_name: string, expires_at: string, error: string} Connection status details.
     */
    public function get_connection_status(): array {
        $status = array(
            'connected'     => false,
            'email'         => '',
            'calendar_name' => '',
            'expires_at'    => '',
            'error'         => '',
        );

        if (!$this->is_configured()) {
            $status['error'] = __('Microsoft Calendar credentials not configured.', 'book-now-kre8iv');
            return $status;
        }

        if (!$this->is_authenticated()) {
            $status['error'] = __('Not connected to Microsoft Calendar.', 'book-now-kre8iv');
            return $status;
        }

        // Check token expiry and try to refresh if needed
        $expires = $this->settings['microsoft_token_expires'] ?? 0;
        if ($expires > 0 && time() >= $expires) {
            if (!$this->refresh_token()) {
                $status['error'] = __('Token expired and refresh failed.', 'book-now-kre8iv');
                return $status;
            }
        }

        // Verify connection by making an API call
        $result = $this->test_connection();
        if (is_wp_error($result)) {
            $status['error'] = $result->get_error_message();
            return $status;
        }

        $status['connected'] = true;
        $status['email'] = $result['user_email'] ?? $this->settings['microsoft_user_email'] ?? '';
        $status['calendar_name'] = $result['user_name'] ?? $this->settings['microsoft_user_name'] ?? __('Primary Calendar', 'book-now-kre8iv');

        if (!empty($this->settings['microsoft_token_expires'])) {
            $status['expires_at'] = wp_date(
                get_option('date_format') . ' ' . get_option('time_format'),
                $this->settings['microsoft_token_expires']
            );
        }

        return $status;
    }

    /**
     * Encrypt tokens for secure storage.
     *
     * @param array<string, mixed> $tokens The tokens to encrypt.
     * @return array<string, mixed> The encrypted tokens.
     */
    private function encrypt_tokens(array $tokens): array {
        $secret_fields = array('microsoft_access_token', 'microsoft_refresh_token');

        foreach ($secret_fields as $field) {
            if (!empty($tokens[$field])) {
                $tokens[$field] = Book_Now_Encryption::encrypt($tokens[$field]);
            }
        }

        return $tokens;
    }

    /**
     * Decrypt tokens for use.
     *
     * @param array<string, mixed> $tokens The encrypted tokens.
     * @return array<string, mixed> The decrypted tokens.
     */
    private function decrypt_tokens(array $tokens): array {
        $secret_fields = array('microsoft_access_token', 'microsoft_refresh_token');

        foreach ($secret_fields as $field) {
            if (!empty($tokens[$field])) {
                $tokens[$field] = Book_Now_Encryption::decrypt($tokens[$field]);
            }
        }

        return $tokens;
    }

    /**
     * Save tokens with encryption.
     *
     * @param array<string, mixed> $tokens The tokens to save.
     * @return bool True on success.
     */
    private function save_tokens(array $tokens): bool {
        $encrypted_tokens = $this->encrypt_tokens($tokens);
        return update_option('booknow_microsoft_tokens', $encrypted_tokens);
    }

    /**
     * Refresh access token using refresh token.
     *
     * @return bool True on success, false on failure.
     */
    private function refresh_token(): bool {
        if (empty($this->settings['microsoft_refresh_token'])) {
            return false;
        }

        $response = wp_remote_post($this->token_url, array(
            'timeout' => 30,
            'body'    => array(
                'client_id'     => $this->settings['microsoft_client_id'],
                'client_secret' => $this->settings['microsoft_client_secret'],
                'refresh_token' => $this->settings['microsoft_refresh_token'],
                'grant_type'    => 'refresh_token',
                'scope'         => $this->scopes,
            ),
        ));

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('BookNow Microsoft Calendar: Token refresh failed - ' . $response->get_error_message());
            }
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error']) || empty($body['access_token'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('BookNow Microsoft Calendar: Token refresh error - ' . ($body['error_description'] ?? $body['error'] ?? 'Unknown error'));
            }
            return false;
        }

        // Update tokens
        $tokens = array(
            'microsoft_access_token'  => $body['access_token'],
            'microsoft_refresh_token' => $body['refresh_token'] ?? $this->settings['microsoft_refresh_token'],
            'microsoft_token_expires' => time() + (int) ($body['expires_in'] ?? 3600),
            'microsoft_user_email'    => $this->settings['microsoft_user_email'] ?? '',
            'microsoft_user_name'     => $this->settings['microsoft_user_name'] ?? '',
        );

        $this->save_tokens($tokens);
        $this->access_token = $body['access_token'];
        $this->settings = array_merge($this->settings, $tokens);

        return true;
    }

    /**
     * Ensure valid access token before making API requests.
     *
     * @return bool True if token is valid.
     */
    private function ensure_valid_token(): bool {
        if (empty($this->access_token)) {
            return false;
        }

        // Check if token is expired and refresh
        $expires = $this->settings['microsoft_token_expires'] ?? 0;
        if ($expires > 0 && time() >= ($expires - 60)) { // Refresh 60 seconds before expiry
            return $this->refresh_token();
        }

        return true;
    }

    /**
     * Make API request to Microsoft Graph.
     *
     * @param string             $endpoint The API endpoint.
     * @param string             $method   HTTP method (GET, POST, PATCH, DELETE).
     * @param array<mixed>|null  $body     Request body data.
     * @return array<mixed>|\WP_Error Response data or WP_Error.
     */
    private function api_request(string $endpoint, string $method = 'GET', ?array $body = null) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', __('Microsoft Calendar not authenticated.', 'book-now-kre8iv'));
        }

        // Ensure token is valid
        if (!$this->ensure_valid_token()) {
            return new WP_Error('token_expired', __('Access token expired and could not be refreshed.', 'book-now-kre8iv'));
        }

        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type'  => 'application/json',
            ),
        );

        if ($body !== null) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($this->graph_url . $endpoint, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // Handle empty response for DELETE requests
        if ($method === 'DELETE' && $status_code === 204) {
            return array('success' => true);
        }

        $data = json_decode($response_body, true);

        if ($status_code >= 400) {
            $error_message = $data['error']['message'] ?? __('Unknown API error', 'book-now-kre8iv');
            return new WP_Error('api_error', $error_message);
        }

        return $data ?? array('success' => true);
    }

    /**
     * Create calendar event for a booking.
     *
     * @param object $booking Booking object.
     * @return string|\WP_Error Event ID or WP_Error.
     */
    public function create_event(object $booking) {
        $type = Book_Now_Consultation_Type::get($booking->consultation_type_id);

        $start_datetime = new DateTime($booking->booking_date . ' ' . $booking->booking_time);
        $end_datetime = clone $start_datetime;
        $end_datetime->add(new DateInterval('PT' . $type->duration . 'M'));

        $timezone = booknow_get_setting('general', 'timezone') ?: 'UTC';

        $event_data = array(
            'subject' => $type->name . ' - ' . $booking->customer_name,
            'body'    => array(
                'contentType' => 'text',
                'content'     => $this->build_event_description($booking, $type),
            ),
            'start' => array(
                'dateTime' => $start_datetime->format('Y-m-d\TH:i:s'),
                'timeZone' => $timezone,
            ),
            'end' => array(
                'dateTime' => $end_datetime->format('Y-m-d\TH:i:s'),
                'timeZone' => $timezone,
            ),
            'attendees' => array(
                array(
                    'emailAddress' => array(
                        'address' => $booking->customer_email,
                        'name'    => $booking->customer_name,
                    ),
                    'type' => 'required',
                ),
            ),
            'reminderMinutesBeforeStart' => 30,
        );

        $result = $this->api_request('/me/events', 'POST', $event_data);

        if (is_wp_error($result)) {
            return $result;
        }

        return $result['id'] ?? '';
    }

    /**
     * Update calendar event.
     *
     * @param string $event_id Microsoft Calendar event ID.
     * @param object $booking  Booking object.
     * @return bool|\WP_Error True on success, WP_Error on failure.
     */
    public function update_event(string $event_id, object $booking) {
        $type = Book_Now_Consultation_Type::get($booking->consultation_type_id);

        $start_datetime = new DateTime($booking->booking_date . ' ' . $booking->booking_time);
        $end_datetime = clone $start_datetime;
        $end_datetime->add(new DateInterval('PT' . $type->duration . 'M'));

        $timezone = booknow_get_setting('general', 'timezone') ?: 'UTC';

        $event_data = array(
            'subject' => $type->name . ' - ' . $booking->customer_name,
            'body'    => array(
                'contentType' => 'text',
                'content'     => $this->build_event_description($booking, $type),
            ),
            'start' => array(
                'dateTime' => $start_datetime->format('Y-m-d\TH:i:s'),
                'timeZone' => $timezone,
            ),
            'end' => array(
                'dateTime' => $end_datetime->format('Y-m-d\TH:i:s'),
                'timeZone' => $timezone,
            ),
        );

        $result = $this->api_request('/me/events/' . $event_id, 'PATCH', $event_data);

        return !is_wp_error($result);
    }

    /**
     * Delete calendar event.
     *
     * @param string $event_id Microsoft Calendar event ID.
     * @return bool|\WP_Error True on success, WP_Error on failure.
     */
    public function delete_event(string $event_id) {
        $result = $this->api_request('/me/events/' . $event_id, 'DELETE');
        return !is_wp_error($result);
    }

    /**
     * Build event description from booking data.
     *
     * @param object $booking Booking object.
     * @param object $type    Consultation type object.
     * @return string Event description.
     */
    private function build_event_description(object $booking, object $type): string {
        $description = "Booking Reference: {$booking->reference_number}\n\n";
        $description .= "Customer: {$booking->customer_name}\n";
        $description .= "Email: {$booking->customer_email}\n";

        if (!empty($booking->customer_phone)) {
            $description .= "Phone: {$booking->customer_phone}\n";
        }

        if (!empty($booking->customer_notes)) {
            $description .= "\nNotes:\n{$booking->customer_notes}";
        }

        return $description;
    }

    /**
     * Test connection to Microsoft Calendar.
     *
     * @return array{success: bool, user_name: string, user_email: string}|\WP_Error Connection test result.
     */
    public function test_connection() {
        $result = $this->api_request('/me');

        if (is_wp_error($result)) {
            return $result;
        }

        return array(
            'success'    => true,
            'user_name'  => $result['displayName'] ?? '',
            'user_email' => $result['mail'] ?? $result['userPrincipalName'] ?? '',
        );
    }

    /**
     * Check if time slot is available (no conflicting events).
     *
     * @param string $date     Date in Y-m-d format.
     * @param string $time     Time in H:i format.
     * @param int    $duration Duration in minutes.
     * @return bool|\WP_Error True if available, false if busy, WP_Error on failure.
     */
    public function is_time_available(string $date, string $time, int $duration) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', __('Microsoft Calendar not authenticated.', 'book-now-kre8iv'));
        }

        $start_datetime = new DateTime($date . ' ' . $time);
        $end_datetime = clone $start_datetime;
        $end_datetime->add(new DateInterval('PT' . $duration . 'M'));

        // Use Calendar View API to get events in time range
        $start_str = $start_datetime->format('Y-m-d\TH:i:s');
        $end_str = $end_datetime->format('Y-m-d\TH:i:s');

        $endpoint = "/me/calendarview?startDateTime={$start_str}&endDateTime={$end_str}";
        $result = $this->api_request($endpoint);

        if (is_wp_error($result)) {
            return $result;
        }

        // If any events found, time slot is busy
        return empty($result['value']);
    }

    /**
     * Get busy times for a date range.
     *
     * @param string $date_from Start date (Y-m-d).
     * @param string $date_to   End date (Y-m-d).
     * @return array<int, array{start: string, end: string, summary: string}>|\WP_Error Array of busy time ranges.
     */
    public function get_busy_times(string $date_from, string $date_to) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', __('Microsoft Calendar not authenticated.', 'book-now-kre8iv'));
        }

        $start_datetime = new DateTime($date_from . ' 00:00:00');
        $end_datetime = new DateTime($date_to . ' 23:59:59');

        $start_str = $start_datetime->format('Y-m-d\TH:i:s');
        $end_str = $end_datetime->format('Y-m-d\TH:i:s');

        $endpoint = "/me/calendarview?startDateTime={$start_str}&endDateTime={$end_str}";
        $result = $this->api_request($endpoint);

        if (is_wp_error($result)) {
            return $result;
        }

        $busy_times = array();

        if (!empty($result['value'])) {
            foreach ($result['value'] as $event) {
                // Skip all-day events
                if (empty($event['start']['dateTime']) || empty($event['end']['dateTime'])) {
                    continue;
                }

                $busy_times[] = array(
                    'start'   => $event['start']['dateTime'],
                    'end'     => $event['end']['dateTime'],
                    'summary' => $event['subject'] ?? '',
                );
            }
        }

        return $busy_times;
    }

    /**
     * List available calendars.
     *
     * @return array<int, array{id: string, name: string, is_default: bool}>|\WP_Error Array of calendars.
     */
    public function list_calendars() {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', __('Microsoft Calendar not authenticated.', 'book-now-kre8iv'));
        }

        $result = $this->api_request('/me/calendars');

        if (is_wp_error($result)) {
            return $result;
        }

        $calendars = array();

        if (!empty($result['value'])) {
            foreach ($result['value'] as $calendar) {
                $calendars[] = array(
                    'id'         => $calendar['id'] ?? '',
                    'name'       => $calendar['name'] ?? '',
                    'is_default' => $calendar['isDefaultCalendar'] ?? false,
                );
            }
        }

        return $calendars;
    }
}
