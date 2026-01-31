<?php
/**
 * Calendar Sync Manager
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 */

class Book_Now_Calendar_Sync {

    /**
     * Google Calendar instance
     */
    private $google;

    /**
     * Microsoft Calendar instance
     */
    private $microsoft;

    /**
     * Settings
     */
    private $settings;

    /**
     * Initialize calendar sync
     */
    public function __construct() {
        $this->settings = get_option('booknow_calendar_settings', array());

        // Instantiate calendar integrations with error handling
        try {
            if (class_exists('Book_Now_Google_Calendar')) {
                $this->google = new Book_Now_Google_Calendar();
            } else {
                $this->google = null;
            }
        } catch (Exception $e) {
            $this->google = null;
            if (class_exists('Book_Now_Logger')) {
                Book_Now_Logger::warning('Google Calendar initialization failed', array('error' => $e->getMessage()));
            }
        }

        try {
            if (class_exists('Book_Now_Microsoft_Calendar')) {
                $this->microsoft = new Book_Now_Microsoft_Calendar();
            } else {
                $this->microsoft = null;
            }
        } catch (Exception $e) {
            $this->microsoft = null;
            if (class_exists('Book_Now_Logger')) {
                Book_Now_Logger::warning('Microsoft Calendar initialization failed', array('error' => $e->getMessage()));
            }
        }

        // Hook into booking actions
        add_action('booknow_booking_created', array($this, 'sync_booking_created'), 10, 1);
        add_action('booknow_booking_confirmed', array($this, 'sync_booking_confirmed'), 10, 1);
        add_action('booknow_booking_updated', array($this, 'sync_booking_updated'), 10, 1);
        add_action('booknow_booking_cancelled', array($this, 'sync_booking_cancelled'), 10, 1);
    }

    /**
     * Sync booking creation to calendars
     *
     * @param int $booking_id Booking ID
     */
    public function sync_booking_created($booking_id) {
        $booking = Book_Now_Booking::get($booking_id);
        
        if (!$booking || $booking->status !== 'confirmed') {
            return;
        }

        // Sync to Google Calendar (with null check)
        if ($this->is_google_enabled() && $this->google !== null && $this->google->is_authenticated()) {
            $event_id = $this->google->create_event($booking);

            if (!is_wp_error($event_id)) {
                Book_Now_Booking::update($booking_id, array(
                    'google_event_id' => $event_id,
                ));
            }
        }

        // Sync to Microsoft Calendar (with null check)
        if ($this->is_microsoft_enabled() && $this->microsoft !== null && $this->microsoft->is_authenticated()) {
            $event_id = $this->microsoft->create_event($booking);
            
            if (!is_wp_error($event_id)) {
                Book_Now_Booking::update($booking_id, array(
                    'microsoft_event_id' => $event_id,
                ));
            }
        }
    }

    /**
     * Sync booking confirmation to calendars
     *
     * Called when a booking status is changed to 'confirmed' (e.g., after payment or manual confirmation)
     *
     * @param int $booking_id Booking ID
     */
    public function sync_booking_confirmed($booking_id) {
        $booking = Book_Now_Booking::get($booking_id);

        if (!$booking) {
            return;
        }

        // Create calendar events if they don't exist yet
        // Sync to Google Calendar (with null check)
        if ($this->is_google_enabled() && $this->google !== null && $this->google->is_authenticated() && empty($booking->google_event_id)) {
            $event_id = $this->google->create_event($booking);

            if (!is_wp_error($event_id)) {
                Book_Now_Booking::update($booking_id, array(
                    'google_event_id' => $event_id,
                ));
            }
        }

        // Sync to Microsoft Calendar (with null check)
        if ($this->is_microsoft_enabled() && $this->microsoft !== null && $this->microsoft->is_authenticated() && empty($booking->microsoft_event_id)) {
            $event_id = $this->microsoft->create_event($booking);

            if (!is_wp_error($event_id)) {
                Book_Now_Booking::update($booking_id, array(
                    'microsoft_event_id' => $event_id,
                ));
            }
        }
    }

