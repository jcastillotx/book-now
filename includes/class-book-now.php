<?php
/**
 * The core plugin class
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @var Book_Now_Loader
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @var string
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @var string
     */
    protected $version;

    /**
     * Calendar sync instance - must be stored to persist action hooks.
     *
     * @var Book_Now_Calendar_Sync
     */
    protected $calendar_sync;

    /**
     * Initialize the core plugin.
     */
    public function __construct() {
        $this->version = BOOK_NOW_VERSION;
        $this->plugin_name = 'book-now-kre8iv';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_rest_api();
    }

    /**
     * Initialize REST API and integration classes.
     *
     * Note: Calendar_Sync MUST be stored as a class property to prevent
     * garbage collection from breaking the auto-sync action hooks.
     */
    private function define_rest_api() {
        new Book_Now_REST_API();
        new Book_Now_Webhook();

        // Store Calendar_Sync instance - this is critical!
        // The hooks registered in the constructor use $this references.
        // If the instance is garbage collected, auto-sync breaks silently.
        $this->calendar_sync = new Book_Now_Calendar_Sync();

        new Book_Now_SMTP();
        new Book_Now_Email();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Core classes - Load loader and i18n first
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-loader.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-i18n.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/helpers.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-logger.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-email-log.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-error-log.php';

        // Model classes (needed by other classes)
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-consultation-type.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-category.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-booking.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-availability.php';

        // Security classes
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-rate-limiter.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-encryption.php';

        // Integration classes
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-stripe.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-notifications.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-google-calendar.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-microsoft-calendar.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-calendar-sync.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-smtp.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-email.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-webhook.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-rest-api.php';

        // Admin classes
        require_once BOOK_NOW_PLUGIN_DIR . 'admin/class-book-now-admin.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'admin/class-book-now-setup-wizard.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'admin/class-book-now-dashboard-widgets.php';

        // Public classes
        require_once BOOK_NOW_PLUGIN_DIR . 'public/class-book-now-public.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'public/class-book-now-public-ajax.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'public/class-book-now-shortcodes.php';

        // Initialize the loader
        $this->loader = new Book_Now_Loader();

        // Set up reminder cron
        $this->setup_cron();
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale() {
        $plugin_i18n = new Book_Now_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        $plugin_admin = new Book_Now_Admin($this->get_plugin_name(), $this->get_version());
        new Book_Now_Setup_Wizard();
        new Book_Now_Dashboard_Widgets();

        // Enqueue admin assets
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');

        // Admin AJAX actions
        $this->loader->add_action('wp_ajax_booknow_save_consultation_type', $plugin_admin, 'ajax_save_consultation_type');
        $this->loader->add_action('wp_ajax_booknow_delete_consultation_type', $plugin_admin, 'ajax_delete_consultation_type');
        $this->loader->add_action('wp_ajax_booknow_get_bookings', $plugin_admin, 'ajax_get_bookings');
        $this->loader->add_action('wp_ajax_booknow_update_booking_status', $plugin_admin, 'ajax_update_booking_status');
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        $plugin_public = new Book_Now_Public($this->get_plugin_name(), $this->get_version());

        // Enqueue public assets
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        // Register shortcodes
        $shortcodes = new Book_Now_Shortcodes();
        add_shortcode('book_now_form', array($shortcodes, 'render_booking_form'));
        add_shortcode('book_now_calendar', array($shortcodes, 'render_calendar'));
        add_shortcode('book_now_list', array($shortcodes, 'render_list_view'));
        add_shortcode('book_now_types', array($shortcodes, 'render_consultation_types'));

        // Initialize public AJAX handlers
        new Book_Now_Public_AJAX();
        $this->loader->add_action('wp_ajax_booknow_get_availability', $plugin_public, 'ajax_get_availability');
        $this->loader->add_action('wp_ajax_nopriv_booknow_get_availability', $plugin_public, 'ajax_get_availability');

        $this->loader->add_action('wp_ajax_booknow_create_booking', $plugin_public, 'ajax_create_booking');
        $this->loader->add_action('wp_ajax_nopriv_booknow_create_booking', $plugin_public, 'ajax_create_booking');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin.
     *
     * @return string
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks.
     *
     * @return Book_Now_Loader
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Set up cron jobs.
     */
    private function setup_cron() {
        // Schedule reminder emails
        if (!wp_next_scheduled('booknow_send_reminders')) {
            wp_schedule_event(time(), 'hourly', 'booknow_send_reminders');
        }

        // Hook the reminder function
        add_action('booknow_send_reminders', array($this, 'send_booking_reminders'));
    }

    /**
     * Send booking reminders.
     */
    public function send_booking_reminders() {
        $email_settings = booknow_get_setting('email');
        $reminder_hours = $email_settings['reminder_hours'] ?? 24;
        
        $bookings = Book_Now_Booking::get_upcoming_for_reminders($reminder_hours);
        
        foreach ($bookings as $booking) {
            Book_Now_Notifications::send_booking_reminder($booking);
            
            Book_Now_Booking::update($booking->id, array(
                'reminder_sent' => 1,
                'reminder_sent_at' => current_time('mysql')
            ));
        }
    }
}
