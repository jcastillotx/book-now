<?php
/**
 * Stripe payment integration
 *
 * @package    BookNow
 * @subpackage BookNow/includes
 */

class Book_Now_Stripe {

    /**
     * Stripe API instance
     */
    private $stripe;

    /**
     * Test mode flag
     */
    private $test_mode;

    /**
     * API keys
     */
    private $secret_key;
    private $publishable_key;

    /**
     * Initialize Stripe
     */
    public function __construct() {
        $this->load_settings();
        $this->init_stripe();
    }

    /**
     * Load Stripe settings
     */
    private function load_settings() {
        $payment_settings = get_option('booknow_payment_settings', array());
        
        $this->test_mode = isset($payment_settings['stripe_mode']) && $payment_settings['stripe_mode'] === 'live' ? false : true;
        
        if ($this->test_mode) {
            $this->secret_key = isset($payment_settings['stripe_test_secret_key']) ? $payment_settings['stripe_test_secret_key'] : '';
            $this->publishable_key = isset($payment_settings['stripe_test_publishable_key']) ? $payment_settings['stripe_test_publishable_key'] : '';
        } else {
            $this->secret_key = isset($payment_settings['stripe_live_secret_key']) ? $payment_settings['stripe_live_secret_key'] : '';
            $this->publishable_key = isset($payment_settings['stripe_live_publishable_key']) ? $payment_settings['stripe_live_publishable_key'] : '';
        }
    }

    /**
     * Initialize Stripe API
     */
    private function init_stripe() {
        if (!class_exists('Stripe\Stripe')) {
            require_once BOOK_NOW_PLUGIN_DIR . 'vendor/autoload.php';
        }

        if ($this->secret_key) {
            \Stripe\Stripe::setApiKey($this->secret_key);
        }
    }

    /**
     * Get publishable key
     */
    public function get_publishable_key() {
        return $this->publishable_key;
    }

    /**
     * Check if Stripe is configured
     */
    public function is_configured() {
        return !empty($this->secret_key) && !empty($this->publishable_key);
    }

    /**
     * Create payment intent
     *
     * @param float  $amount Amount in dollars
     * @param string $currency Currency code
     * @param array  $metadata Additional metadata
     * @return array|WP_Error
     */
    public function create_payment_intent($amount, $currency = 'usd', $metadata = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('stripe_not_configured', __('Stripe is not configured.', 'book-now-kre8iv'));
        }