    /**
     * Sync booking update to calendars
     *
     * @param int $booking_id Booking ID
     */
    public function sync_booking_updated($booking_id) {
        $booking = Book_Now_Booking::get($booking_id);
        
        if (!$booking) {
            return;
        }

        // Update Google Calendar event (with null check)
        if ($this->is_google_enabled() && $this->google !== null && !empty($booking->google_event_id)) {
            $this->google->update_event($booking->google_event_id, $booking);
        }

        // Update Microsoft Calendar event (with null check)
        if ($this->is_microsoft_enabled() && $this->microsoft !== null && !empty($booking->microsoft_event_id)) {
            $this->microsoft->update_event($booking->microsoft_event_id, $booking);
        }
    }

    /**
     * Sync booking cancellation to calendars
     *
     * @param int $booking_id Booking ID
     */
    public function sync_booking_cancelled($booking_id) {
        $booking = Book_Now_Booking::get($booking_id);
        
        if (!$booking) {
            return;
        }

        // Delete Google Calendar event (with null check)
        if ($this->is_google_enabled() && $this->google !== null && !empty($booking->google_event_id)) {
            $this->google->delete_event($booking->google_event_id);
        }

        // Delete Microsoft Calendar event (with null check)
        if ($this->is_microsoft_enabled() && $this->microsoft !== null && !empty($booking->microsoft_event_id)) {
            $this->microsoft->delete_event($booking->microsoft_event_id);
        }
    }

    /**
     * Check if Google Calendar sync is enabled
     */
    private function is_google_enabled() {
        return !empty($this->settings['google_sync_enabled']);
    }

    /**
     * Check if Microsoft Calendar sync is enabled
     */
    private function is_microsoft_enabled() {
        return !empty($this->settings['microsoft_sync_enabled']);
    }

    /**
     * Check if time slot is available across all enabled calendars
     *
     * @param string $date Date in Y-m-d format
     * @param string $time Time in H:i format
     * @param int    $duration Duration in minutes
     * @return bool True if available in all calendars, false if busy in any
     */
    public function is_time_available($date, $time, $duration) {
        $available = true;

        // Check Google Calendar (with null check)
        if ($this->is_google_enabled() && $this->google !== null && $this->google->is_authenticated()) {
            $google_available = $this->google->is_time_available($date, $time, $duration);

            if (is_wp_error($google_available)) {
                if (class_exists('Book_Now_Logger')) {
                    Book_Now_Logger::warning('Google Calendar availability check failed', array('error' => $google_available->get_error_message()));
                }
                $available = false; // Assume not available on error
            } else {
                $available = $available && $google_available;
            }
        }

        // If already unavailable, no need to check Microsoft
        if (!$available) {
            return false;
        }

        // Check Microsoft Calendar (with null check)
        if ($this->is_microsoft_enabled() && $this->microsoft !== null && $this->microsoft->is_authenticated()) {
            $microsoft_available = $this->microsoft->is_time_available($date, $time, $duration);

            if (is_wp_error($microsoft_available)) {
                if (class_exists('Book_Now_Logger')) {
                    Book_Now_Logger::warning('Microsoft Calendar availability check failed', array('error' => $microsoft_available->get_error_message()));
                }
                $available = false; // Assume not available on error
            } else {
                $available = $available && $microsoft_available;
            }
        }

        return $available;
    }

    /**
     * Get all busy times from enabled calendars
     *
     * @param string $date_from Start date (Y-m-d)
     * @param string $date_to End date (Y-m-d)
     * @return array Combined busy times from all calendars
     */
    public function get_busy_times($date_from, $date_to) {
        $all_busy_times = array();

        // Get Google Calendar busy times (with null check)
        if ($this->is_google_enabled() && $this->google !== null && $this->google->is_authenticated()) {
            $google_busy = $this->google->get_busy_times($date_from, $date_to);

            if (!is_wp_error($google_busy)) {
                $all_busy_times = array_merge($all_busy_times, $google_busy);
            }
        }

        // Get Microsoft Calendar busy times (with null check)
        if ($this->is_microsoft_enabled() && $this->microsoft !== null && $this->microsoft->is_authenticated()) {
            $microsoft_busy = $this->microsoft->get_busy_times($date_from, $date_to);

            if (!is_wp_error($microsoft_busy)) {
                $all_busy_times = array_merge($all_busy_times, $microsoft_busy);
            }
        }

        // Sort by start time
        usort($all_busy_times, function($a, $b) {
            return strcmp($a['start'], $b['start']);
        });

        return $all_busy_times;
    }

