<?php
/**
 * Shortcode functionality
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now_Shortcodes {

    /**
     * Render the booking form shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_booking_form($atts) {
        $atts = shortcode_atts(array(
            'type'        => '',
            'category'    => '',
            'show_types'  => 'true',
        ), $atts, 'book_now_form');

        ob_start();
        include BOOK_NOW_PLUGIN_DIR . 'public/partials/form-wizard.php';
        return ob_get_clean();
    }

    /**
     * Render calendar view shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_calendar($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'category' => '',
        ), $atts);

        ob_start();
        include BOOK_NOW_PLUGIN_DIR . 'public/partials/calendar-view.php';
        return ob_get_clean();
    }

    /**
     * Render the list view shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_list_view($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'category' => '',
            'days' => 7,
        ), $atts);

        ob_start();
        include BOOK_NOW_PLUGIN_DIR . 'public/partials/list-view.php';
        return ob_get_clean();
    }

    /**
     * Render consultation types shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_consultation_types($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'columns'  => '3',
        ), $atts, 'book_now_types');

        $args = array('status' => 'active');
        if ($atts['category']) {
            $args['category'] = absint($atts['category']);
        }

        $types = Book_Now_Consultation_Type::get_all($args);

        if (empty($types)) {
            return '<p>' . esc_html__('No consultation types available at this time.', 'book-now-kre8iv') . '</p>';
        }

        ob_start();
        ?>
        <div class="booknow-types-grid booknow-columns-<?php echo esc_attr($atts['columns']); ?>">
            <?php foreach ($types as $type) : ?>
                <div class="booknow-type-card">
                    <h3 class="type-name"><?php echo esc_html($type->name); ?></h3>

                    <?php if ($type->description) : ?>
                        <div class="type-description">
                            <?php echo wp_kses_post($type->description); ?>
                        </div>
                    <?php endif; ?>

                    <div class="type-meta">
                        <span class="type-duration">
                            <span class="dashicons dashicons-clock"></span>
                            <?php printf(esc_html__('%d minutes', 'book-now-kre8iv'), $type->duration); ?>
                        </span>

                        <span class="type-price">
                            <?php echo esc_html(booknow_format_price($type->price)); ?>
                        </span>
                    </div>

                    <a href="#" class="button booknow-select-type" data-type-id="<?php echo esc_attr($type->id); ?>">
                        <?php esc_html_e('Book Now', 'book-now-kre8iv'); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
