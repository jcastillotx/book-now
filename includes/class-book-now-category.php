<?php
/**
 * Category model and CRUD operations
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now_Category {

    /**
     * Get all categories.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public static function get_all($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_categories';

        $defaults = array(
            'parent_id' => null,
            'orderby'   => 'display_order',
            'order'     => 'ASC',
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if ($args['parent_id'] !== null) {
            if ($args['parent_id'] === 0) {
                $where[] = 'parent_id IS NULL';
            } else {
                $where[] = 'parent_id = %d';
                $values[] = $args['parent_id'];
            }
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
     * Get a single category by ID.
     *
     * @param int $id Category ID.
     * @return object|null
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_categories';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $id
        ));
    }

    /**
     * Get a single category by slug.
     *
     * @param string $slug Category slug.
     * @return object|null
     */
    public static function get_by_slug($slug) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_categories';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE slug = %s",
            $slug
        ));
    }

    /**
     * Create a new category.
     *
     * @param array $data Category data.
     * @return int|false Category ID or false on failure.
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_categories';

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['name']);
        }

        // Ensure unique slug
        $data['slug'] = self::unique_slug($data['slug']);

        $result = $wpdb->insert($table, array(
            'name'          => sanitize_text_field($data['name']),
            'slug'          => $data['slug'],
            'description'   => wp_kses_post($data['description'] ?? ''),
            'parent_id'     => isset($data['parent_id']) && $data['parent_id'] > 0 ? absint($data['parent_id']) : null,
            'display_order' => absint($data['display_order'] ?? 0),
        ), array(
            '%s', '%s', '%s', '%d', '%d'
        ));

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update a category.
     *
     * @param int   $id   Category ID.
     * @param array $data Updated data.
     * @return bool
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_categories';

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
        if (isset($data['parent_id'])) {
            $update_data['parent_id'] = $data['parent_id'] > 0 ? absint($data['parent_id']) : null;
            $format[] = '%d';
        }
        if (isset($data['display_order'])) {
            $update_data['display_order'] = absint($data['display_order']);
            $format[] = '%d';
        }

        return $wpdb->update($table, $update_data, array('id' => $id), $format, array('%d'));
    }

    /**
     * Delete a category.
     *
     * @param int $id Category ID.
     * @return bool
     */
    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_categories';

        // Check if category has children
        $children = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE parent_id = %d",
            $id
        ));

        if ($children > 0) {
            return false; // Cannot delete category with children
        }

        // Check if category is used by consultation types
        $types_table = $wpdb->prefix . 'booknow_consultation_types';
        $types_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$types_table} WHERE category_id = %d",
            $id
        ));

        if ($types_count > 0) {
            return false; // Cannot delete category in use
        }

        return $wpdb->delete($table, array('id' => $id), array('%d')) !== false;
    }

    /**
     * Generate a unique slug.
     *
     * @param string $slug Base slug.
     * @param int    $id   Category ID (for updates).
     * @return string
     */
    private static function unique_slug($slug, $id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_categories';

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
     * Get category count.
     *
     * @return int
     */
    public static function count() {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_categories';

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    /**
     * Get categories with consultation type count.
     *
     * @return array
     */
    public static function get_with_counts() {
        global $wpdb;
        $categories_table = $wpdb->prefix . 'booknow_categories';
        $types_table = $wpdb->prefix . 'booknow_consultation_types';

        $sql = "SELECT c.*, COUNT(t.id) as type_count
                FROM {$categories_table} c
                LEFT JOIN {$types_table} t ON c.id = t.category_id
                GROUP BY c.id
                ORDER BY c.display_order ASC, c.name ASC";

        return $wpdb->get_results($sql);
    }
}
