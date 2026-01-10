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
     * Initialize the core plugin.
     */
    public function __construct() {
        $this->version = BOOK_NOW_VERSION;
        $this->plugin_name = 'book-now-kre8iv';

        $this->loader = new Book_Now_Loader();

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_rest_api();
    }

    /**
     * Initialize REST API.
     */
    private function define_rest_api() {
        new Book_Now_REST_API();
        new Book_Now_Webhook();
        new Book_Now_Calendar_Sync();
        new Book_Now_SMTP();
        new Book_Now_Email();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Core classes
        require_once BOOK_NOW_PLUGIN_DIR . 'admin/class-book-now-setup-wizard.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-rest-api.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-stripe.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-webhook.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-google-calendar.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-microsoft-calendar.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-calendar-sync.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-smtp.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-email.php';

        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-loader.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/helpers.php';

        // Admin classes
        require_once BOOK_NOW_PLUGIN_DIR . 'admin/class-book-now-admin.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'admin/class-book-now-setup-wizard.php';

        // Public classes
        require_once BOOK_NOW_PLUGIN_DIR . 'public/class-book-now-public.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'public/class-book-now-public-ajax.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'public/class-book-now-shortcodes.php';

        // Model classes
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-consultation-type.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-booking.php';
        require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-availability.php';

        // Initialize the loader
        $this->loader = new Book_Now_Loader();
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
}
