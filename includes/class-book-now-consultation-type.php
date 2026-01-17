<?php
/**
 * Consultation Type model and CRUD operations
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now_Consultation_Type {

    /**
     * Get all consultation types.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public static function get_all($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_consultation_types';

        $defaults = array(
            'status'   => 'active',
            'category' => null,
            'orderby'  => 'name',
            'order'    => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);

        // Whitelist allowed orderby values to prevent SQL injection
        $allowed_orderby = array('name', 'price', 'duration', 'created_at', 'id');
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'name';

        // Whitelist allowed order values
        $allowed_order = array('ASC', 'DESC');
        $order = in_array(strtoupper($args['order']), $allowed_order, true) ? strtoupper($args['order']) : 'ASC';

        $where = array('1=1');
        $values = array();

        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ($args['category']) {
            $where[] = 'category_id = %d';
            $values[] = $args['category'];
        }

        $where_clause = implode(' AND ', $where);
        $order_clause = sprintf('%s %s', $orderby, $order);

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
     * Get a single consultation type by ID.
     *
     * Alias for get_by_id() for backwards compatibility.
     *
     * @param int $id Consultation type ID.
     * @return object|null
     */
    public static function get($id) {
        return self::get_by_id($id);
    }

    /**
     * Get a single consultation type by ID.
     *
     * @param int $id Consultation type ID.
     * @return object|null
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_consultation_types';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $id
        ));
    }

    /**
     * Get a single consultation type by slug.
     *
     * @param string $slug Consultation type slug.
     * @return object|null
     */
    public static function get_by_slug($slug) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_consultation_types';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE slug = %s",
            $slug
        ));
    }

    /**
     * Create a new consultation type.
     *
     * @param array $data Consultation type data.
     * @return int|false Consultation type ID or false on failure.
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_consultation_types';

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['name']);
        }

        // Ensure unique slug
        $data['slug'] = self::unique_slug($data['slug']);

        $result = $wpdb->insert($table, array(
            'name'             => sanitize_text_field($data['name']),
            'slug'             => $data['slug'],
            'description'      => wp_kses_post($data['description'] ?? ''),
            'duration'         => absint($data['duration'] ?? 30),
            'price'            => floatval($data['price'] ?? 0),
            'deposit_amount'   => isset($data['deposit_amount']) ? floatval($data['deposit_amount']) : null,
            'deposit_type'     => sanitize_text_field($data['deposit_type'] ?? 'fixed'),
            'category_id'      => isset($data['category_id']) ? absint($data['category_id']) : null,
            'buffer_before'    => absint($data['buffer_before'] ?? 0),
            'buffer_after'     => absint($data['buffer_after'] ?? 0),
            'status'           => sanitize_text_field($data['status'] ?? 'active'),
        ), array(
            '%s', '%s', '%s', '%d', '%f', '%f', '%s', '%d', '%d', '%d', '%s'
        ));

        if ($result === false) {
            Book_Now_Logger::error( 'DB Error in create_consultation_type', array( 'error' => $wpdb->last_error ) );
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update a consultation type.
     *
     * @param int   $id   Consultation type ID.
     * @param array $data Updated data.
     * @return bool
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_consultation_types';

        $update_data = array();
        $format = array();

        // Only update provided fields
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }
        if (isset($data['slug'])) {
            $update_data['slug'] = sanitize_title($data['slug']);
            $format[] = '%s';
        }
        if (isset($data['description'])) {
            $update_data['description'] = wp_kses_post($data['description']);
            $format[] = '%s';
        }
        if (isset($data['duration'])) {
            $update_data['duration'] = absint($data['duration']);
            $format[] = '%d';
        }
        if (isset($data['price'])) {
            $update_data['price'] = floatval($data['price']);
            $format[] = '%f';
        }
        if (isset($data['deposit_amount'])) {
            $update_data['deposit_amount'] = floatval($data['deposit_amount']);
            $format[] = '%f';
        }
        if (isset($data['deposit_type'])) {
            $update_data['deposit_type'] = sanitize_text_field($data['deposit_type']);
            $format[] = '%s';
        }
        if (isset($data['category_id'])) {
            $update_data['category_id'] = absint($data['category_id']);
            $format[] = '%d';
        }
        if (isset($data['buffer_before'])) {
            $update_data['buffer_before'] = absint($data['buffer_before']);
            $format[] = '%d';
        }
        if (isset($data['buffer_after'])) {
            $update_data['buffer_after'] = absint($data['buffer_after']);
            $format[] = '%d';
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $format[] = '%s';
        }

        return $wpdb->update($table, $update_data, array('id' => $id), $format, array('%d'));
    }

    /**
     * Delete a consultation type.
     *
     * @param int $id Consultation type ID.
     * @return bool
     */
    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_consultation_types';

        return $wpdb->delete($table, array('id' => $id), array('%d')) !== false;
    }

    /**
     * Generate a unique slug.
     *
     * @param string $slug Base slug.
     * @param int    $id   Consultation type ID (for updates).
     * @return string
     */
    private static function unique_slug($slug, $id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_consultation_types';

        $original_slug = $slug;
        $counter = 1;

        while (true) {
            $query = $wpdb->prepare(
                "SELECT id FROM {$table} WHERE slug = %s AND id != %d",
                $slug,
                $id
            );

            if (!$wpdb->get_var($query)) {
                break;
            }

            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get consultation type count by status.
     *
     * @param string $status Status to count.
     * @return int
     */
    public static function count_by_status($status = 'active') {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_consultation_types';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = %s",
            $status
        ));
    }
}
