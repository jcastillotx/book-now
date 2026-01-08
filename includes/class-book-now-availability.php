<?php
/**
 * Availability model and slot calculation
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now_Availability {

    /**
     * Get all availability rules.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public static function get_all($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_availability';

        $defaults = array(
            'rule_type'          => null,
            'consultation_type'  => null,
            'orderby'            => 'priority',
            'order'              => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if ($args['rule_type']) {
            $where[] = 'rule_type = %s';
            $values[] = $args['rule_type'];
        }

        if ($args['consultation_type']) {
            $where[] = '(consultation_type_id = %d OR consultation_type_id IS NULL)';
            $values[] = $args['consultation_type'];
        }

        $where_clause = implode(' AND ', $where);
        $order_clause = sprintf('%s %s', $args['orderby'], $args['order']);

        if (!empty($values)) {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$order_clause}",
                ...$values
            );
        } else {
            $sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$order_clause}";
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Get a single availability rule by ID.
     *
     * @param int $id Rule ID.
     * @return object|null
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_availability';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $id
        ));
    }

    /**
     * Create a new availability rule.
     *
     * @param array $data Rule data.
     * @return int|false Rule ID or false on failure.
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_availability';

        $result = $wpdb->insert($table, array(
            'rule_type'             => sanitize_text_field($data['rule_type']),
            'day_of_week'           => isset($data['day_of_week']) ? absint($data['day_of_week']) : null,
            'specific_date'         => $data['specific_date'] ?? null,
            'start_time'            => $data['start_time'] ?? null,
            'end_time'              => $data['end_time'] ?? null,
            'is_available'          => isset($data['is_available']) ? (int)$data['is_available'] : 1,
            'consultation_type_id'  => isset($data['consultation_type_id']) ? absint($data['consultation_type_id']) : null,
            'priority'              => absint($data['priority'] ?? 0),
        ), array(
            '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d'
        ));

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update an availability rule.
     *
     * @param int   $id   Rule ID.
     * @param array $data Updated data.
     * @return bool
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_availability';

        $update_data = array();
        $format = array();

        if (isset($data['rule_type'])) {
            $update_data['rule_type'] = sanitize_text_field($data['rule_type']);
            $format[] = '%s';
        }
        if (isset($data['day_of_week'])) {
            $update_data['day_of_week'] = absint($data['day_of_week']);
            $format[] = '%d';
        }
        if (isset($data['specific_date'])) {
            $update_data['specific_date'] = $data['specific_date'];
            $format[] = '%s';
        }
        if (isset($data['start_time'])) {
            $update_data['start_time'] = $data['start_time'];
            $format[] = '%s';
        }
        if (isset($data['end_time'])) {
            $update_data['end_time'] = $data['end_time'];
            $format[] = '%s';
        }
        if (isset($data['is_available'])) {
            $update_data['is_available'] = (int)$data['is_available'];
            $format[] = '%d';
        }
        if (isset($data['consultation_type_id'])) {
            $update_data['consultation_type_id'] = absint($data['consultation_type_id']);
            $format[] = '%d';
        }
        if (isset($data['priority'])) {
            $update_data['priority'] = absint($data['priority']);
            $format[] = '%d';
        }

        return $wpdb->update($table, $update_data, array('id' => $id), $format, array('%d'));
    }

    /**
     * Delete an availability rule.
     *
     * @param int $id Rule ID.
     * @return bool
     */
    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_availability';

        return $wpdb->delete($table, array('id' => $id), array('%d')) !== false;
    }

    /**
     * Calculate available slots for a given date and consultation type.
     *
     * @param string $date              Date (Y-m-d).
     * @param int    $consultation_type_id Consultation type ID.
     * @return array
     */
    public static function calculate_slots($date, $consultation_type_id) {
        // Get consultation type details
        $consultation_type = Book_Now_Consultation_Type::get_by_id($consultation_type_id);
        if (!$consultation_type) {
            return array();
        }

        // Get day of week (0 = Sunday, 6 = Saturday)
        $day_of_week = date('w', strtotime($date));

        // Get availability rules for this day
        $rules = self::get_rules_for_date($date, $day_of_week, $consultation_type_id);

        if (empty($rules)) {
            return array();
        }

        // Generate time slots
        $slots = array();
        $interval = booknow_get_setting('general', 'slot_interval') ?: 30;
        $duration = $consultation_type->duration;

        foreach ($rules as $rule) {
            if (!$rule->is_available) {
                continue;
            }

            $start_minutes = booknow_time_to_minutes($rule->start_time);
            $end_minutes = booknow_time_to_minutes($rule->end_time);

            for ($minutes = $start_minutes; $minutes + $duration <= $end_minutes; $minutes += $interval) {
                $time = booknow_minutes_to_time($minutes);

                // Check if slot is not already booked
                if (!self::is_slot_booked($date, $time, $duration)) {
                    $slots[] = array(
                        'time'     => $time,
                        'display'  => booknow_format_time($time),
                        'datetime' => $date . ' ' . $time,
                    );
                }
            }
        }

        return $slots;
    }

    /**
     * Get availability rules for a specific date.
     *
     * @param string $date              Date (Y-m-d).
     * @param int    $day_of_week       Day of week (0-6).
     * @param int    $consultation_type_id Consultation type ID.
     * @return array
     */
    private static function get_rules_for_date($date, $day_of_week, $consultation_type_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_availability';

        // Priority: specific date > weekly > general
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE (
                (rule_type = 'specific_date' AND specific_date = %s)
                OR (rule_type = 'weekly' AND day_of_week = %d)
            )
            AND (consultation_type_id = %d OR consultation_type_id IS NULL)
            ORDER BY priority DESC, rule_type DESC",
            $date,
            $day_of_week,
            $consultation_type_id
        );

        $rules = $wpdb->get_results($sql);

        // Check for blocks on this date
        $blocks = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE rule_type = 'block'
            AND specific_date = %s
            AND (consultation_type_id = %d OR consultation_type_id IS NULL)",
            $date,
            $consultation_type_id
        ));

        // Remove blocked time ranges
        if (!empty($blocks)) {
            $rules = array_filter($rules, function($rule) use ($blocks) {
                foreach ($blocks as $block) {
                    if ($rule->start_time >= $block->start_time && $rule->end_time <= $block->end_time) {
                        return false;
                    }
                }
                return true;
            });
        }

        return $rules;
    }

    /**
     * Check if a time slot is already booked.
     *
     * @param string $date     Date (Y-m-d).
     * @param string $time     Time (HH:MM:SS).
     * @param int    $duration Duration in minutes.
     * @return bool
     */
    private static function is_slot_booked($date, $time, $duration) {
        $bookings = Book_Now_Booking::get_by_date($date);

        $slot_start = booknow_time_to_minutes($time);
        $slot_end = $slot_start + $duration;

        foreach ($bookings as $booking) {
            $booking_start = booknow_time_to_minutes($booking->booking_time);
            $booking_end = $booking_start + $booking->duration;

            // Check for overlap
            if ($slot_start < $booking_end && $slot_end > $booking_start) {
                return true;
            }
        }

        return false;
    }
}
