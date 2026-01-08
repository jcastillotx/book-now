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

// Get all bookings
$bookings = Book_Now_Booking::get_all(array('limit' => 100));
?>

<div class="wrap">
    <h1><?php esc_html_e('Bookings', 'book-now-kre8iv'); ?></h1>

    <?php if (!empty($bookings)) : ?>
        <table class="wp-list-table widefat fixed striped">
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
                        <td><strong><?php echo esc_html($booking->reference_number); ?></strong></td>
                        <td>
                            <?php echo esc_html($booking->customer_name); ?><br>
                            <small><?php echo esc_html($booking->customer_email); ?></small>
                        </td>
                        <td>
                            <?php echo esc_html(booknow_format_date($booking->booking_date)); ?><br>
                            <small><?php echo esc_html(booknow_format_time($booking->booking_time)); ?></small>
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
                        <td><?php echo $booking->payment_amount ? esc_html(booknow_format_price($booking->payment_amount)) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php esc_html_e('No bookings found.', 'book-now-kre8iv'); ?></p>
    <?php endif; ?>
</div>
