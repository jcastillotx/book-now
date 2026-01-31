<?php
/**
 * The admin-specific functionality of the plugin
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now_Admin {

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
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Handle booking actions before page renders (to allow redirects)
        add_action('admin_init', array($this, 'handle_booking_actions'));
    }

    /**
     * Handle booking actions (confirm, cancel, sync, etc.) before page renders.
     *
     * This must run on admin_init to allow redirects before headers are sent.
     */
    public function handle_booking_actions() {
        // Only process on the bookings page
        if (!isset($_GET['page']) || sanitize_text_field(wp_unslash($_GET['page'])) !== 'book-now-bookings') {
            return;
        }

        // Check for action and required parameters
        $booking_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if (!$booking_id || !isset($_GET['action']) || !isset($_GET['_wpnonce'])) {
            return;
        }

        // Security check
        if (!current_user_can('manage_options')) {
            return;
        }

        $action = sanitize_text_field(wp_unslash($_GET['action']));

        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'booknow_booking_action_' . $booking_id)) {
            $redirect_url = add_query_arg(
                array(
                    'page' => 'book-now-bookings',
                    'id' => $booking_id,
                    'booknow_notice' => rawurlencode(__('Security check failed. The requested action could not be completed. Please try again.', 'book-now-kre8iv')),
                    'booknow_notice_type' => 'error',
                ),
                admin_url('admin.php')
            );
            wp_redirect($redirect_url);
            exit;
        }

        $existing_booking = Book_Now_Booking::get($booking_id);
        if (!$existing_booking) {
            $redirect_url = add_query_arg(
                array(
                    'page' => 'book-now-bookings',
                    'id' => $booking_id,
                    'booknow_notice' => rawurlencode(__('Booking not found.', 'book-now-kre8iv')),
                    'booknow_notice_type' => 'error',
                ),
                admin_url('admin.php')
            );
            wp_redirect($redirect_url);
            exit;
        }

        $notice = '';
        $notice_type = 'info';

        switch ($action) {
            case 'confirm':
                Book_Now_Booking::update($booking_id, array('status' => 'confirmed'));
                do_action('booknow_booking_confirmed', $booking_id);
                $notice = __('Booking confirmed successfully. Calendar sync triggered.', 'book-now-kre8iv');
                $notice_type = 'success';
                break;

            case 'cancel':
                Book_Now_Booking::update($booking_id, array('status' => 'cancelled'));
                do_action('booknow_booking_cancelled', $booking_id);
                $notice = __('Booking cancelled.', 'book-now-kre8iv');
                $notice_type = 'warning';
                break;

            case 'complete':
                Book_Now_Booking::update($booking_id, array('status' => 'completed'));
                $notice = __('Booking marked as completed.', 'book-now-kre8iv');
                $notice_type = 'success';
                break;

            case 'resend_email':
                $email = new Book_Now_Email();
                $sent = $email->send_confirmation_email($booking_id);
                $notice = $sent
                    ? __('Confirmation email sent.', 'book-now-kre8iv')
                    : __('Failed to send email. Check email settings.', 'book-now-kre8iv');
                $notice_type = $sent ? 'success' : 'error';
                break;

            case 'sync_calendar':
                $notice = $this->handle_calendar_sync($booking_id);
                $notice_type = $this->get_sync_notice_type($notice);
                break;

            default:
                // Unknown action, just return without redirect
                return;
        }

        // Redirect to clean URL with notice
        $redirect_url = add_query_arg(
            array(
                'page' => 'book-now-bookings',
                'id' => $booking_id,
                'booknow_notice' => rawurlencode($notice),
                'booknow_notice_type' => $notice_type,
            ),
            admin_url('admin.php')
        );
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Handle calendar sync action with robust error handling to prevent blank screens.
     *
     * @param int $booking_id Booking ID.
     * @return string Notice message.
     */
    private function handle_calendar_sync($booking_id) {
        // Start output buffering to prevent any stray output causing blank screens
        ob_start();

        try {
            // Load dependencies required by calendar classes with error checking
            $required_classes = array(
                'Book_Now_Encryption' => 'includes/class-book-now-encryption.php',
                'Book_Now_Logger' => 'includes/class-book-now-logger.php',
                'Book_Now_Calendar_Sync' => 'includes/class-book-now-calendar-sync.php',
                'Book_Now_Google_Calendar' => 'includes/class-book-now-google-calendar.php',
                'Book_Now_Microsoft_Calendar' => 'includes/class-book-now-microsoft-calendar.php',
                'Book_Now_Consultation_Type' => 'includes/class-book-now-consultation-type.php',
            );

            foreach ($required_classes as $class_name => $file_path) {
                if (!class_exists($class_name)) {
                    $full_path = BOOK_NOW_PLUGIN_DIR . $file_path;
                    if (!file_exists($full_path)) {
                        ob_end_clean();
                        return sprintf(__('Required file missing: %s', 'book-now-kre8iv'), $file_path);
                    }
                    require_once $full_path;
                }
            }

            // Create calendar sync instance with error handling
            $calendar_sync = new Book_Now_Calendar_Sync();

            // Perform the sync
            $results = $calendar_sync->manual_sync($booking_id);

            // Clean output buffer
            ob_end_clean();

            if (empty($results)) {
                return __('No calendars configured or authenticated. Go to Settings → Integrations to connect your calendar.', 'book-now-kre8iv');
            }

            if (isset($results['error'])) {
                return $results['error'];
            }

            $success_msgs = array();
            $error_msgs = array();

            foreach ($results as $provider => $status) {
                if ($status === 'error') {
                    $error_msgs[] = sprintf(
                        __('Sync with %s failed. Please re-authenticate in Settings → Integrations.', 'book-now-kre8iv'),
                        ucfirst($provider)
                    );
                } else {
                    $success_msgs[] = sprintf(
                        __('Sync with %s successful (%s).', 'book-now-kre8iv'),
                        ucfirst($provider),
                        $status
                    );
                }
            }

            if (!empty($error_msgs)) {
                return implode(' ', array_merge($error_msgs, $success_msgs));
            }

            return implode(' ', $success_msgs);

        } catch (Throwable $e) {
            // Clean any buffered output
            ob_end_clean();

            // Log the error
            if (class_exists('Book_Now_Logger')) {
                Book_Now_Logger::error('Calendar sync failed', array(
                    'booking_id' => $booking_id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ));
            }

            // Return user-friendly error message
            return sprintf(
                __('Calendar sync error: %s. Check Settings → Integrations to verify your calendar connection.', 'book-now-kre8iv'),
                $e->getMessage()
            );
        }
    }

    /**
     * Determine notice type based on sync result message.
     *
     * @param string $notice The notice message.
     * @return string Notice type (success, warning, error).
     */
    private function get_sync_notice_type($notice) {
        if (strpos($notice, __('failed', 'book-now-kre8iv')) !== false ||
            strpos($notice, __('error', 'book-now-kre8iv')) !== false) {
            // Check if there's also a success
            if (strpos($notice, __('successful', 'book-now-kre8iv')) !== false) {
                return 'warning';
            }
            return 'error';
        }

        if (strpos($notice, __('No calendars', 'book-now-kre8iv')) !== false) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        if ($this->is_booknow_admin_page()) {
            wp_enqueue_style(
                $this->plugin_name,
                BOOK_NOW_PLUGIN_URL . 'admin/css/book-now-admin.css',
                array(),
                $this->version,
                'all'
            );
        }
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        if ($this->is_booknow_admin_page()) {
            wp_enqueue_script(
                $this->plugin_name,
                BOOK_NOW_PLUGIN_URL . 'admin/js/book-now-admin.js',
                array('jquery'),
                $this->version,
                false
            );

            // Localize script
            wp_localize_script($this->plugin_name, 'bookNowAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('booknow_admin_nonce'),
                'strings' => array(
                    'confirmDelete' => __('Are you sure you want to delete this item?', 'book-now-kre8iv'),
                    'error'         => __('An error occurred. Please try again.', 'book-now-kre8iv'),
                    'success'       => __('Changes saved successfully.', 'book-now-kre8iv'),
                ),
            ));
        }
    }

    /**
     * Check if current page is a Book Now admin page.
     *
     * @return bool
     */
    private function is_booknow_admin_page() {
        $screen = get_current_screen();
        return $screen && strpos($screen->id, 'book-now') !== false;
    }

    /**
     * Add admin menu pages.
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Book Now', 'book-now-kre8iv'),
            __('Book Now', 'book-now-kre8iv'),
            'manage_options',
            'book-now',
            array($this, 'display_dashboard'),
            'dashicons-calendar-alt',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'book-now',
            __('Dashboard', 'book-now-kre8iv'),
            __('Dashboard', 'book-now-kre8iv'),
            'manage_options',
            'book-now',
            array($this, 'display_dashboard')
        );

        // Bookings submenu
        add_submenu_page(
            'book-now',
            __('Bookings', 'book-now-kre8iv'),
            __('Bookings', 'book-now-kre8iv'),
            'manage_options',
            'book-now-bookings',
            array($this, 'display_bookings')
        );

        // Consultation Types submenu
        add_submenu_page(
            'book-now',
            __('Consultation Types', 'book-now-kre8iv'),
            __('Consultation Types', 'book-now-kre8iv'),
            'manage_options',
            'book-now-types',
            array($this, 'display_consultation_types')
        );

        // Availability submenu
        add_submenu_page(
            'book-now',
            __('Availability', 'book-now-kre8iv'),
            __('Availability', 'book-now-kre8iv'),
            'manage_options',
            'book-now-availability',
            array($this, 'display_availability')
        );

        // Categories submenu
        add_submenu_page(
            'book-now',
            __('Categories', 'book-now-kre8iv'),
            __('Categories', 'book-now-kre8iv'),
            'manage_options',
            'book-now-categories',
            array($this, 'display_categories')
        );

        // Settings submenu
        add_submenu_page(
            'book-now',
            __('Settings', 'book-now-kre8iv'),
            __('Settings', 'book-now-kre8iv'),
            'manage_options',
            'book-now-settings',
            array($this, 'display_settings')
        );

        // Setup Wizard submenu
        add_submenu_page(
            'book-now',
            __('Setup Wizard', 'book-now-kre8iv'),
            __('Setup Wizard', 'book-now-kre8iv'),
            'manage_options',
            'booknow-setup',
            array($this, 'display_setup_wizard')
        );

        // Email Logs submenu
        add_submenu_page(
            'book-now',
            __('Email Logs', 'book-now-kre8iv'),
            __('Email Logs', 'book-now-kre8iv'),
            'manage_options',
            'book-now-email-logs',
            array($this, 'display_email_logs')
        );

        // Error Logs submenu
        add_submenu_page(
            'book-now',
            __('Error Logs', 'book-now-kre8iv'),
            __('Error Logs', 'book-now-kre8iv'),
            'manage_options',
            'book-now-error-logs',
            array($this, 'display_error_logs')
        );
    }

    /**
     * Display setup wizard page.
     */
    public function display_setup_wizard() {
        $wizard = new Book_Now_Setup_Wizard();
        $wizard->setup_wizard();
    }

    /**
     * Display dashboard page.
     */
    public function display_dashboard() {
        include BOOK_NOW_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Display bookings page.
     */
    public function display_bookings() {
        include BOOK_NOW_PLUGIN_DIR . 'admin/partials/bookings-list.php';
    }

    /**
     * Display consultation types page.
     */
    public function display_consultation_types() {
        include BOOK_NOW_PLUGIN_DIR . 'admin/partials/consultation-types-list.php';
    }

    /**
     * Display availability page.
     */
    public function display_availability() {
        include BOOK_NOW_PLUGIN_DIR . 'admin/partials/availability.php';
    }

    /**
     * Display categories page.
     */
    public function display_categories() {
        include BOOK_NOW_PLUGIN_DIR . 'admin/partials/categories.php';
    }

    /**
     * Display settings page.
     */
    public function display_settings() {
        include BOOK_NOW_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * Display email logs page.
     */
    public function display_email_logs() {
        include BOOK_NOW_PLUGIN_DIR . 'admin/partials/email-logs.php';
    }

    /**
     * Display error logs page.
     */
    public function display_error_logs() {
        include BOOK_NOW_PLUGIN_DIR . 'admin/partials/error-logs.php';
    }

    /**
     * AJAX: Save consultation type.
     */
    public function ajax_save_consultation_type() {
        check_ajax_referer('booknow_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'book-now-kre8iv')));
        }

        $data = array(
            'name'          => sanitize_text_field($_POST['name'] ?? ''),
            'slug'          => sanitize_title($_POST['slug'] ?? ''),
            'description'   => wp_kses_post($_POST['description'] ?? ''),
            'duration'      => absint($_POST['duration'] ?? 30),
            'price'         => floatval($_POST['price'] ?? 0),
            'deposit_amount' => isset($_POST['deposit_amount']) ? floatval($_POST['deposit_amount']) : null,
            'deposit_type'  => sanitize_text_field($_POST['deposit_type'] ?? 'fixed'),
            'category_id'   => isset($_POST['category_id']) ? absint($_POST['category_id']) : null,
            'buffer_before' => absint($_POST['buffer_before'] ?? 0),
            'buffer_after'  => absint($_POST['buffer_after'] ?? 0),
            'status'        => sanitize_text_field($_POST['status'] ?? 'active'),
        );

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

        if ($id) {
            $result = Book_Now_Consultation_Type::update($id, $data);
            $message = __('Consultation type updated successfully.', 'book-now-kre8iv');
        } else {
            $result = Book_Now_Consultation_Type::create($data);
            $message = __('Consultation type created successfully.', 'book-now-kre8iv');
            $id = $result;
        }

        if ($result) {
            wp_send_json_success(array('message' => $message, 'id' => $id));
        } else {
            wp_send_json_error(array('message' => __('Failed to save consultation type.', 'book-now-kre8iv')));
        }
    }

    /**
     * AJAX: Delete consultation type.
     */
    public function ajax_delete_consultation_type() {
        check_ajax_referer('booknow_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'book-now-kre8iv')));
        }

        $id = absint($_POST['id'] ?? 0);

        if (Book_Now_Consultation_Type::delete($id)) {
            wp_send_json_success(array('message' => __('Consultation type deleted successfully.', 'book-now-kre8iv')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete consultation type.', 'book-now-kre8iv')));
        }
    }

    /**
     * AJAX: Get bookings.
     */
    public function ajax_get_bookings() {
        check_ajax_referer('booknow_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'book-now-kre8iv')));
        }

        $args = array(
            'status'    => sanitize_text_field($_POST['status'] ?? ''),
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to'   => sanitize_text_field($_POST['date_to'] ?? ''),
            'limit'     => absint($_POST['limit'] ?? 50),
            'offset'    => absint($_POST['offset'] ?? 0),
        );

        $bookings = Book_Now_Booking::get_all($args);

        wp_send_json_success(array('bookings' => $bookings));
    }

    /**
     * AJAX: Update booking status.
     */
    public function ajax_update_booking_status() {
        check_ajax_referer('booknow_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'book-now-kre8iv')));
        }

        $id = absint($_POST['id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');

        if (Book_Now_Booking::update($id, array('status' => $status))) {
            wp_send_json_success(array('message' => __('Booking status updated successfully.', 'book-now-kre8iv')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update booking status.', 'book-now-kre8iv')));
        }
    }
}
