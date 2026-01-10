<?php
/**
 * Webhook handler for Stripe
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 */

class Book_Now_Webhook {

    /**
     * Initialize webhook handler
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
    }

    /**
     * Register webhook REST endpoint
     */
    public function register_webhook_endpoint() {
        register_rest_route('book-now/v1', '/webhook/stripe', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_stripe_webhook'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Handle Stripe webhook
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function handle_stripe_webhook($request) {
        $payload = $request->get_body();
        $signature = $request->get_header('stripe-signature');

        if (!$signature) {
            return new WP_REST_Response(array(
                'error' => 'Missing signature'
            ), 400);
        }

        // Initialize Stripe handler
        $stripe = new Book_Now_Stripe();

        // Verify webhook signature
        $event = $stripe->verify_webhook($payload, $signature);

        if (is_wp_error($event)) {
            return new WP_REST_Response(array(
                'error' => $event->get_error_message()
            ), 400);
        }

        // Log webhook event
        $this->log_webhook($event);

        // Handle the event
        $stripe->handle_webhook_event($event);

        return new WP_REST_Response(array(
            'received' => true
        ), 200);
    }

    /**
     * Log webhook event
     *
     * @param object $event Stripe event
     */
    private function log_webhook($event) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_webhook_log';

        // Create table if it doesn't exist
        $this->maybe_create_log_table();

        $wpdb->insert($table, array(
            'event_id' => $event->id,
            'event_type' => $event->type,
            'payload' => json_encode($event->data->object),
            'created_at' => current_time('mysql'),
        ));
    }

    /**
     * Create webhook log table if needed
     */
    private function maybe_create_log_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_webhook_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id varchar(255) NOT NULL,
            event_type varchar(100) NOT NULL,
            payload longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY event_type (event_type)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
