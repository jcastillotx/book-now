<?php
/**
 * Admin dashboard page
 *
 * @package BookNow
 * @since   1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Security check - verify user has admin capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'book-now-kre8iv'));
}

// Get statistics
$stats = Book_Now_Booking::get_stats();
$consultation_types_count = Book_Now_Consultation_Type::count_by_status('active');
?>

<div class="wrap">
    <h1><?php esc_html_e('Book Now Dashboard', 'book-now-kre8iv'); ?></h1>

    <div class="booknow-dashboard">
        <!-- Statistics Cards -->
        <div class="booknow-stats-row">
            <div class="booknow-stat-card">
                <div class="stat-icon dashicons dashicons-calendar-alt"></div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['total']); ?></h3>
                    <p><?php esc_html_e('Total Bookings', 'book-now-kre8iv'); ?></p>
                </div>
            </div>

            <div class="booknow-stat-card">
                <div class="stat-icon dashicons dashicons-clock"></div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['pending']); ?></h3>
                    <p><?php esc_html_e('Pending', 'book-now-kre8iv'); ?></p>
                </div>
            </div>

            <div class="booknow-stat-card">
                <div class="stat-icon dashicons dashicons-yes"></div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['confirmed']); ?></h3>
                    <p><?php esc_html_e('Confirmed', 'book-now-kre8iv'); ?></p>
                </div>
            </div>

            <div class="booknow-stat-card">
                <div class="stat-icon dashicons dashicons-admin-post"></div>
                <div class="stat-content">
                    <h3><?php echo esc_html($consultation_types_count); ?></h3>
                    <p><?php esc_html_e('Active Types', 'book-now-kre8iv'); ?></p>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="booknow-dashboard-section">
            <h2><?php esc_html_e('Recent Bookings', 'book-now-kre8iv'); ?></h2>
            <?php
            $recent_bookings = Book_Now_Booking::get_all(array(
                'limit'   => 10,
                'orderby' => 'created_at',
                'order'   => 'DESC',
            ));

            if (!empty($recent_bookings)) :
            ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Reference', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Customer', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Date & Time', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Status', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Payment', 'book-now-kre8iv'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking) : ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings&id=' . $booking->id)); ?>">
                                            <?php echo esc_html($booking->reference_number); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td>
                                    <?php echo esc_html($booking->customer_name); ?><br>
                                    <small><?php echo esc_html($booking->customer_email); ?></small>
                                </td>
                                <td>
                                    <?php echo esc_html(booknow_format_date($booking->booking_date)); ?><br>
                                    <small><?php echo esc_html(booknow_format_time($booking->booking_time)); ?></small>
                                </td>
                                <td>
                                    <span class="booknow-status-badge status-<?php echo esc_attr($booking->status); ?>">
                                        <?php echo esc_html(booknow_get_status_label($booking->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="booknow-payment-badge payment-<?php echo esc_attr($booking->payment_status); ?>">
                                        <?php echo esc_html(booknow_get_payment_status_label($booking->payment_status)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No bookings yet.', 'book-now-kre8iv'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Quick Links -->
        <div class="booknow-dashboard-section">
            <h2><?php esc_html_e('Quick Links', 'book-now-kre8iv'); ?></h2>
            <div class="booknow-quick-links">
                <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-types')); ?>" class="button button-primary">
                    <?php esc_html_e('Add Consultation Type', 'book-now-kre8iv'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-availability')); ?>" class="button">
                    <?php esc_html_e('Set Availability', 'book-now-kre8iv'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-settings')); ?>" class="button">
                    <?php esc_html_e('Configure Settings', 'book-now-kre8iv'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
