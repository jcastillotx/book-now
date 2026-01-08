<?php
/**
 * Fired during plugin activation
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now_Activator {

    /**
     * Activate the plugin.
     *
     * Creates database tables, sets default options, and flushes rewrite rules.
     *
     * @since 1.0.0
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();

        // Set plugin version
        update_option('booknow_version', BOOK_NOW_VERSION);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create plugin database tables.
     *
     * @since 1.0.0
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Consultation Types Table
        $table_name = $wpdb->prefix . 'booknow_consultation_types';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            duration int(11) NOT NULL DEFAULT 30,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            deposit_amount decimal(10,2) DEFAULT NULL,
            deposit_type enum('fixed','percentage') DEFAULT 'fixed',
            category_id bigint(20) unsigned DEFAULT NULL,
            stripe_product_id varchar(255) DEFAULT NULL,
            stripe_price_id varchar(255) DEFAULT NULL,
            buffer_before int(11) DEFAULT 0,
            buffer_after int(11) DEFAULT 0,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY category_id (category_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql);

        // Bookings Table
        $table_name = $wpdb->prefix . 'booknow_bookings';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            reference_number varchar(20) NOT NULL,
            consultation_type_id bigint(20) unsigned NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50) DEFAULT NULL,
            customer_notes text,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            duration int(11) NOT NULL,
            timezone varchar(100) DEFAULT 'UTC',
            status enum('pending','confirmed','completed','cancelled','no-show') DEFAULT 'pending',
            payment_status enum('pending','paid','refunded','failed') DEFAULT 'pending',
            payment_amount decimal(10,2) DEFAULT NULL,
            payment_intent_id varchar(255) DEFAULT NULL,
            payment_date datetime DEFAULT NULL,
            google_event_id varchar(255) DEFAULT NULL,
            microsoft_event_id varchar(255) DEFAULT NULL,
            reminder_sent tinyint(1) DEFAULT 0,
            reminder_sent_at datetime DEFAULT NULL,
            admin_notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY reference_number (reference_number),
            KEY consultation_type_id (consultation_type_id),
            KEY customer_email (customer_email),
            KEY booking_date (booking_date),
            KEY status (status),
            KEY payment_status (payment_status)
        ) $charset_collate;";
        dbDelta($sql);

        // Availability Rules Table
        $table_name = $wpdb->prefix . 'booknow_availability';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            rule_type enum('weekly','specific_date','block') DEFAULT 'weekly',
            day_of_week tinyint(1) DEFAULT NULL COMMENT '0=Sunday,6=Saturday',
            specific_date date DEFAULT NULL,
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            is_available tinyint(1) DEFAULT 1,
            consultation_type_id bigint(20) unsigned DEFAULT NULL,
            priority int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY rule_type (rule_type),
            KEY day_of_week (day_of_week),
            KEY specific_date (specific_date),
            KEY consultation_type_id (consultation_type_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Categories Table
        $table_name = $wpdb->prefix . 'booknow_categories';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            parent_id bigint(20) unsigned DEFAULT NULL,
            display_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Email Log Table (optional, for tracking)
        $table_name = $wpdb->prefix . 'booknow_email_log';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) unsigned NOT NULL,
            email_type enum('confirmation','reminder','cancellation','admin_notification') NOT NULL,
            recipient_email varchar(255) NOT NULL,
            subject varchar(500) NOT NULL,
            status enum('sent','failed') DEFAULT 'sent',
            error_message text,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY email_type (email_type),
            KEY recipient_email (recipient_email)
        ) $charset_collate;";
        dbDelta($sql);

        // Team Members Table (for multi-user/agency support)
        $table_name = $wpdb->prefix . 'booknow_team_members';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            bio text,
            photo_url varchar(500) DEFAULT NULL,
            status enum('active','inactive') DEFAULT 'active',
            google_calendar_id varchar(255) DEFAULT NULL,
            microsoft_calendar_id varchar(255) DEFAULT NULL,
            display_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY email (email),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql);
    }

    /**
     * Set default plugin options.
     *
     * @since 1.0.0
     */
    private static function set_default_options() {
        // General settings
        $default_general = array(
            'business_name'     => get_bloginfo('name'),
            'timezone'          => get_option('timezone_string') ?: 'UTC',
            'currency'          => 'USD',
            'date_format'       => 'F j, Y',
            'time_format'       => 'g:i a',
            'slot_interval'     => 30,
            'min_booking_notice' => 24,
            'max_booking_advance' => 90,
            'account_type'      => 'single',
            'enable_team_members' => false,
        );
        add_option('booknow_general_settings', $default_general);

        // Setup wizard status
        add_option('booknow_setup_wizard_completed', false);
        add_option('booknow_setup_wizard_redirect', true);

        // Payment settings
        $default_payment = array(
            'stripe_mode'       => 'test',
            'stripe_test_publishable_key' => '',
            'stripe_test_secret_key' => '',
            'stripe_live_publishable_key' => '',
            'stripe_live_secret_key' => '',
            'payment_required'  => true,
            'allow_deposit'     => false,
        );
        add_option('booknow_payment_settings', $default_payment);

        // Email settings
        $default_email = array(
            'from_name'         => get_bloginfo('name'),
            'from_email'        => get_option('admin_email'),
            'admin_notification' => true,
            'admin_email'       => get_option('admin_email'),
            'reminder_hours'    => 24,
        );
        add_option('booknow_email_settings', $default_email);

        // Integration settings
        $default_integration = array(
            'google_calendar_enabled' => false,
            'microsoft_calendar_enabled' => false,
        );
        add_option('booknow_integration_settings', $default_integration);
    }
}
