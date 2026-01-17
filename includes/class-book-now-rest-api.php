<?php
/**
 * REST API functionality for the plugin.
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 */

class Book_Now_REST_API {

    /**
     * REST API namespace.
     */
    const NAMESPACE = 'book-now/v1';

    /**
     * Initialize the REST API.
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Check rate limit and return error response if exceeded.
     *
     * @param string $action The rate limit action identifier.
     * @return WP_Error|null WP_Error if rate limited, null if allowed.
     */
    private function check_rate_limit( $action ) {
        if ( ! Book_Now_Rate_Limiter::check( $action ) ) {
            $retry_after = Book_Now_Rate_Limiter::get_retry_after( $action );

            // Send rate limit headers
            Book_Now_Rate_Limiter::send_rate_limit_headers( $action );

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

        // Send rate limit headers even for successful requests
        Book_Now_Rate_Limiter::send_rate_limit_headers( $action );

        return null;
    }

    /**
     * Register REST API routes.
     */
    public function register_routes() {
        // Get all consultation types
        register_rest_route(self::NAMESPACE, '/consultation-types', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_consultation_types'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'category' => array(
                    'type'        => 'integer',
                    'description' => 'Filter by category ID',
                ),
                'status' => array(
                    'type'        => 'string',
                    'default'     => 'active',
                    'description' => 'Filter by status',
                ),
            ),
        ));

        // Get single consultation type by slug
        register_rest_route(self::NAMESPACE, '/consultation-types/(?P<slug>[a-zA-Z0-9-]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_consultation_type'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'slug' => array(
                    'type'        => 'string',
                    'required'    => true,
                    'description' => 'Consultation type slug',
                ),
            ),
        ));

        // Get all categories
        register_rest_route(self::NAMESPACE, '/categories', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_categories'),
            'permission_callback' => '__return_true',
        ));

        // Get availability for consultation type
        register_rest_route(self::NAMESPACE, '/availability', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_availability'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'consultation_type_id' => array(
                    'type'        => 'integer',
                    'required'    => true,
                    'description' => 'Consultation type ID',
                ),
                'date' => array(
                    'type'        => 'string',
                    'required'    => true,
                    'description' => 'Date in Y-m-d format',
                ),
            ),
        ));

        // Create booking
        register_rest_route(self::NAMESPACE, '/bookings', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'create_booking'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'consultation_type_id' => array(
                    'type'        => 'integer',
                    'required'    => true,
                    'description' => 'Consultation type ID',
                ),
                'booking_date' => array(
                    'type'        => 'string',
                    'required'    => true,
                    'description' => 'Booking date (Y-m-d)',
                ),
                'booking_time' => array(
                    'type'        => 'string',
                    'required'    => true,
                    'description' => 'Booking time (H:i)',
                ),
                'customer_name' => array(
                    'type'        => 'string',
                    'required'    => true,
                    'description' => 'Customer name',
                ),
                'customer_email' => array(
                    'type'        => 'string',
                    'required'    => true,
                    'description' => 'Customer email',
                ),
                'customer_phone' => array(
                    'type'        => 'string',
                    'required'    => false,
                    'description' => 'Customer phone',
                ),
                'notes' => array(
                    'type'        => 'string',
                    'required'    => false,
                    'description' => 'Booking notes',
                ),
            ),
        ));

        // Get booking by reference
        register_rest_route(self::NAMESPACE, '/bookings/(?P<reference>[A-Z0-9]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_booking'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'reference' => array(
                    'type'        => 'string',
                    'required'    => true,
                    'description' => 'Booking reference number',
                ),
                'customer_email' => array(
                    'type'              => 'string',
                    'required'          => true,
                    'description'       => 'Customer email for verification',
                    'validate_callback' => function( $param ) {
                        return is_email( $param );
                    },
                    'sanitize_callback' => 'sanitize_email',
                ),
            ),
        ));

        // Cancel booking
        register_rest_route(self::NAMESPACE, '/bookings/(?P<reference>[A-Z0-9]+)/cancel', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'cancel_booking'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'reference' => array(
                    'type'        => 'string',
                    'required'    => true,
                    'description' => 'Booking reference number',
                ),
                'customer_email' => array(
                    'type'              => 'string',
                    'required'          => true,
                    'description'       => 'Customer email for verification',
                    'validate_callback' => function( $param ) {
                        return is_email( $param );
                    },
                    'sanitize_callback' => 'sanitize_email',
                ),
            ),
        ));
    }

    /**
     * Get all consultation types.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_consultation_types($request) {
        $category = $request->get_param('category');
        $status = $request->get_param('status');

        $args = array(
            'status' => $status,
        );

        if ($category) {
            $args['category_id'] = $category;
        }

        $types = Book_Now_Consultation_Type::get_all($args);

        if (is_wp_error($types)) {
            return new WP_Error('fetch_failed', __('Failed to fetch consultation types.', 'book-now-kre8iv'), array('status' => 500));
        }

        return new WP_REST_Response($types, 200);
    }

    /**
     * Get single consultation type by slug.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_consultation_type($request) {
        $slug = $request->get_param('slug');
        $type = Book_Now_Consultation_Type::get_by_slug($slug);

        if (!$type) {
            return new WP_Error('not_found', __('Consultation type not found.', 'book-now-kre8iv'), array('status' => 404));
        }

        return new WP_REST_Response($type, 200);
    }

    /**
     * Get all categories.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_categories($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_categories';

        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE status = %s ORDER BY name ASC",
            'active'
        ));

        if ($wpdb->last_error) {
            return new WP_Error('fetch_failed', __('Failed to fetch categories.', 'book-now-kre8iv'), array('status' => 500));
        }

        return new WP_REST_Response($categories, 200);
    }

    /**
     * Get available time slots for a consultation type on a specific date.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_availability($request) {
        // Check rate limit (60 requests per minute per IP)
        $rate_limit_error = $this->check_rate_limit( 'availability_check' );
        if ( $rate_limit_error ) {
            return $rate_limit_error;
        }

        $consultation_type_id = $request->get_param('consultation_type_id');
        $date = $request->get_param('date');

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return new WP_Error('invalid_date', __('Invalid date format. Use Y-m-d.', 'book-now-kre8iv'), array('status' => 400));
        }

        // Get consultation type
        $type = Book_Now_Consultation_Type::get_by_id($consultation_type_id);
        if (!$type) {
            return new WP_Error('not_found', __('Consultation type not found.', 'book-now-kre8iv'), array('status' => 404));
        }

        // Check if date is bookable
        if (!booknow_is_date_bookable($date)) {
            return new WP_REST_Response(array('slots' => array()), 200);
        }

        // Get available slots
        $slots = $this->calculate_available_slots($consultation_type_id, $date);

        return new WP_REST_Response(array('slots' => $slots), 200);
    }

    /**
     * Calculate available time slots for a consultation type on a date.
     *
     * @param int    $consultation_type_id Consultation type ID.
     * @param string $date                 Date in Y-m-d format.
     * @return array Available time slots.
     */
    private function calculate_available_slots($consultation_type_id, $date) {
        global $wpdb;

        // Get consultation type
        $type = Book_Now_Consultation_Type::get($consultation_type_id);
        if (!$type) {
            return array();
        }

        // Get day of week (0 = Sunday, 6 = Saturday)
        $day_of_week = date('w', strtotime($date));

        // Get availability rules for this day
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
            // Skip if not available
            if ($rule->is_available != 1) {
                continue;
            }

            $start_minutes = booknow_time_to_minutes($rule->start_time);
            $end_minutes = booknow_time_to_minutes($rule->end_time);

            // Generate slots
            for ($time = $start_minutes; $time + $duration <= $end_minutes; $time += $slot_interval) {
                $slot_time = booknow_minutes_to_time($time);
                $slot_end_time = booknow_minutes_to_time($time + $duration);

                // Check if slot is available (not booked)
                if ($this->is_slot_available($date, $slot_time, $duration, $buffer_before, $buffer_after)) {
                    $slots[] = array(
                        'time'      => $slot_time,
                        'end_time'  => $slot_end_time,
                        'available' => true,
                    );
                }
            }
        }

        return $slots;
    }

    /**
     * Check if a time slot is available (not conflicting with existing bookings).
     *
     * @param string $date          Date in Y-m-d format.
     * @param string $time          Time in H:i format.
     * @param int    $duration      Duration in minutes.
     * @param int    $buffer_before Buffer before in minutes.
     * @param int    $buffer_after  Buffer after in minutes.
     * @return bool True if available, false otherwise.
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
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function create_booking($request) {
        // Check rate limit (5 requests per hour per IP)
        $rate_limit_error = $this->check_rate_limit( 'booking_create' );
        if ( $rate_limit_error ) {
            return $rate_limit_error;
        }

        // Validate consultation type exists
        $consultation_type_id = $request->get_param('consultation_type_id');
        $type = Book_Now_Consultation_Type::get_by_id($consultation_type_id);
        
        if (!$type) {
            return new WP_Error('invalid_type', __('Invalid consultation type.', 'book-now-kre8iv'), array('status' => 400));
        }

        // Validate email
        $email = sanitize_email($request->get_param('customer_email'));
        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Invalid email address.', 'book-now-kre8iv'), array('status' => 400));
        }

        // Validate date
        $date = sanitize_text_field($request->get_param('booking_date'));
        if (!booknow_is_date_bookable($date)) {
            return new WP_Error('invalid_date', __('This date is not available for booking.', 'book-now-kre8iv'), array('status' => 400));
        }

        // Validate time format
        $time = sanitize_text_field($request->get_param('booking_time'));

        // Note: Pre-check availability for early feedback, but the atomic lock in
        // create_with_lock() is the authoritative check for race condition prevention
        if (!$this->is_slot_available($date, $time, $type->duration, $type->buffer_before, $type->buffer_after)) {
            return new WP_Error('slot_unavailable', __('This time slot is no longer available.', 'book-now-kre8iv'), array('status' => 400));
        }

        // Create booking data
        $booking_data = array(
            'consultation_type_id' => $consultation_type_id,
            'booking_date'         => $date,
            'booking_time'         => $time,
            'duration'             => $type->duration,
            'customer_name'        => sanitize_text_field($request->get_param('customer_name')),
            'customer_email'       => $email,
            'customer_phone'       => sanitize_text_field($request->get_param('customer_phone')),
            'customer_notes'       => sanitize_textarea_field($request->get_param('notes')),
            'payment_amount'       => $type->price,
            'status'               => 'pending',
            'payment_status'       => 'pending',
        );

        // Create booking with atomic lock to prevent race conditions
        // This ensures that concurrent requests cannot double-book the same slot
        $booking_id = Book_Now_Booking::create_with_lock($booking_data);

        if (is_wp_error($booking_id)) {
            $error_code = $booking_id->get_error_code();
            $error_data = $booking_id->get_error_data();
            $status = isset($error_data['status']) ? $error_data['status'] : 500;

            return new WP_Error($error_code, $booking_id->get_error_message(), array('status' => $status));
        }

        // Get created booking
        $booking = Book_Now_Booking::get_by_id($booking_id);

        // Note: 'booknow_booking_created' action is already fired in Book_Now_Booking::create()

        return new WP_REST_Response(array(
            'success'   => true,
            'booking'   => $booking,
            'message'   => __('Booking created successfully.', 'book-now-kre8iv'),
        ), 201);
    }

    /**
     * Calculate deposit amount based on consultation type settings.
     *
     * @param object $type Consultation type object.
     * @return float Deposit amount.
     */
    private function calculate_deposit($type) {
        if (!$type->require_deposit) {
            return 0;
        }

        if ($type->deposit_type === 'percentage') {
            return ($type->price * $type->deposit_amount) / 100;
        }

        return $type->deposit_amount;
    }

    /**
     * Get booking by reference number.
     *
     * Requires email verification to prevent unauthorized access to booking data.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_booking($request) {
        // Check rate limit (20 requests per minute per IP)
        $rate_limit_error = $this->check_rate_limit( 'booking_lookup' );
        if ( $rate_limit_error ) {
            return $rate_limit_error;
        }

        $reference = sanitize_text_field($request->get_param('reference'));
        $customer_email = sanitize_email($request->get_param('customer_email'));

        $booking = Book_Now_Booking::get_by_reference($reference);

        if (!$booking) {
            return new WP_Error('not_found', __('Booking not found.', 'book-now-kre8iv'), array('status' => 404));
        }

        // Verify email matches the booking's customer email (case-insensitive comparison)
        if (strtolower($booking->customer_email) !== strtolower($customer_email)) {
            return new WP_Error(
                'forbidden',
                __('The provided email does not match the booking record.', 'book-now-kre8iv'),
                array('status' => 403)
            );
        }

        return new WP_REST_Response($booking, 200);
    }

    /**
     * Cancel a booking.
     *
     * Requires email verification to prevent unauthorized cancellation.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function cancel_booking($request) {
        $reference = sanitize_text_field($request->get_param('reference'));
        $customer_email = sanitize_email($request->get_param('customer_email'));

        $booking = Book_Now_Booking::get_by_reference($reference);

        if (!$booking) {
            return new WP_Error('not_found', __('Booking not found.', 'book-now-kre8iv'), array('status' => 404));
        }

        // Verify email matches the booking's customer email (case-insensitive comparison)
        if (strtolower($booking->customer_email) !== strtolower($customer_email)) {
            return new WP_Error(
                'forbidden',
                __('The provided email does not match the booking record.', 'book-now-kre8iv'),
                array('status' => 403)
            );
        }

        // Check if booking can be cancelled
        if (in_array($booking->status, array('cancelled', 'completed', 'no-show'), true)) {
            return new WP_Error('cannot_cancel', __('This booking cannot be cancelled.', 'book-now-kre8iv'), array('status' => 400));
        }

        // Update booking status
        $updated = Book_Now_Booking::update($booking->id, array(
            'status' => 'cancelled',
        ));

        if (is_wp_error($updated)) {
            return new WP_Error('update_failed', __('Failed to cancel booking.', 'book-now-kre8iv'), array('status' => 500));
        }

        // Trigger cancellation actions (email, calendar cleanup)
        do_action('booknow_booking_cancelled', $booking->id);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Booking cancelled successfully.', 'book-now-kre8iv'),
        ), 200);
    }
}
