<?php
/**
 * Booking model and CRUD operations
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now_Booking {

    /**
     * Get all bookings.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public static function get_all($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        $defaults = array(
            'status'        => null,
            'date_from'     => null,
            'date_to'       => null,
            'consultation_type' => null,
            'limit'         => 50,
            'offset'        => 0,
            'orderby'       => 'booking_date',
            'order'         => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        // Whitelist allowed orderby values to prevent SQL injection
        $allowed_orderby = array('booking_date', 'booking_time', 'created_at', 'customer_name', 'status', 'payment_status', 'id');
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'booking_date';

        // Whitelist allowed order values
        $allowed_order = array('ASC', 'DESC');
        $order = in_array(strtoupper($args['order']), $allowed_order, true) ? strtoupper($args['order']) : 'DESC';

        $where = array('1=1');
        $values = array();

        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ($args['date_from']) {
            $where[] = 'booking_date >= %s';
            $values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where[] = 'booking_date <= %s';
            $values[] = $args['date_to'];
        }

        if ($args['consultation_type']) {
            $where[] = 'consultation_type_id = %d';
            $values[] = $args['consultation_type'];
        }

        $where_clause = implode(' AND ', $where);
        $order_clause = sprintf('%s %s', $orderby, $order);

        $values[] = $args['limit'];
        $values[] = $args['offset'];

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$order_clause} LIMIT %d OFFSET %d",
            ...$values
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Get a single booking by ID.
     *
     * @param int $id Booking ID.
     * @return object|null
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $id
        ));
    }

    /**
     * Get a single booking by reference number.
     *
     * @param string $reference Reference number.
     * @return object|null
     */
    public static function get_by_reference($reference) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE reference_number = %s",
            $reference
        ));
    }

    /**
     * Create a new booking.
     *
     * @param array $data Booking data.
     * @return int|false Booking ID or false on failure.
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        // Generate reference number
        $reference = booknow_generate_reference_number();

        $result = $wpdb->insert($table, array(
            'reference_number'       => $reference,
            'consultation_type_id'   => absint($data['consultation_type_id']),
            'customer_name'          => sanitize_text_field($data['customer_name']),
            'customer_email'         => sanitize_email($data['customer_email']),
            'customer_phone'         => booknow_sanitize_phone($data['customer_phone'] ?? ''),
            'customer_notes'         => wp_kses_post($data['customer_notes'] ?? ''),
            'booking_date'           => sanitize_text_field($data['booking_date']),
            'booking_time'           => sanitize_text_field($data['booking_time']),
            'duration'               => absint($data['duration']),
            'timezone'               => sanitize_text_field($data['timezone'] ?? 'UTC'),
            'status'                 => sanitize_text_field($data['status'] ?? 'pending'),
            'payment_status'         => sanitize_text_field($data['payment_status'] ?? 'pending'),
            'payment_amount'         => isset($data['payment_amount']) ? floatval($data['payment_amount']) : null,
        ), array(
            '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%f'
        ));

        if ($result === false) {
            error_log('BookNow DB Error in create_booking: ' . $wpdb->last_error);
            return false;
        }

        do_action('booknow_booking_created', $wpdb->insert_id, $data);
        return $wpdb->insert_id;
    }

    /**
     * Update a booking.
     *
     * @param int   $id   Booking ID.
     * @param array $data Updated data.
     * @return bool
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        $update_data = array();
        $format = array();

        // Only update provided fields
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $format[] = '%s';
        }
        if (isset($data['payment_status'])) {
            $update_data['payment_status'] = sanitize_text_field($data['payment_status']);
            $format[] = '%s';
        }
        if (isset($data['payment_intent_id'])) {
            $update_data['payment_intent_id'] = sanitize_text_field($data['payment_intent_id']);
            $format[] = '%s';
        }
        if (isset($data['payment_date'])) {
            $update_data['payment_date'] = sanitize_text_field($data['payment_date']);
            $format[] = '%s';
        }
        if (isset($data['google_event_id'])) {
            $update_data['google_event_id'] = sanitize_text_field($data['google_event_id']);
            $format[] = '%s';
        }
        if (isset($data['microsoft_event_id'])) {
            $update_data['microsoft_event_id'] = sanitize_text_field($data['microsoft_event_id']);
            $format[] = '%s';
        }
        if (isset($data['reminder_sent'])) {
            $update_data['reminder_sent'] = (int)$data['reminder_sent'];
            $format[] = '%d';
        }
        if (isset($data['reminder_sent_at'])) {
            $update_data['reminder_sent_at'] = sanitize_text_field($data['reminder_sent_at']);
            $format[] = '%s';
        }
        if (isset($data['admin_notes'])) {
            $update_data['admin_notes'] = wp_kses_post($data['admin_notes']);
            $format[] = '%s';
        }

        $result = $wpdb->update($table, $update_data, array('id' => $id), $format, array('%d'));

        if ($result !== false) {
            do_action('booknow_booking_updated', $id, $data);
        }

        return $result !== false;
    }

    /**
     * Delete a booking.
     *
     * @param int $id Booking ID.
     * @return bool
     */
    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        do_action('booknow_before_booking_deleted', $id);

        return $wpdb->delete($table, array('id' => $id), array('%d')) !== false;
    }

    /**
     * Cancel a booking.
     *
     * @param int $id Booking ID.
     * @return bool
     */
    public static function cancel($id) {
        return self::update($id, array('status' => 'cancelled'));
    }

    /**
     * Get bookings for a specific date and consultation type.
     *
     * @param string $date              Date (Y-m-d).
     * @param int    $consultation_type_id Consultation type ID.
     * @return array
     */
    public static function get_by_date($date, $consultation_type_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        if ($consultation_type_id) {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE booking_date = %s
                AND consultation_type_id = %d
                AND status NOT IN ('cancelled')
                ORDER BY booking_time ASC",
                $date,
                $consultation_type_id
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE booking_date = %s
                AND status NOT IN ('cancelled')
                ORDER BY booking_time ASC",
                $date
            );
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Get booking statistics.
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        return array(
            'total'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"),
            'pending'   => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", 'pending')),
            'confirmed' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", 'confirmed')),
            'completed' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", 'completed')),
            'cancelled' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", 'cancelled')),
        );
    }

    /**
     * Get upcoming bookings for reminders.
     *
     * @param int $hours Hours ahead to check.
     * @return array
     */
    public static function get_upcoming_for_reminders($hours = 24) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        $start_time = current_time('mysql');
        $end_time = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE CONCAT(booking_date, ' ', booking_time) BETWEEN %s AND %s
            AND status IN ('pending', 'confirmed')
            AND reminder_sent = 0
            ORDER BY booking_date ASC, booking_time ASC",
            $start_time,
            $end_time
        );

        return $wpdb->get_results($sql);
    }
}
