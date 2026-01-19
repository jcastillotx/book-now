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

// Check if viewing a single booking
$booking_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

// Get notice from redirect if present (actions are handled in Book_Now_Admin::handle_booking_actions)
if (isset($_GET['booknow_notice'])) {
    $notice = sanitize_text_field(wp_unslash($_GET['booknow_notice']));
    $notice_type = isset($_GET['booknow_notice_type']) ? sanitize_text_field($_GET['booknow_notice_type']) : 'info';
}

// Handle booking actions (confirm, cancel, etc.)
if ($booking_id && isset($_GET['action']) && isset($_GET['_wpnonce'])) {
    $action = sanitize_text_field($_GET['action']);

    if (wp_verify_nonce($_GET['_wpnonce'], 'booknow_booking_action_' . $booking_id)) {

        $existing_booking = Book_Now_Booking::get($booking_id);

        if ($existing_booking) {
            switch ($action) {
                case 'confirm':
                    $old_booking = $existing_booking;
                    Book_Now_Booking::update($booking_id, array('status' => 'confirmed'));
                    do_action('booknow_booking_confirmed', $booking_id);
                    $notice = __('Booking confirmed successfully. Calendar sync triggered.', 'book-now-kre8iv');
                    $notice_type = 'success';
                    break;
                case 'cancel':
                    Book_Now_Booking::update($booking_id, array('status' => 'cancelled'));
                    do_action('booknow_booking_cancelled', $booking_id);
                    $notice = __('Booking cancelled.', 'book-now-kre8iv');
                    $notice_type = 'warning';
                    break;
                case 'complete':
                    Book_Now_Booking::update($booking_id, array('status' => 'completed'));
                    $notice = __('Booking marked as completed.', 'book-now-kre8iv');
                    $notice_type = 'success';
                    break;
                case 'resend_email':
                    $email = new Book_Now_Email();
                    $sent = $email->send_confirmation_email($booking_id);
                    $notice = $sent ? __('Confirmation email sent.', 'book-now-kre8iv') : __('Failed to send email. Check email settings.', 'book-now-kre8iv');
                    $notice_type = $sent ? 'success' : 'error';
                    break;
                case 'sync_calendar':
                    try {
                        // Load dependencies required by calendar classes
                        if (!class_exists('Book_Now_Encryption')) {
                            require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-encryption.php';
                        }
                        if (!class_exists('Book_Now_Logger')) {
                            require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-logger.php';
                        }

                        // Calendar classes are loaded by the main plugin, but ensure they exist
                        if (!class_exists('Book_Now_Calendar_Sync')) {
                            require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-calendar-sync.php';
                        }
                        if (!class_exists('Book_Now_Google_Calendar')) {
                            require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-google-calendar.php';
                        }
                        if (!class_exists('Book_Now_Microsoft_Calendar')) {
                            require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-microsoft-calendar.php';
                        }
                        // Ensure consultation type class is loaded as it might be needed for event description
                        if (!class_exists('Book_Now_Consultation_Type')) {
                            require_once BOOK_NOW_PLUGIN_DIR . 'includes/class-book-now-consultation-type.php';
                        }

                        $calendar_sync = new Book_Now_Calendar_Sync();
                        $results = $calendar_sync->manual_sync($booking_id);

                        if (empty($results)) {
                            $notice = __('No calendars configured or authenticated.', 'book-now-kre8iv');
                            $notice_type = 'warning';
                        } elseif (isset($results['error'])) {
                            $notice = $results['error'];
                            $notice_type = 'error';
                        } else {
                            $success_msgs = array();
                            $error_msgs = array();

                            foreach ($results as $provider => $status) {
                                if ($status === 'error') {
                                    $error_msgs[] = sprintf(__('Sync with %s failed. Please check logs or re-authenticate.', 'book-now-kre8iv'), ucfirst($provider));
                                } else {
                                    $success_msgs[] = sprintf(__('Sync with %s successful (%s).', 'book-now-kre8iv'), ucfirst($provider), $status);
                                }
                            }

                            if (!empty($error_msgs)) {
                                $notice = implode(' ', array_merge($error_msgs, $success_msgs));
                                $notice_type = !empty($success_msgs) ? 'warning' : 'error';
                            } else {
                                $notice = implode(' ', $success_msgs);
                                $notice_type = 'success';
                            }
                        }
                    } catch (Throwable $e) {
                        Book_Now_Logger::error('Calendar sync failed', array(
                            'booking_id' => $booking_id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ));
                        $notice = sprintf(__('Calendar sync encountered an error: %s', 'book-now-kre8iv'), $e->getMessage());
                        $notice_type = 'error';
                    }
                    break;
            }
        } else {
            $notice = __('Booking not found.', 'book-now-kre8iv');
            $notice_type = 'error';
        }
    } else {
        // Nonce verification failed; inform the user instead of failing silently.
        $notice = __('Security check failed. The requested action could not be completed. Please try again.', 'book-now-kre8iv');
        $notice_type = 'error';
    }

    // Redirect to a clean URL to prevent action re-execution on page refresh (PRG pattern).
    $redirect_url = remove_query_arg(
        array('action', '_wpnonce', 'booknow_notice', 'booknow_notice_type')
    );

    if (!empty($notice) && !empty($notice_type)) {
        $redirect_url = add_query_arg(
            array(
                'booknow_notice' => rawurlencode($notice),
                'booknow_notice_type' => $notice_type,
            ),
            $redirect_url
        );
    }

    wp_redirect($redirect_url);
    exit;
}

