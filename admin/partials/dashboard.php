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

<div class="booknow-wrap">
    <h1>
        <span class="dashicons dashicons-dashboard"></span>
        <?php esc_html_e('Book Now Dashboard', 'book-now-kre8iv'); ?>
    </h1>

    <div class="booknow-dashboard">
        <!-- Statistics Cards -->
        <div class="booknow-stats-row">
            <div class="booknow-stat-card booknow-stat-primary">
                <div class="stat-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo esc_html($stats['total']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Total Bookings', 'book-now-kre8iv'); ?></span>
                </div>
            </div>

            <div class="booknow-stat-card booknow-stat-warning">
                <div class="stat-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo esc_html($stats['pending']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Pending', 'book-now-kre8iv'); ?></span>
                </div>
            </div>

            <div class="booknow-stat-card booknow-stat-success">
                <div class="stat-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo esc_html($stats['confirmed']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Confirmed', 'book-now-kre8iv'); ?></span>
                </div>
            </div>

            <div class="booknow-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-list-view"></span>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo esc_html($consultation_types_count); ?></span>
                    <span class="stat-label"><?php esc_html_e('Active Types', 'book-now-kre8iv'); ?></span>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="booknow-card">
            <div class="booknow-card-header">
                <h2>
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Recent Bookings', 'book-now-kre8iv'); ?>
                </h2>
            </div>
            <div class="booknow-card-body">
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
                <div class="booknow-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('No bookings yet.', 'book-now-kre8iv'); ?></p>
                </div>
            <?php endif; ?>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="booknow-card">
            <div class="booknow-card-header">
                <h2>
                    <span class="dashicons dashicons-admin-links"></span>
                    <?php esc_html_e('Quick Links', 'book-now-kre8iv'); ?>
                </h2>
            </div>
            <div class="booknow-card-body">
                <div class="booknow-quick-links">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-types')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e('Add Consultation Type', 'book-now-kre8iv'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-availability')); ?>" class="button">
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e('Set Availability', 'book-now-kre8iv'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-settings')); ?>" class="button">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php esc_html_e('Configure Settings', 'book-now-kre8iv'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
