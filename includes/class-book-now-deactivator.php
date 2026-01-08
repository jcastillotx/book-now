<?php
/**
 * Fired during plugin deactivation
 *
 * @package BookNow
 * @since   1.0.0
 */

class Book_Now_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Clears scheduled events and flushes rewrite rules.
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('booknow_send_reminders');
        wp_clear_scheduled_hook('booknow_cleanup_pending_bookings');

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
