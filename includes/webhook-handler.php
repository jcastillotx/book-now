<?php
/**
 * Stripe Webhook Handler
 *
 * This file handles incoming Stripe webhooks.
 * URL: https://yoursite.com/wp-content/plugins/book-now/includes/webhook-handler.php
 *
 * @package BookNow
 * @since   1.0.0
 */

// Load WordPress
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
} else {
    http_response_code(500);
    die('WordPress not found');
}

// Verify this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Get the payload
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($payload) || empty($sig_header)) {
    http_response_code(400);
    die('Invalid request');
}

// Load Stripe class
require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-stripe.php';

// Initialize Stripe
$stripe = new Book_Now_Stripe();

// Handle the webhook
$result = $stripe->handle_webhook($payload, $sig_header);

if (is_wp_error($result)) {
    error_log('Book Now Webhook Error: ' . $result->get_error_message());
    http_response_code(400);
    die($result->get_error_message());
}

// Success
http_response_code(200);
echo json_encode(array('success' => true));
