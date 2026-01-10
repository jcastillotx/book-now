<?php
/**
 * Stripe test connection AJAX handler
 *
 * @package BookNow
 */

// AJAX handler for testing Stripe connection
add_action('wp_ajax_booknow_test_stripe', 'booknow_test_stripe_connection');

function booknow_test_stripe_connection() {
    check_ajax_referer('booknow_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Permission denied.', 'book-now-kre8iv')));
    }

    $stripe = new Book_Now_Stripe();
    $result = $stripe->test_connection();

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }

    wp_send_json_success($result);
}
