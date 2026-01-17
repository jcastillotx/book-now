<?php
/**
 * Admin bookings list page
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

// Get all bookings
$bookings = Book_Now_Booking::get_all(array('limit' => 100));
?>

<div class="booknow-wrap">
    <div class="booknow-page-header">
        <h1>
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php esc_html_e('Bookings', 'book-now-kre8iv'); ?>
        </h1>
    </div>

    <div class="booknow-card">
        <div class="booknow-card-body">
            <?php if (!empty($bookings)) : ?>
                <table class="wp-list-table widefat fixed striped booknow-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Reference', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Customer', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Date & Time', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Type', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Status', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Payment', 'book-now-kre8iv'); ?></th>
                            <th><?php esc_html_e('Amount', 'book-now-kre8iv'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking) :
                            $type = Book_Now_Consultation_Type::get_by_id($booking->consultation_type_id);
                        ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings&id=' . $booking->id)); ?>">
                                            <?php echo esc_html($booking->reference_number); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td>
                                    <span class="booknow-customer-name"><?php echo esc_html($booking->customer_name); ?></span>
                                    <span class="booknow-customer-email"><?php echo esc_html($booking->customer_email); ?></span>
                                </td>
                                <td>
                                    <span class="booknow-date"><?php echo esc_html(booknow_format_date($booking->booking_date)); ?></span>
                                    <span class="booknow-time"><?php echo esc_html(booknow_format_time($booking->booking_time)); ?></span>
                                </td>
                                <td><?php echo $type ? esc_html($type->name) : '-'; ?></td>
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
                                <td class="booknow-amount"><?php echo $booking->payment_amount ? esc_html(booknow_format_price($booking->payment_amount)) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="booknow-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('No bookings found.', 'book-now-kre8iv'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
