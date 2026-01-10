<?php
/**
 * Fired when the plugin is uninstalled
 *
 * @package BookNow
 * @since   1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to delete data on uninstall
$delete_data = get_option('booknow_delete_data_on_uninstall', false);

if ($delete_data) {
    global $wpdb;

    // Drop custom tables
    $tables = array(
        $wpdb->prefix . 'booknow_bookings',
        $wpdb->prefix . 'booknow_consultation_types',
        $wpdb->prefix . 'booknow_availability',
        $wpdb->prefix . 'booknow_categories',
        $wpdb->prefix . 'booknow_email_log',
        $wpdb->prefix . 'booknow_team_members',
    );

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    // Delete plugin options
    $options = array(
        'booknow_version',
        'booknow_general_settings',
        'booknow_payment_settings',
        'booknow_email_settings',
        'booknow_integration_settings',
        'booknow_delete_data_on_uninstall',
        'booknow_setup_wizard_completed',
        'booknow_setup_wizard_redirect',
    );

    foreach ($options as $option) {
        delete_option($option);
    }

    // Clear scheduled events
    wp_clear_scheduled_hook('booknow_send_reminders');
    wp_clear_scheduled_hook('booknow_cleanup_pending_bookings');

    // Flush rewrite rules
    flush_rewrite_rules();
}
