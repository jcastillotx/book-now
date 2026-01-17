<?php
/**
 * Microsoft Calendar Integration
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 */

class Book_Now_Microsoft_Calendar {

    /**
     * Microsoft Graph API endpoint
     */
    private $graph_url = 'https://graph.microsoft.com/v1.0';

    /**
     * Settings
     */
    private $settings;

    /**
     * Access token
     */
    private $access_token;

    /**
     * Initialize Microsoft Calendar
     */
    public function __construct() {
        $this->load_settings();
    }

    /**
     * Load calendar settings
     */
    private function load_settings() {
        // Load integration settings (credentials) with automatic decryption
        $integration_settings = Book_Now_Encryption::get_integration_settings();

        // Load calendar settings (tokens, etc.)
        $calendar_settings = get_option('booknow_calendar_settings', array());

        // Merge integration credentials with calendar settings
        // Integration settings take precedence for credentials
        $this->settings = array_merge($calendar_settings, array(
            'microsoft_client_id'     => $integration_settings['microsoft_client_id'] ?? '',
            'microsoft_client_secret' => $integration_settings['microsoft_client_secret'] ?? '',
            'microsoft_tenant_id'     => $integration_settings['microsoft_tenant_id'] ?? '',
        ));

        $this->access_token = $this->settings['microsoft_access_token'] ?? null;
    }

    /**
     * Check if Microsoft Calendar is configured
     */
    public function is_configured() {
        return !empty($this->settings['microsoft_client_id']) && 
               !empty($this->settings['microsoft_client_secret']) &&
               !empty($this->settings['microsoft_tenant_id']);
    }

    /**
     * Check if authenticated
     */
    public function is_authenticated() {
        return $this->is_configured() && !empty($this->access_token);
    }

    /**
     * Get authorization URL
     */
    public function get_auth_url() {
        if (!$this->is_configured()) {
            return false;
        }

        $params = array(
            'client_id' => $this->settings['microsoft_client_id'],
            'response_type' => 'code',
            'redirect_uri' => admin_url('admin.php?page=book-now-settings&tab=calendar'),
            'response_mode' => 'query',
            'scope' => 'offline_access Calendars.ReadWrite',
        );

        $tenant_id = $this->settings['microsoft_tenant_id'];
        return "https://login.microsoftonline.com/{$tenant_id}/oauth2/v2.0/authorize?" . http_build_query($params);
    }