        try {
            // Convert amount to cents
            $amount_cents = intval($amount * 100);

            $intent = \Stripe\PaymentIntent::create([
                'amount' => $amount_cents,
                'currency' => strtolower($currency),
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return array(
                'client_secret' => $intent->client_secret,
                'intent_id' => $intent->id,
                'amount' => $amount,
                'currency' => $currency,
            );

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Retrieve payment intent
     *
     * @param string $intent_id Payment intent ID
     * @return object|WP_Error
     */
    public function get_payment_intent($intent_id) {
        if (!$this->is_configured()) {
            return new WP_Error('stripe_not_configured', __('Stripe is not configured.', 'book-now-kre8iv'));
        }

        try {
            return \Stripe\PaymentIntent::retrieve($intent_id);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Confirm payment intent
     *
     * @param string $intent_id Payment intent ID
     * @return object|WP_Error
     */
    public function confirm_payment($intent_id) {
        if (!$this->is_configured()) {
            return new WP_Error('stripe_not_configured', __('Stripe is not configured.', 'book-now-kre8iv'));
        }

        try {
            $intent = \Stripe\PaymentIntent::retrieve($intent_id);
            
            if ($intent->status === 'requires_confirmation') {
                $intent->confirm();
            }

            return $intent;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Create refund
     *
     * @param string $payment_intent_id Payment intent ID
     * @param float  $amount            Amount to refund (null for full refund)
     * @param string $reason            Refund reason
     * @return object|WP_Error
     */
    public function create_refund($payment_intent_id, $amount = null, $reason = 'requested_by_customer') {
        if (!$this->is_configured()) {
            return new WP_Error('stripe_not_configured', __('Stripe is not configured.', 'book-now-kre8iv'));
        }

        try {
            $refund_data = array(
                'payment_intent' => $payment_intent_id,
                'reason' => $reason,
            );

            if ($amount !== null) {
                $refund_data['amount'] = intval($amount * 100);
            }

            $refund = \Stripe\Refund::create($refund_data);

            return $refund;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Test connection to Stripe
     *
     * @return bool|WP_Error
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return new WP_Error('stripe_not_configured', __('Stripe API keys are not configured.', 'book-now-kre8iv'));
        }

        try {
            // Try to retrieve account info
            $account = \Stripe\Account::retrieve();
            
            return array(
                'success' => true,
                'mode' => $this->test_mode ? 'test' : 'live',
                'account_id' => $account->id,
                'email' => $account->email,
            );
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new WP_Error('stripe_connection_failed', $e->getMessage());
        }
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload Webhook payload
     * @param string $signature Stripe signature header
     * @return object|WP_Error
     */
    public function verify_webhook($payload, $signature) {
        $webhook_secret = get_option('booknow_stripe_webhook_secret', '');

        if (empty($webhook_secret)) {
            return new WP_Error('webhook_not_configured', __('Webhook secret is not configured.', 'book-now-kre8iv'));
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $webhook_secret
            );

            return $event;
        } catch (\UnexpectedValueException $e) {
            return new WP_Error('invalid_payload', $e->getMessage());
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new WP_Error('invalid_signature', $e->getMessage());
        }
    }

    /**
     * Handle webhook event
     *
     * @param object $event Stripe event object
     */
    public function handle_webhook_event($event) {
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handle_payment_succeeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handle_payment_failed($event->data->object);
                break;

            case 'charge.refunded':
                $this->handle_refund($event->data->object);
                break;

            case 'charge.dispute.created':
                $this->handle_dispute($event->data->object);
                break;
        }
    }

    /**
     * Handle successful payment
     *
     * @param object $payment_intent Payment intent object
     */
    private function handle_payment_succeeded($payment_intent) {
        $booking_id = isset($payment_intent->metadata->booking_id) ? $payment_intent->metadata->booking_id : null;

        if ($booking_id) {
            Book_Now_Booking::update($booking_id, array(
                'payment_status' => 'paid',
                'payment_intent_id' => $payment_intent->id,
                'status' => 'confirmed',
            ));

            // TODO: Send confirmation email (Phase 6)
            do_action('booknow_payment_succeeded', $booking_id, $payment_intent);
        }
    }

    /**
     * Handle failed payment
     *
     * @param object $payment_intent Payment intent object
     */
    private function handle_payment_failed($payment_intent) {
        $booking_id = isset($payment_intent->metadata->booking_id) ? $payment_intent->metadata->booking_id : null;

        if ($booking_id) {
            Book_Now_Booking::update($booking_id, array(
                'payment_status' => 'failed',
                'payment_intent_id' => $payment_intent->id,
            ));

            do_action('booknow_payment_failed', $booking_id, $payment_intent);
        }
    }

    /**
     * Handle refund
     *
     * @param object $charge Charge object
     */
    private function handle_refund($charge) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        // Find booking by payment intent
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE payment_intent_id = %s",
            $charge->payment_intent
        ));

        if ($booking) {
            Book_Now_Booking::update($booking->id, array(
                'payment_status' => 'refunded',
            ));

            do_action('booknow_payment_refunded', $booking->id, $charge);
        }
    }

    /**
     * Handle dispute
     *
     * @param object $dispute Dispute object
     */
    private function handle_dispute($dispute) {
        // Log dispute for admin review
        error_log('Stripe Dispute Created: ' . $dispute->id);
        
        do_action('booknow_payment_dispute', $dispute);
    }
}