// Get single booking or all bookings
if ($booking_id) {
    $booking = Book_Now_Booking::get($booking_id);
} else {
    $bookings = Book_Now_Booking::get_all(array('limit' => 100));
}
?>

<div class="booknow-wrap">
    <div class="booknow-page-header">
        <h1>
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php esc_html_e('Bookings', 'book-now-kre8iv'); ?>
        </h1>
    </div>

    <?php if (isset($notice)): ?>
        <div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible">
            <p><?php echo esc_html($notice); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($booking_id && $booking):
        // Single booking detail view
        $type = Book_Now_Consultation_Type::get_by_id($booking->consultation_type_id);
        $confirm_nonce = wp_create_nonce('booknow_booking_action_' . $booking->id);
        ?>
        <div class="booknow-card">
            <div class="booknow-card-header">
                <h2>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings')); ?>"
                        class="booknow-back-link">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                    </a>
                    <?php printf(esc_html__('Booking: %s', 'book-now-kre8iv'), esc_html($booking->reference_number)); ?>
                </h2>
                <div class="booknow-booking-actions">
                    <?php if ($booking->status === 'pending'): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings&id=' . $booking->id . '&action=confirm&_wpnonce=' . $confirm_nonce)); ?>"
                            class="button button-primary">
                            <span class="dashicons dashicons-yes"></span> <?php esc_html_e('Confirm', 'book-now-kre8iv'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (!in_array($booking->status, array('cancelled', 'completed'))): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings&id=' . $booking->id . '&action=cancel&_wpnonce=' . $confirm_nonce)); ?>"
                            class="button"
                            onclick="return confirm('<?php esc_attr_e('Are you sure you want to cancel this booking?', 'book-now-kre8iv'); ?>');">
                            <span class="dashicons dashicons-no"></span> <?php esc_html_e('Cancel', 'book-now-kre8iv'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($booking->status === 'confirmed'): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings&id=' . $booking->id . '&action=complete&_wpnonce=' . $confirm_nonce)); ?>"
                            class="button">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e('Mark Complete', 'book-now-kre8iv'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings&id=' . $booking->id . '&action=resend_email&_wpnonce=' . $confirm_nonce)); ?>"
                        class="button">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php esc_html_e('Resend Email', 'book-now-kre8iv'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings&id=' . $booking->id . '&action=sync_calendar&_wpnonce=' . $confirm_nonce)); ?>"
                        class="button">
                        <span class="dashicons dashicons-calendar"></span>
                        <?php esc_html_e('Sync Calendar', 'book-now-kre8iv'); ?>
                    </a>
                </div>
            </div>
            <div class="booknow-card-body">
                <div class="booknow-booking-detail-grid">
                    <div class="booknow-detail-section">
                        <h3><?php esc_html_e('Booking Information', 'book-now-kre8iv'); ?></h3>
                        <table class="booknow-detail-table">
                            <tr>
                                <th><?php esc_html_e('Reference', 'book-now-kre8iv'); ?></th>
                                <td><strong><?php echo esc_html($booking->reference_number); ?></strong></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Status', 'book-now-kre8iv'); ?></th>
                                <td>
                                    <span class="booknow-status-badge status-<?php echo esc_attr($booking->status); ?>">
                                        <?php echo esc_html(booknow_get_status_label($booking->status)); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Consultation Type', 'book-now-kre8iv'); ?></th>
                                <td><?php echo $type ? esc_html($type->name) : '-'; ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Date', 'book-now-kre8iv'); ?></th>
                                <td><?php echo esc_html(booknow_format_date($booking->booking_date)); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Time', 'book-now-kre8iv'); ?></th>
                                <td><?php echo esc_html(booknow_format_time($booking->booking_time)); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Duration', 'book-now-kre8iv'); ?></th>
                                <td><?php echo esc_html($booking->duration); ?>
                                    <?php esc_html_e('minutes', 'book-now-kre8iv'); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Created', 'book-now-kre8iv'); ?></th>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->created_at))); ?>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="booknow-detail-section">
                        <h3><?php esc_html_e('Customer Information', 'book-now-kre8iv'); ?></h3>
                        <table class="booknow-detail-table">
                            <tr>
                                <th><?php esc_html_e('Name', 'book-now-kre8iv'); ?></th>
                                <td><?php echo esc_html($booking->customer_name); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Email', 'book-now-kre8iv'); ?></th>
                                <td><a
                                        href="mailto:<?php echo esc_attr($booking->customer_email); ?>"><?php echo esc_html($booking->customer_email); ?></a>
                                </td>
                            </tr>
                            <?php if (!empty($booking->customer_phone)): ?>
                                <tr>
                                    <th><?php esc_html_e('Phone', 'book-now-kre8iv'); ?></th>
                                    <td><a
                                            href="tel:<?php echo esc_attr($booking->customer_phone); ?>"><?php echo esc_html($booking->customer_phone); ?></a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($booking->customer_notes)): ?>
                                <tr>
                                    <th><?php esc_html_e('Notes', 'book-now-kre8iv'); ?></th>
                                    <td><?php echo nl2br(esc_html($booking->customer_notes)); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <div class="booknow-detail-section">
                        <h3><?php esc_html_e('Payment Information', 'book-now-kre8iv'); ?></h3>
                        <table class="booknow-detail-table">
                            <tr>
                                <th><?php esc_html_e('Payment Status', 'book-now-kre8iv'); ?></th>
                                <td>
                                    <span
                                        class="booknow-payment-badge payment-<?php echo esc_attr($booking->payment_status); ?>">
                                        <?php echo esc_html(booknow_get_payment_status_label($booking->payment_status)); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php if ($booking->payment_amount): ?>
                                <tr>
                                    <th><?php esc_html_e('Amount', 'book-now-kre8iv'); ?></th>
                                    <td><?php echo esc_html(booknow_format_price($booking->payment_amount)); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($booking->payment_intent_id)): ?>
                                <tr>
                                    <th><?php esc_html_e('Payment Intent', 'book-now-kre8iv'); ?></th>
                                    <td><code><?php echo esc_html($booking->payment_intent_id); ?></code></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($booking->payment_date)): ?>
                                <tr>
                                    <th><?php esc_html_e('Payment Date', 'book-now-kre8iv'); ?></th>
                                    <td><?php echo esc_html(booknow_format_date($booking->payment_date)); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <div class="booknow-detail-section">
                        <h3><?php esc_html_e('Calendar Sync', 'book-now-kre8iv'); ?></h3>
                        <table class="booknow-detail-table">
                            <tr>
                                <th><?php esc_html_e('Google Calendar', 'book-now-kre8iv'); ?></th>
                                <td>
                                    <?php if (!empty($booking->google_event_id)): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                        <?php esc_html_e('Synced', 'book-now-kre8iv'); ?>
                                        <code
                                            style="font-size: 11px; margin-left: 5px;"><?php echo esc_html($booking->google_event_id); ?></code>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-minus" style="color: #999;"></span>
                                        <?php esc_html_e('Not synced', 'book-now-kre8iv'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Microsoft Calendar', 'book-now-kre8iv'); ?></th>
                                <td>
                                    <?php if (!empty($booking->microsoft_event_id)): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                        <?php esc_html_e('Synced', 'book-now-kre8iv'); ?>
                                        <code
                                            style="font-size: 11px; margin-left: 5px;"><?php echo esc_html($booking->microsoft_event_id); ?></code>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-minus" style="color: #999;"></span>
                                        <?php esc_html_e('Not synced', 'book-now-kre8iv'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($booking->status !== 'confirmed'): ?>
                                <tr>
                                    <td colspan="2" style="color: #666; font-style: italic;">
                                        <span class="dashicons dashicons-info"></span>
                                        <?php esc_html_e('Calendar sync requires booking status to be "Confirmed".', 'book-now-kre8iv'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($booking_id && !$booking): ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Booking not found.', 'book-now-kre8iv'); ?></p>
        </div>
        <p><a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings')); ?>">&larr;
                <?php esc_html_e('Back to bookings list', 'book-now-kre8iv'); ?></a></p>

    <?php else: ?>

        <div class="booknow-card">
            <div class="booknow-card-body">
                <?php if (!empty($bookings)): ?>
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
                            <?php foreach ($bookings as $booking):
                                $type = Book_Now_Consultation_Type::get_by_id($booking->consultation_type_id);
                                ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a
                                                href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings&id=' . $booking->id)); ?>">
                                                <?php echo esc_html($booking->reference_number); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="booknow-customer-name"><?php echo esc_html($booking->customer_name); ?></span>
                                        <span
                                            class="booknow-customer-email"><?php echo esc_html($booking->customer_email); ?></span>
                                    </td>
                                    <td>
                                        <span
                                            class="booknow-date"><?php echo esc_html(booknow_format_date($booking->booking_date)); ?></span>
                                        <span
                                            class="booknow-time"><?php echo esc_html(booknow_format_time($booking->booking_time)); ?></span>
                                    </td>
                                    <td><?php echo $type ? esc_html($type->name) : '-'; ?></td>
                                    <td>
                                        <span class="booknow-status-badge status-<?php echo esc_attr($booking->status); ?>">
                                            <?php echo esc_html(booknow_get_status_label($booking->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            class="booknow-payment-badge payment-<?php echo esc_attr($booking->payment_status); ?>">
                                            <?php echo esc_html(booknow_get_payment_status_label($booking->payment_status)); ?>
                                        </span>
                                    </td>
                                    <td class="booknow-amount">
                                        <?php echo $booking->payment_amount ? esc_html(booknow_format_price($booking->payment_amount)) : '-'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="booknow-empty-state">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <p><?php esc_html_e('No bookings found.', 'book-now-kre8iv'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>