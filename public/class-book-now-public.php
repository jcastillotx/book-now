<?php
/**
 * The public-facing functionality of the plugin
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now_Public {

    /**
     * The ID of this plugin.
     *
     * @var string
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @var string
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        global $post;

        // Only enqueue on pages with our shortcodes
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'book_now_form') ||
            has_shortcode($post->post_content, 'book_now_calendar') ||
            has_shortcode($post->post_content, 'book_now_list') ||
            has_shortcode($post->post_content, 'book_now_types')
        )) {
            wp_enqueue_style(
                $this->plugin_name,
                BOOK_NOW_PLUGIN_URL . 'public/css/book-now-public.css',
                array(),
                $this->version,
                'all'
            );
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        global $post;

        // Only enqueue on pages with our shortcodes
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'book_now_form') ||
            has_shortcode($post->post_content, 'book_now_calendar') ||
            has_shortcode($post->post_content, 'book_now_list') ||
            has_shortcode($post->post_content, 'book_now_types')
        )) {
            wp_enqueue_script(
                $this->plugin_name,
                BOOK_NOW_PLUGIN_URL . 'public/js/book-now-public.js',
                array('jquery'),
                $this->version,
                false
            );

            // Localize script
            wp_localize_script($this->plugin_name, 'bookNowPublic', array(
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('booknow_public_nonce'),
                'restUrl'   => rest_url('book-now/v1/'),
                'restNonce' => wp_create_nonce('wp_rest'),
                'strings'   => array(
                    'selectType'     => __('Please select a consultation type', 'book-now-kre8iv'),
                    'selectDateTime' => __('Please select date and time', 'book-now-kre8iv'),
                    'fillFields'     => __('Please fill in all required fields', 'book-now-kre8iv'),
                    'error'          => __('An error occurred. Please try again.', 'book-now-kre8iv'),
                    'loading'        => __('Loading...', 'book-now-kre8iv'),
                ),
            ));
        }
    }

    /**
     * AJAX: Get availability for a consultation type on a specific date.
     */
    public function ajax_get_availability() {
        check_ajax_referer('booknow_public_nonce', 'nonce');

        $consultation_type_id = absint($_POST['consultation_type_id'] ?? 0);
        $date = sanitize_text_field($_POST['date'] ?? '');

        if (!$consultation_type_id || !$date) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'book-now-kre8iv')));
        }

        // Check if date is within booking window
        if (!booknow_is_date_bookable($date)) {
            wp_send_json_error(array('message' => __('This date is not available for booking.', 'book-now-kre8iv')));
        }

        $slots = Book_Now_Availability::calculate_slots($date, $consultation_type_id);

        wp_send_json_success(array(
            'slots' => $slots,
            'date'  => $date,
        ));
    }

    /**
     * AJAX: Create a new booking.
     */
    public function ajax_create_booking() {
        check_ajax_referer('booknow_public_nonce', 'nonce');

        // Validate required fields
        $required = array('consultation_type_id', 'booking_date', 'booking_time', 'customer_name', 'customer_email');
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => sprintf(__('%s is required.', 'book-now-kre8iv'), ucfirst(str_replace('_', ' ', $field)))));
            }
        }

        // Validate email
        $email = booknow_sanitize_email($_POST['customer_email']);
        if (!$email) {
            wp_send_json_error(array('message' => __('Invalid email address.', 'book-now-kre8iv')));
        }

        // Get consultation type
        $consultation_type_id = absint($_POST['consultation_type_id']);
        $consultation_type = Book_Now_Consultation_Type::get_by_id($consultation_type_id);

        if (!$consultation_type) {
            wp_send_json_error(array('message' => __('Invalid consultation type.', 'book-now-kre8iv')));
        }

        // Create booking data
        $booking_data = array(
            'consultation_type_id' => $consultation_type_id,
            'customer_name'        => sanitize_text_field($_POST['customer_name']),
            'customer_email'       => $email,
            'customer_phone'       => booknow_sanitize_phone($_POST['customer_phone'] ?? ''),
            'customer_notes'       => wp_kses_post($_POST['customer_notes'] ?? ''),
            'booking_date'         => sanitize_text_field($_POST['booking_date']),
            'booking_time'         => sanitize_text_field($_POST['booking_time']),
            'duration'             => $consultation_type->duration,
            'timezone'             => sanitize_text_field($_POST['timezone'] ?? booknow_get_setting('general', 'timezone')),
            'status'               => 'pending',
            'payment_status'       => 'pending',
            'payment_amount'       => $consultation_type->price,
        );

        // Create booking
        $booking_id = Book_Now_Booking::create($booking_data);

        if ($booking_id) {
            $booking = Book_Now_Booking::get_by_id($booking_id);

            // If payment is required, return payment intent data
            // This would be implemented in Phase 4 with Stripe integration
            $payment_required = booknow_get_setting('payment', 'payment_required');

            wp_send_json_success(array(
                'message'          => __('Booking created successfully!', 'book-now-kre8iv'),
                'booking_id'       => $booking_id,
                'reference_number' => $booking->reference_number,
                'payment_required' => $payment_required,
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to create booking. Please try again.', 'book-now-kre8iv')));
        }
    }
}
