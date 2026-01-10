<?php
/**
 * Calendar availability test AJAX handler
 *
 * @package BookNow
 */

// AJAX handler for testing calendar availability
add_action('wp_ajax_booknow_test_calendar_availability', 'booknow_test_calendar_availability');

function booknow_test_calendar_availability() {
    check_ajax_referer('booknow_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Permission denied.', 'book-now-kre8iv')));
    }

    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : date('Y-m-d');
    $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '10:00';
    $duration = isset($_POST['duration']) ? absint($_POST['duration']) : 60;

    $calendar_sync = new Book_Now_Calendar_Sync();
    
    // Check availability
    $is_available = $calendar_sync->is_time_available($date, $time, $duration);

    // Get busy times for the day
    $busy_times = $calendar_sync->get_busy_times($date, $date);

    wp_send_json_success(array(
        'available' => $is_available,
        'busy_times' => $busy_times,
        'checked_slot' => array(
            'date' => $date,
            'time' => $time,
            'duration' => $duration,
        ),
    ));
}