    /**
     * Handle OAuth callback
     */
    public function handle_oauth_callback($code) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('Microsoft Calendar not configured.', 'book-now-kre8iv'));
        }

        $tenant_id = $this->settings['microsoft_tenant_id'];
        $token_url = "https://login.microsoftonline.com/{$tenant_id}/oauth2/v2.0/token";

        $response = wp_remote_post($token_url, array(
            'body' => array(
                'client_id' => $this->settings['microsoft_client_id'],
                'client_secret' => $this->settings['microsoft_client_secret'],
                'code' => $code,
                'redirect_uri' => admin_url('admin.php?page=book-now-settings&tab=calendar'),
                'grant_type' => 'authorization_code',
                'scope' => 'offline_access Calendars.ReadWrite',
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('oauth_error', $body['error_description']);
        }

        // Save tokens
        $this->settings['microsoft_access_token'] = $body['access_token'];
        $this->settings['microsoft_refresh_token'] = $body['refresh_token'];
        $this->settings['microsoft_token_expires'] = time() + $body['expires_in'];
        update_option('booknow_calendar_settings', $this->settings);

        $this->access_token = $body['access_token'];

        return true;
    }

    /**
     * Refresh access token
     */
    private function refresh_token() {
        if (empty($this->settings['microsoft_refresh_token'])) {
            return false;
        }

        $tenant_id = $this->settings['microsoft_tenant_id'];
        $token_url = "https://login.microsoftonline.com/{$tenant_id}/oauth2/v2.0/token";

        $response = wp_remote_post($token_url, array(
            'body' => array(
                'client_id' => $this->settings['microsoft_client_id'],
                'client_secret' => $this->settings['microsoft_client_secret'],
                'refresh_token' => $this->settings['microsoft_refresh_token'],
                'grant_type' => 'refresh_token',
                'scope' => 'offline_access Calendars.ReadWrite',
            ),
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['access_token'])) {
            $this->settings['microsoft_access_token'] = $body['access_token'];
            $this->settings['microsoft_token_expires'] = time() + $body['expires_in'];
            update_option('booknow_calendar_settings', $this->settings);
            $this->access_token = $body['access_token'];
            return true;
        }

        return false;
    }

    /**
     * Make API request
     */
    private function api_request($endpoint, $method = 'GET', $body = null) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', __('Microsoft Calendar not authenticated.', 'book-now-kre8iv'));
        }

        // Check if token expired and refresh
        if (!empty($this->settings['microsoft_token_expires']) && 
            time() >= $this->settings['microsoft_token_expires']) {
            $this->refresh_token();
        }

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
            ),
        );

        if ($body) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($this->graph_url . $endpoint, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code >= 400) {
            return new WP_Error('api_error', $body['error']['message'] ?? 'Unknown error');
        }

        return $body;
    }

    /**
     * Create calendar event
     *
     * @param object $booking Booking object
     * @return string|WP_Error Event ID or error
     */
    public function create_event($booking) {
        $type = Book_Now_Consultation_Type::get($booking->consultation_type_id);
        
        $start_datetime = new DateTime($booking->booking_date . ' ' . $booking->booking_time);
        $end_datetime = clone $start_datetime;
        $end_datetime->add(new DateInterval('PT' . $type->duration . 'M'));

        $timezone = booknow_get_setting('general', 'timezone') ?: 'UTC';

        $event_data = array(
            'subject' => $type->name . ' - ' . $booking->customer_name,
            'body' => array(
                'contentType' => 'text',
                'content' => $this->build_event_description($booking, $type),
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
                        'name' => $booking->customer_name,
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

        return $result['id'];
    }

    /**
     * Update calendar event
     *
     * @param string $event_id Microsoft Calendar event ID
     * @param object $booking Booking object
     * @return bool|WP_Error
     */
    public function update_event($event_id, $booking) {
        $type = Book_Now_Consultation_Type::get($booking->consultation_type_id);
        
        $start_datetime = new DateTime($booking->booking_date . ' ' . $booking->booking_time);
        $end_datetime = clone $start_datetime;
        $end_datetime->add(new DateInterval('PT' . $type->duration . 'M'));

        $timezone = booknow_get_setting('general', 'timezone') ?: 'UTC';

        $event_data = array(
            'subject' => $type->name . ' - ' . $booking->customer_name,
            'body' => array(
                'contentType' => 'text',
                'content' => $this->build_event_description($booking, $type),
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
     * Delete calendar event
     *
     * @param string $event_id Microsoft Calendar event ID
     * @return bool|WP_Error
     */
    public function delete_event($event_id) {
        $result = $this->api_request('/me/events/' . $event_id, 'DELETE');
        return !is_wp_error($result);
    }

    /**
     * Build event description
     */
    private function build_event_description($booking, $type) {
        $description = "Booking Reference: {$booking->reference_number}\n\n";
        $description .= "Customer: {$booking->customer_name}\n";
        $description .= "Email: {$booking->customer_email}\n";
        
        if ($booking->customer_phone) {
            $description .= "Phone: {$booking->customer_phone}\n";
        }
        
        if ($booking->customer_notes) {
            $description .= "\nNotes:\n{$booking->customer_notes}";
        }

        return $description;
    }

    /**
     * Test connection
     */
    public function test_connection() {
        $result = $this->api_request('/me');

        if (is_wp_error($result)) {
            return $result;
        }

        return array(
            'success' => true,
            'user_name' => $result['displayName'],
            'user_email' => $result['mail'] ?? $result['userPrincipalName'],
        );
    }

    /**
     * Check if time slot is available (no conflicting events)
     *
     * @param string $date Date in Y-m-d format
     * @param string $time Time in H:i format
     * @param int    $duration Duration in minutes
     * @return bool|WP_Error True if available, false if busy, WP_Error on failure
     */
    public function is_time_available($date, $time, $duration) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', __('Microsoft Calendar not authenticated.', 'book-now-kre8iv'));
        }

        $start_datetime = new DateTime($date . ' ' . $time);
        $end_datetime = clone $start_datetime;
        $end_datetime->add(new DateInterval('PT' . $duration . 'M'));

        $timezone = booknow_get_setting('general', 'timezone') ?: 'UTC';

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
     * Get busy times for a date range
     *
     * @param string $date_from Start date (Y-m-d)
     * @param string $date_to End date (Y-m-d)
     * @return array|WP_Error Array of busy time ranges
     */
    public function get_busy_times($date_from, $date_to) {
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
                    'start' => $event['start']['dateTime'],
                    'end' => $event['end']['dateTime'],
                    'summary' => $event['subject'],
                );
            }
        }

        return $busy_times;
    }
}
