<?php
/**
 * Google Calendar Integration
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 */

class Book_Now_Google_Calendar {

    /**
     * Google Client instance
     */
    private $client;

    /**
     * Calendar service
     */
    private $service;

    /**
     * Settings
     */
    private $settings;

    /**
     * Initialize Google Calendar
     */
    public function __construct() {
        $this->load_settings();
        $this->init_client();
    }

    /**
     * Load calendar settings
     */
    private function load_settings() {
        // Load integration settings (credentials) with automatic decryption
        $integration_settings = Book_Now_Encryption::get_integration_settings();

        // Load calendar settings (tokens, calendar_id, etc.)
        $calendar_settings = get_option('booknow_calendar_settings', array());

        // Merge integration credentials with calendar settings
        // Integration settings take precedence for credentials
        $this->settings = array_merge($calendar_settings, array(
            'google_client_id'     => $integration_settings['google_client_id'] ?? '',
            'google_client_secret' => $integration_settings['google_client_secret'] ?? '',
            'google_calendar_id'   => $integration_settings['google_calendar_id'] ?? ($calendar_settings['google_calendar_id'] ?? 'primary'),
        ));
    }

    /**
     * Initialize Google Client
     */
    private function init_client() {
        if (!$this->is_configured()) {
            return;
        }

        if (!class_exists('Google_Client')) {
            require_once BOOK_NOW_PLUGIN_DIR . 'vendor/autoload.php';
        }

        try {
            $this->client = new Google_Client();
            $this->client->setApplicationName('Book Now Plugin');
            $this->client->setScopes(Google_Service_Calendar::CALENDAR);
            $this->client->setAuthConfig(array(
                'client_id' => $this->settings['google_client_id'],
                'client_secret' => $this->settings['google_client_secret'],
                'redirect_uris' => array(admin_url('admin.php?page=book-now-settings&tab=calendar')),
            ));
            $this->client->setAccessType('offline');
            $this->client->setPrompt('consent');

            // Set access token if available
            if (!empty($this->settings['google_access_token'])) {
                $this->client->setAccessToken($this->settings['google_access_token']);

                // Refresh token if expired
                if ($this->client->isAccessTokenExpired()) {
                    $this->refresh_token();
                }
            }

            $this->service = new Google_Service_Calendar($this->client);
        } catch (Exception $e) {
            error_log('Google Calendar initialization error: ' . $e->getMessage());
        }
    }

    /**
     * Check if Google Calendar is configured
     */
    public function is_configured() {
        return !empty($this->settings['google_client_id']) && 
               !empty($this->settings['google_client_secret']);
    }

    /**
     * Check if authenticated
     */
    public function is_authenticated() {
        return $this->is_configured() && 
               !empty($this->settings['google_access_token']) &&
               $this->client &&
               !$this->client->isAccessTokenExpired();
    }

    /**
     * Get authorization URL
     */
    public function get_auth_url() {
        if (!$this->client) {
            return false;
        }

        return $this->client->createAuthUrl();
    }

    /**
     * Handle OAuth callback
     */
    public function handle_oauth_callback($code) {
        if (!$this->client) {
            return new WP_Error('client_not_initialized', __('Google Client not initialized.', 'book-now-kre8iv'));
        }

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                return new WP_Error('oauth_error', $token['error_description']);
            }

            // Save token
            $this->settings['google_access_token'] = $token;
            update_option('booknow_calendar_settings', $this->settings);

