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
        $this->google = new Book_Now_Google_Calendar();
        $this->microsoft = new Book_Now_Microsoft_Calendar();

        // Hook into booking actions
        add_action('booknow_booking_created', array($this, 'sync_booking_created'), 10, 1);
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

        // Sync to Google Calendar
        if ($this->is_google_enabled() && $this->google->is_authenticated()) {
            $event_id = $this->google->create_event($booking);
            
            if (!is_wp_error($event_id)) {
                Book_Now_Booking::update($booking_id, array(
                    'google_event_id' => $event_id,
                ));
            }
        }

        // Sync to Microsoft Calendar
        if ($this->is_microsoft_enabled() && $this->microsoft->is_authenticated()) {
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

        // Update Google Calendar event
        if ($this->is_google_enabled() && !empty($booking->google_event_id)) {
            $this->google->update_event($booking->google_event_id, $booking);
        }

        // Update Microsoft Calendar event
        if ($this->is_microsoft_enabled() && !empty($booking->microsoft_event_id)) {
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

        // Delete Google Calendar event
        if ($this->is_google_enabled() && !empty($booking->google_event_id)) {
            $this->google->delete_event($booking->google_event_id);
        }

        // Delete Microsoft Calendar event
        if ($this->is_microsoft_enabled() && !empty($booking->microsoft_event_id)) {
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

        // Check Google Calendar
        if ($this->is_google_enabled() && $this->google->is_authenticated()) {
            $google_available = $this->google->is_time_available($date, $time, $duration);
            
            if (is_wp_error($google_available)) {
                error_log('Google Calendar availability check failed: ' . $google_available->get_error_message());
            } else {
                $available = $available && $google_available;
            }
        }

        // Check Microsoft Calendar
        if ($this->is_microsoft_enabled() && $this->microsoft->is_authenticated()) {
            $microsoft_available = $this->microsoft->is_time_available($date, $time, $duration);
            
            if (is_wp_error($microsoft_available)) {
                error_log('Microsoft Calendar availability check failed: ' . $microsoft_available->get_error_message());
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

        // Get Google Calendar busy times
        if ($this->is_google_enabled() && $this->google->is_authenticated()) {
            $google_busy = $this->google->get_busy_times($date_from, $date_to);
            
            if (!is_wp_error($google_busy)) {
                $all_busy_times = array_merge($all_busy_times, $google_busy);
            }
        }

        // Get Microsoft Calendar busy times
        if ($this->is_microsoft_enabled() && $this->microsoft->is_authenticated()) {
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

        // Sync to Google
        if ($this->is_google_enabled() && $this->google->is_authenticated()) {
            if (empty($booking->google_event_id)) {
                $event_id = $this->google->create_event($booking);
                $results['google'] = !is_wp_error($event_id) ? 'created' : 'error';
            } else {
                $result = $this->google->update_event($booking->google_event_id, $booking);
                $results['google'] = !is_wp_error($result) ? 'updated' : 'error';
            }
        }

        // Sync to Microsoft
        if ($this->is_microsoft_enabled() && $this->microsoft->is_authenticated()) {
            if (empty($booking->microsoft_event_id)) {
                $event_id = $this->microsoft->create_event($booking);
                $results['microsoft'] = !is_wp_error($event_id) ? 'created' : 'error';
            } else {
                $result = $this->microsoft->update_event($booking->microsoft_event_id, $booking);
                $results['microsoft'] = !is_wp_error($result) ? 'updated' : 'error';
            }
        }

        return $results;
    }
}