    /**
     * Sync all confirmed bookings that are missing calendar events.
     *
     * Useful for catching up after initial calendar connection or fixing missed syncs.
     *
     * @param int $limit Maximum number of bookings to sync (default 50)
     * @return array Results with counts of synced/failed bookings
     */
    public function sync_all_pending($limit = 50) {
        $results = array(
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => array(),
        );

        // Get confirmed bookings
        $bookings = Book_Now_Booking::get_all(array(
            'status' => 'confirmed',
            'limit' => $limit,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ));

        foreach ($bookings as $booking) {
            $results['total']++;
            $needs_sync = false;

            // Check if needs Google sync
            if ($this->is_google_enabled() && $this->google !== null && $this->google->is_authenticated() && empty($booking->google_event_id)) {
                $needs_sync = true;
            }

            // Check if needs Microsoft sync
            if ($this->is_microsoft_enabled() && $this->microsoft !== null && $this->microsoft->is_authenticated() && empty($booking->microsoft_event_id)) {
                $needs_sync = true;
            }

            if (!$needs_sync) {
                $results['skipped']++;
                continue;
            }

            // Perform sync
            $sync_result = $this->manual_sync($booking->id);

            if (isset($sync_result['error'])) {
                $results['failed']++;
                $results['details'][$booking->id] = array(
                    'status' => 'error',
                    'message' => $sync_result['error'],
                );
            } else {
                $has_error = false;
                foreach ($sync_result as $provider => $status) {
                    if ($status === 'error') {
                        $has_error = true;
                    }
                }

                if ($has_error) {
                    $results['failed']++;
                } else {
                    $results['synced']++;
                }
                $results['details'][$booking->id] = $sync_result;
            }
        }

        return $results;
    }

    /**
     * Manual sync booking to calendars
     *
     * @param int $booking_id Booking ID
     * @return array Results
     */
    public function manual_sync($booking_id) {
        $booking = Book_Now_Booking::get($booking_id);
        $results = array();

        if (!$booking) {
            return array('error' => 'Booking not found');
        }

        // Sync to Google (with null check)
        if ($this->is_google_enabled() && $this->google !== null && $this->google->is_authenticated()) {
            if (empty($booking->google_event_id)) {
                $event_id = $this->google->create_event($booking);
                if (!is_wp_error($event_id)) {
                    // Save the event ID to the booking record
                    Book_Now_Booking::update($booking_id, array(
                        'google_event_id' => $event_id,
                    ));
                    $results['google'] = 'created';
                } else {
                    $results['google'] = 'error';
                }
            } else {
                $result = $this->google->update_event($booking->google_event_id, $booking);
                $results['google'] = !is_wp_error($result) ? 'updated' : 'error';
            }
        }

        // Sync to Microsoft (with null check)
        if ($this->is_microsoft_enabled() && $this->microsoft !== null && $this->microsoft->is_authenticated()) {
            if (empty($booking->microsoft_event_id)) {
                $event_id = $this->microsoft->create_event($booking);
                if (!is_wp_error($event_id)) {
                    // Save the event ID to the booking record
                    Book_Now_Booking::update($booking_id, array(
                        'microsoft_event_id' => $event_id,
                    ));
                    $results['microsoft'] = 'created';
                } else {
                    $results['microsoft'] = 'error';
                }
            } else {
                $result = $this->microsoft->update_event($booking->microsoft_event_id, $booking);
                $results['microsoft'] = !is_wp_error($result) ? 'updated' : 'error';
            }
        }

        return $results;
    }
}