            return true;
        } catch (Exception $e) {
            return new WP_Error('oauth_exception', $e->getMessage());
        }
    }

    /**
     * Refresh access token
     */
    private function refresh_token() {
        try {
            if ($this->client->getRefreshToken()) {
                $token = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                
                if (!isset($token['error'])) {
                    $this->settings['google_access_token'] = $token;
                    update_option('booknow_calendar_settings', $this->settings);
                }
            }
        } catch (Exception $e) {
            error_log('Token refresh error: ' . $e->getMessage());
        }
    }

    /**
     * Create calendar event
     *
     * @param object $booking Booking object
     * @return string|WP_Error Event ID or error
     */
    public function create_event($booking) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', __('Google Calendar not authenticated.', 'book-now-kre8iv'));
        }

        try {
            $type = Book_Now_Consultation_Type::get($booking->consultation_type_id);
            
            // Parse booking date and time
            $start_datetime = new DateTime($booking->booking_date . ' ' . $booking->booking_time);
            $end_datetime = clone $start_datetime;
            $end_datetime->add(new DateInterval('PT' . $type->duration . 'M'));

            // Create event
            $event = new Google_Service_Calendar_Event(array(
                'summary' => $type->name . ' - ' . $booking->customer_name,
                'description' => $this->build_event_description($booking, $type),
                'start' => array(
                    'dateTime' => $start_datetime->format('c'),
                    'timeZone' => booknow_get_setting('general', 'timezone') ?: 'UTC',
                ),
                'end' => array(
                    'dateTime' => $end_datetime->format('c'),
                    'timeZone' => booknow_get_setting('general', 'timezone') ?: 'UTC',
                ),
                'attendees' => array(
                    array('email' => $booking->customer_email),
                ),
                'reminders' => array(
                    'useDefault' => false,
                    'overrides' => array(
                        array('method' => 'email', 'minutes' => 24 * 60),
                        array('method' => 'popup', 'minutes' => 30),
                    ),
                ),
            ));

            $calendar_id = $this->settings['google_calendar_id'] ?? 'primary';
            $created_event = $this->service->events->insert($calendar_id, $event);

            return $created_event->getId();
        } catch (Exception $e) {
            return new WP_Error('event_creation_failed', $e->getMessage());
        }
    }

    /**
     * Update calendar event
     *
     * @param string $event_id Google Calendar event ID
     * @param object $booking Booking object
     * @return bool|WP_Error
     */
    public function update_event($event_id, $booking) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', __('Google Calendar not authenticated.', 'book-now-kre8iv'));
        }

        try {
            $calendar_id = $this->settings['google_calendar_id'] ?? 'primary';
            $event = $this->service->events->get($calendar_id, $event_id);

            $type = Book_Now_Consultation_Type::get($booking->consultation_type_id);
            
            $start_datetime = new DateTime($booking->booking_date . ' ' . $booking->booking_time);
            $end_datetime = clone $start_datetime;
            $end_datetime->add(new DateInterval('PT' . $type->duration . 'M'));

            $event->setSummary($type->name . ' - ' . $booking->customer_name);
            $event->setDescription($this->build_event_description($booking, $type));
            $event->setStart(new Google_Service_Calendar_EventDateTime(array(
                'dateTime' => $start_datetime->format('c'),
                'timeZone' => booknow_get_setting('general', 'timezone') ?: 'UTC',
            )));
            $event->setEnd(new Google_Service_Calendar_EventDateTime(array(
                'dateTime' => $end_datetime->format('c'),
                'timeZone' => booknow_get_setting('general', 'timezone') ?: 'UTC',
            )));

            $this->service->events->update($calendar_id, $event_id, $event);

            return true;
        } catch (Exception $e) {
            return new WP_Error('event_update_failed', $e->getMessage());
        }
    }

    /**
     * Delete calendar event
     *
     * @param string $event_id Google Calendar event ID
     * @return bool|WP_Error
     */
    public function delete_event($event_id) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', __('Google Calendar not authenticated.', 'book-now-kre8iv'));
        }

        try {
            $calendar_id = $this->settings['google_calendar_id'] ?? 'primary';
            $this->service->events->delete($calendar_id, $event_id);

            return true;
        } catch (Exception $e) {
            return new WP_Error('event_deletion_failed', $e->getMessage());
        }
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
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', __('Google Calendar not authenticated.', 'book-now-kre8iv'));
        }

        try {
            $calendar_id = $this->settings['google_calendar_id'] ?? 'primary';
            $calendar = $this->service->calendars->get($calendar_id);

            return array(
                'success' => true,
                'calendar_name' => $calendar->getSummary(),
                'calendar_id' => $calendar->getId(),
            );
        } catch (Exception $e) {
            return new WP_Error('connection_failed', $e->getMessage());
        }
    }

    /**
     * List calendars
     */
    public function list_calendars() {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', __('Google Calendar not authenticated.', 'book-now-kre8iv'));
        }

        try {
            $calendar_list = $this->service->calendarList->listCalendarList();
            $calendars = array();

            foreach ($calendar_list->getItems() as $calendar) {
                $calendars[] = array(
                    'id' => $calendar->getId(),
                    'name' => $calendar->getSummary(),
                    'primary' => $calendar->getPrimary(),
                );
            }

            return $calendars;
        } catch (Exception $e) {
            return new WP_Error('list_failed', $e->getMessage());
        }
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
            return new WP_Error('not_authenticated', __('Google Calendar not authenticated.', 'book-now-kre8iv'));
        }

        try {
            $start_datetime = new DateTime($date . ' ' . $time);
            $end_datetime = clone $start_datetime;
            $end_datetime->add(new DateInterval('PT' . $duration . 'M'));

            $calendar_id = $this->settings['google_calendar_id'] ?? 'primary';

            // Query for events in this time range
            $events = $this->service->events->listEvents($calendar_id, array(
                'timeMin' => $start_datetime->format('c'),
                'timeMax' => $end_datetime->format('c'),
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ));

            // If any events found, time slot is busy
            return count($events->getItems()) === 0;
        } catch (Exception $e) {
            return new WP_Error('availability_check_failed', $e->getMessage());
        }
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
            return new WP_Error('not_authenticated', __('Google Calendar not authenticated.', 'book-now-kre8iv'));
        }

        try {
            $start_datetime = new DateTime($date_from . ' 00:00:00');
            $end_datetime = new DateTime($date_to . ' 23:59:59');

            $calendar_id = $this->settings['google_calendar_id'] ?? 'primary';

            $events = $this->service->events->listEvents($calendar_id, array(
                'timeMin' => $start_datetime->format('c'),
                'timeMax' => $end_datetime->format('c'),
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ));

            $busy_times = array();

            foreach ($events->getItems() as $event) {
                $start = $event->getStart();
                $end = $event->getEnd();

                // Skip all-day events
                if (!$start->getDateTime() || !$end->getDateTime()) {
                    continue;
                }

                $busy_times[] = array(
                    'start' => $start->getDateTime(),
                    'end' => $end->getDateTime(),
                    'summary' => $event->getSummary(),
                );
            }

            return $busy_times;
        } catch (Exception $e) {
            return new WP_Error('busy_times_failed', $e->getMessage());
        }
    }
}
