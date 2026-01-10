<?php
/**
 * Public-facing AJAX functionality.
 *
 * @package    BookNow
 * @subpackage BookNow/public
 */

class Book_Now_Public_AJAX {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // AJAX handlers for logged in and non-logged in users
        add_action('wp_ajax_booknow_get_available_dates', array($this, 'get_available_dates'));
        add_action('wp_ajax_nopriv_booknow_get_available_dates', array($this, 'get_available_dates'));

        add_action('wp_ajax_booknow_get_time_slots', array($this, 'get_time_slots'));
        add_action('wp_ajax_nopriv_booknow_get_time_slots', array($this, 'get_time_slots'));

        add_action('wp_ajax_booknow_create_booking', array($this, 'create_booking'));
        add_action('wp_ajax_nopriv_booknow_create_booking', array($this, 'create_booking'));

        add_action('wp_ajax_booknow_get_booking_details', array($this, 'get_booking_details'));
        add_action('wp_ajax_nopriv_booknow_get_booking_details', array($this, 'get_booking_details'));

        add_action('wp_ajax_booknow_cancel_booking', array($this, 'cancel_booking'));
        add_action('wp_ajax_nopriv_booknow_cancel_booking', array($this, 'cancel_booking'));
    }

    /**
     * Get available dates for a consultation type.
     */
    public function get_available_dates() {
        check_ajax_referer('booknow_public_nonce', 'nonce');

        $consultation_type_id = isset($_POST['consultation_type_id']) ? absint($_POST['consultation_type_id']) : 0;
        $month = isset($_POST['month']) ? sanitize_text_field($_POST['month']) : date('Y-m');

        if (!$consultation_type_id) {
            wp_send_json_error(array('message' => __('Invalid consultation type.', 'book-now-kre8iv')));
        }

        // Get consultation type
        $type = Book_Now_Consultation_Type::get($consultation_type_id);
        if (!$type) {
            wp_send_json_error(array('message' => __('Consultation type not found.', 'book-now-kre8iv')));
        }

        // Get available dates for the month
        $available_dates = $this->calculate_available_dates($consultation_type_id, $month);

        wp_send_json_success(array(
            'dates' => $available_dates,
            'month' => $month,
        ));
    }

    /**
     * Calculate available dates for a consultation type in a given month.
     *
     * @param int    $consultation_type_id Consultation type ID.
     * @param string $month                Month in Y-m format.
     * @return array Available dates.
     */
    private function calculate_available_dates($consultation_type_id, $month) {
        $available_dates = array();
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        $current_date = $start_date;
        while ($current_date <= $end_date) {
            if (booknow_is_date_bookable($current_date)) {
                $day_of_week = date('w', strtotime($current_date));
                $availability = Book_Now_Availability::get_for_date($current_date, $day_of_week);

                if (!empty($availability)) {
                    $has_slots = false;
                    foreach ($availability as $rule) {
                        if ($rule->is_available == 1) {
                            $has_slots = true;
                            break;
                        }
                    }

                    if ($has_slots) {
                        $available_dates[] = $current_date;
                    }
                }
            }

            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }

        return $available_dates;
    }

    /**
     * Get available time slots for a date.
     */
    public function get_time_slots() {
        check_ajax_referer('booknow_public_nonce', 'nonce');

        $consultation_type_id = isset($_POST['consultation_type_id']) ? absint($_POST['consultation_type_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';

        if (!$consultation_type_id || !$date) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'book-now-kre8iv')));
        }

        // Validate date is not in the past
        if (!booknow_is_date_bookable($date)) {
            wp_send_json_error(array('message' => __('This date is not available for booking.', 'book-now-kre8iv')));
        }

        $type = Book_Now_Consultation_Type::get($consultation_type_id);

        if (!$type) {
            wp_send_json_error(array('message' => __('Consultation type not found.', 'book-now-kre8iv')));
        }

        // Get available time slots
        $slots = $this->calculate_available_slots($type, $date);

        // Filter out slots that conflict with calendar events
        $calendar_sync = new Book_Now_Calendar_Sync();
        $filtered_slots = array();

        foreach ($slots as $slot) {
            $is_available = $calendar_sync->is_time_available($date, $slot['time'], $type->duration);
            
            if ($is_available) {
                $filtered_slots[] = $slot;
            }
        }

        wp_send_json_success(array(
            'slots' => $slots,
            'date' => $date,
        ));
    }

    /**
     * Calculate available time slots for a consultation type on a specific date.
     *
     * @param int    $consultation_type_id Consultation type ID.
     * @param string $date                 Date in Y-m-d format.
     * @return array Available time slots.
     */
    private function calculate_time_slots($consultation_type_id, $date) {
        global $wpdb;

        // Get consultation type
        $type = Book_Now_Consultation_Type::get($consultation_type_id);
        if (!$type) {
            return array();
        }

        // Get day of week
        $day_of_week = date('w', strtotime($date));

        // Get availability rules
        $availability = Book_Now_Availability::get_for_date($date, $day_of_week);

        if (empty($availability)) {
            return array();
        }

        $slots = array();
        $slot_interval = absint(booknow_get_setting('general', 'slot_interval')) ?: 30;
        $duration = absint($type->duration);
        $buffer_before = absint($type->buffer_before);
        $buffer_after = absint($type->buffer_after);

        foreach ($availability as $rule) {
            if ($rule->is_available != 1) {
                continue;
            }

            $start_minutes = booknow_time_to_minutes($rule->start_time);
            $end_minutes = booknow_time_to_minutes($rule->end_time);

            // Generate time slots
            for ($time = $start_minutes; $time + $duration <= $end_minutes; $time += $slot_interval) {
                $slot_time = booknow_minutes_to_time($time);
                $slot_end_time = booknow_minutes_to_time($time + $duration);

                // Check if slot is available
                if ($this->is_slot_available($date, $slot_time, $duration, $buffer_before, $buffer_after)) {
                    $slots[] = array(
                        'time'      => $slot_time,
                        'end_time'  => $slot_end_time,
                        'formatted' => booknow_format_time($slot_time) . ' - ' . booknow_format_time($slot_end_time),
                        'available' => true,
                    );
                }
            }
        }

        return $slots;
    }

    /**
     * Check if a time slot is available.
     *
     * @param string $date          Date in Y-m-d format.
     * @param string $time          Time in H:i format.
     * @param int    $duration      Duration in minutes.
     * @param int    $buffer_before Buffer before in minutes.
     * @param int    $buffer_after  Buffer after in minutes.
     * @return bool True if available.
     */
    private function is_slot_available($date, $time, $duration, $buffer_before, $buffer_after) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        $slot_start = booknow_time_to_minutes($time) - $buffer_before;
        $slot_end = booknow_time_to_minutes($time) + $duration + $buffer_after;

        // Check for conflicting bookings
        $conflicts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
            WHERE booking_date = %s
            AND status NOT IN ('cancelled', 'no-show')
            AND (
                (TIME_TO_SEC(booking_time) / 60 >= %d AND TIME_TO_SEC(booking_time) / 60 < %d)
                OR (TIME_TO_SEC(booking_time) / 60 + duration < %d AND TIME_TO_SEC(booking_time) / 60 + duration > %d)
                OR (TIME_TO_SEC(booking_time) / 60 <= %d AND TIME_TO_SEC(booking_time) / 60 + duration >= %d)
            )",
            $date,
            $slot_start,
            $slot_end,
            $slot_end,
            $slot_start,
            $slot_start,
            $slot_end
        ));

        return $conflicts == 0;
    }

    /**
     * Create a new booking.
     */
    public function create_booking() {
        check_ajax_referer('booknow_public_nonce', 'nonce');

        // Validate required fields
        $consultation_type_id = isset($_POST['consultation_type_id']) ? absint($_POST['consultation_type_id']) : 0;
        $date = isset($_POST['booking_date']) ? sanitize_text_field($_POST['booking_date']) : '';
        $time = isset($_POST['booking_time']) ? sanitize_text_field($_POST['booking_time']) : '';
        $name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
        $email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
        $phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        // Validation
        if (!$consultation_type_id || !$date || !$time || !$name || !$email) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'book-now-kre8iv')));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'book-now-kre8iv')));
        }

        // Get consultation type
        $type = Book_Now_Consultation_Type::get($consultation_type_id);
        if (!$type) {
            wp_send_json_error(array('message' => __('Invalid consultation type.', 'book-now-kre8iv')));
        }

                $deposit_amount = ($type->price * $type->deposit_amount) / 100;
            } else {
                $deposit_amount = $type->deposit_amount;
            }
        }

        // Create booking
        $booking_data = array(
            'consultation_type_id' => $consultation_type_id,
            'booking_date'         => $date,
            'booking_time'         => $time,
            'duration'             => $type->duration,
            'customer_name'        => $name,
            'customer_email'       => $email,
            'customer_phone'       => $phone,
            'notes'                => $notes,
            'price'                => $type->price,
            'deposit_amount'       => $deposit_amount,
            'status'               => 'pending',
            'payment_status'       => 'pending',
        );

        $booking_id = Book_Now_Booking::create($booking_data);

        if (is_wp_error($booking_id)) {
            wp_send_json_error(array('message' => __('Failed to create booking. Please try again.', 'book-now-kre8iv')));
        }

        // Get created booking
        $booking = Book_Now_Booking::get($booking_id);

        // Check if payment is required
        $needs_payment = $type->require_deposit || booknow_get_setting('payment', 'payment_required');
        $payment_intent = null;

        if ($needs_payment && $deposit_amount > 0) {
            $stripe = new Book_Now_Stripe();
            
            if ($stripe->is_configured()) {
                $intent_result = $stripe->create_payment_intent(
                    $deposit_amount,
                    booknow_get_setting('general', 'currency') ?: 'usd',
                    array(
                        'booking_id' => $booking_id,
                        'booking_reference' => $booking->reference_number,
                        'customer_email' => $email,
                    )
                );

                if (!is_wp_error($intent_result)) {
                    $payment_intent = $intent_result;
                    
                    // Update booking with payment intent ID
                    Book_Now_Booking::update($booking_id, array(
                        'payment_intent_id' => $intent_result['intent_id'],
                    ));
                }
            }
        }

        // Trigger post-booking actions (calendar sync, email)
        do_action('booknow_booking_created', $booking_id);

        wp_send_json_success(array(
            'booking'        => $booking,
            'message'        => __('Booking created successfully!', 'book-now-kre8iv'),
            'reference'      => $booking->reference_number,
            'needs_payment'  => $needs_payment,
            'payment_intent' => $payment_intent,
        ));
    }

    /**
     * Get booking details by reference number.
     */
    public function get_booking_details() {
        check_ajax_referer('booknow_public_nonce', 'nonce');

        $reference = isset($_POST['reference']) ? sanitize_text_field($_POST['reference']) : '';

        if (!$reference) {
            wp_send_json_error(array('message' => __('Reference number is required.', 'book-now-kre8iv')));
        }

        $booking = Book_Now_Booking::get_by_reference($reference);

        if (!$booking) {
            wp_send_json_error(array('message' => __('Booking not found.', 'book-now-kre8iv')));
        }

        // Get consultation type details
        $type = Book_Now_Consultation_Type::get($booking->consultation_type_id);

        wp_send_json_success(array(
            'booking' => $booking,
            'consultation_type' => $type,
        ));
    }

    /**
     * Cancel a booking.
     */
    public function cancel_booking() {
        check_ajax_referer('booknow_public_nonce', 'nonce');

        $reference = isset($_POST['reference']) ? sanitize_text_field($_POST['reference']) : '';

        if (!$reference) {
            wp_send_json_error(array('message' => __('Reference number is required.', 'book-now-kre8iv')));
        }

        $booking = Book_Now_Booking::get_by_reference($reference);

        if (!$booking) {
            wp_send_json_error(array('message' => __('Booking not found.', 'book-now-kre8iv')));
        }

        // Check if booking can be cancelled
        if (in_array($booking->status, array('cancelled', 'completed', 'no-show'))) {
            wp_send_json_error(array('message' => __('This booking cannot be cancelled.', 'book-now-kre8iv')));
        }

        // Update status
        $updated = Book_Now_Booking::update($booking->id, array(
}
