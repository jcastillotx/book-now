<?php
/**
 * Admin email logs viewer page
 *
 * @package BookNow
 * @since   1.3.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Security check - verify user has admin capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'book-now-kre8iv'));
}

global $wpdb;
$table_name = $wpdb->prefix . 'booknow_email_log';

// Handle bulk actions
if (isset($_POST['action']) && isset($_POST['email_log_ids']) && isset($_POST['_wpnonce'])) {
    if (wp_verify_nonce($_POST['_wpnonce'], 'booknow_email_logs_bulk_action')) {
        $action = sanitize_text_field($_POST['action']);
        $log_ids = array_map('absint', $_POST['email_log_ids']);

        if ($action === 'delete' && !empty($log_ids)) {
            $placeholders = implode(',', array_fill(0, count($log_ids), '%d'));
            $wpdb->query($wpdb->prepare("DELETE FROM {$table_name} WHERE id IN ({$placeholders})", $log_ids));
            $notice = __('Selected email logs deleted successfully.', 'book-now-kre8iv');
            $notice_type = 'success';
        }
    } else {
        $notice = __('Security check failed. Please try again.', 'book-now-kre8iv');
        $notice_type = 'error';
    }
}

// Handle clear old logs action
if (isset($_POST['clear_old_logs']) && isset($_POST['_wpnonce'])) {
    if (wp_verify_nonce($_POST['_wpnonce'], 'booknow_clear_old_email_logs')) {
        $days = absint($_POST['clear_days'] ?? 30);
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$table_name} WHERE sent_at < %s", $date_threshold));
        $notice = sprintf(__('Deleted %d email logs older than %d days.', 'book-now-kre8iv'), $deleted, $days);
        $notice_type = 'success';
    }
}

// Pagination settings
$per_page = 20;
$current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$offset = ($current_page - 1) * $per_page;

// Build query filters
$where_conditions = array('1=1');
$query_params = array();

// Filter by email type
if (isset($_GET['email_type']) && !empty($_GET['email_type'])) {
    $email_type = sanitize_text_field($_GET['email_type']);
    $where_conditions[] = 'email_type = %s';
    $query_params[] = $email_type;
}

// Filter by status
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = sanitize_text_field($_GET['status']);
    $where_conditions[] = 'status = %s';
    $query_params[] = $status;
}

// Filter by date range
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $date_from = sanitize_text_field($_GET['date_from']) . ' 00:00:00';
    $where_conditions[] = 'sent_at >= %s';
    $query_params[] = $date_from;
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $date_to = sanitize_text_field($_GET['date_to']) . ' 23:59:59';
    $where_conditions[] = 'sent_at <= %s';
    $query_params[] = $date_to;
}

// Search by recipient email or subject
if (isset($_GET['s']) && !empty($_GET['s'])) {
    $search = '%' . $wpdb->esc_like(sanitize_text_field($_GET['s'])) . '%';
    $where_conditions[] = '(recipient_email LIKE %s OR subject LIKE %s)';
    $query_params[] = $search;
    $query_params[] = $search;
}

// Build WHERE clause
$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
if (!empty($query_params)) {
    $count_query = $wpdb->prepare($count_query, $query_params);
}
$total_items = $wpdb->get_var($count_query);
$total_pages = ceil($total_items / $per_page);

// Get logs with pagination
$query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY sent_at DESC LIMIT %d OFFSET %d";
$query_params[] = $per_page;
$query_params[] = $offset;
$email_logs = $wpdb->get_results($wpdb->prepare($query, $query_params));

// Get statistics
$stats = $wpdb->get_row("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
        COUNT(DISTINCT DATE(sent_at)) as days_active
    FROM {$table_name}
");
?>

<div class="booknow-wrap">
    <div class="booknow-page-header">
        <h1>
            <span class="dashicons dashicons-email-alt"></span>
            <?php esc_html_e('Email Logs', 'book-now-kre8iv'); ?>
        </h1>
        <div class="booknow-page-actions">
            <button type="button" class="button" id="export-csv">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export CSV', 'book-now-kre8iv'); ?>
            </button>
            <button type="button" class="button" id="clear-old-logs-btn">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Clear Old Logs', 'book-now-kre8iv'); ?>
            </button>
        </div>
    </div>

    <?php if (isset($notice)) : ?>
        <div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible">
            <p><?php echo esc_html($notice); ?></p>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="booknow-stats-row">
        <div class="booknow-stat-card stat-primary">
            <div class="stat-icon">
                <span class="dashicons dashicons-email"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($stats->total)); ?></h3>
                <p><?php esc_html_e('Total Emails', 'book-now-kre8iv'); ?></p>
            </div>
        </div>

        <div class="booknow-stat-card stat-success">
            <div class="stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($stats->sent)); ?></h3>
                <p><?php esc_html_e('Sent Successfully', 'book-now-kre8iv'); ?></p>
            </div>
        </div>

        <div class="booknow-stat-card stat-danger">
            <div class="stat-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($stats->failed)); ?></h3>
                <p><?php esc_html_e('Failed', 'book-now-kre8iv'); ?></p>
            </div>
        </div>

        <div class="booknow-stat-card stat-warning">
            <div class="stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($stats->days_active)); ?></h3>
                <p><?php esc_html_e('Days Active', 'book-now-kre8iv'); ?></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="booknow-card">
        <div class="booknow-card-header">
            <h2>
                <span class="dashicons dashicons-filter"></span>
                <?php esc_html_e('Filter & Search', 'book-now-kre8iv'); ?>
            </h2>
        </div>
        <div class="booknow-card-body">
            <form method="get" action="" id="email-logs-filter-form">
                <input type="hidden" name="page" value="book-now-email-logs">

                <div class="booknow-form-row-inline">
                    <div class="booknow-form-field">
                        <label for="email-type"><?php esc_html_e('Email Type', 'book-now-kre8iv'); ?></label>
                        <select name="email_type" id="email-type">
                            <option value=""><?php esc_html_e('All Types', 'book-now-kre8iv'); ?></option>
                            <option value="confirmation" <?php selected(isset($_GET['email_type']) && $_GET['email_type'] === 'confirmation'); ?>><?php esc_html_e('Confirmation', 'book-now-kre8iv'); ?></option>
                            <option value="reminder" <?php selected(isset($_GET['email_type']) && $_GET['email_type'] === 'reminder'); ?>><?php esc_html_e('Reminder', 'book-now-kre8iv'); ?></option>
                            <option value="cancellation" <?php selected(isset($_GET['email_type']) && $_GET['email_type'] === 'cancellation'); ?>><?php esc_html_e('Cancellation', 'book-now-kre8iv'); ?></option>
                            <option value="admin_notification" <?php selected(isset($_GET['email_type']) && $_GET['email_type'] === 'admin_notification'); ?>><?php esc_html_e('Admin Notification', 'book-now-kre8iv'); ?></option>
                        </select>
                    </div>

                    <div class="booknow-form-field">
                        <label for="status"><?php esc_html_e('Status', 'book-now-kre8iv'); ?></label>
                        <select name="status" id="status">
                            <option value=""><?php esc_html_e('All Statuses', 'book-now-kre8iv'); ?></option>
                            <option value="sent" <?php selected(isset($_GET['status']) && $_GET['status'] === 'sent'); ?>><?php esc_html_e('Sent', 'book-now-kre8iv'); ?></option>
                            <option value="failed" <?php selected(isset($_GET['status']) && $_GET['status'] === 'failed'); ?>><?php esc_html_e('Failed', 'book-now-kre8iv'); ?></option>
                        </select>
                    </div>

                    <div class="booknow-form-field">
                        <label for="date-from"><?php esc_html_e('Date From', 'book-now-kre8iv'); ?></label>
                        <input type="date" name="date_from" id="date-from" value="<?php echo isset($_GET['date_from']) ? esc_attr($_GET['date_from']) : ''; ?>">
                    </div>

                    <div class="booknow-form-field">
                        <label for="date-to"><?php esc_html_e('Date To', 'book-now-kre8iv'); ?></label>
                        <input type="date" name="date_to" id="date-to" value="<?php echo isset($_GET['date_to']) ? esc_attr($_GET['date_to']) : ''; ?>">
                    </div>
                </div>

                <div class="booknow-form-row" style="margin-top: 16px;">
                    <label for="search"><?php esc_html_e('Search', 'book-now-kre8iv'); ?></label>
                    <input type="text" name="s" id="search" placeholder="<?php esc_attr_e('Search by recipient email or subject...', 'book-now-kre8iv'); ?>" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>" style="max-width: 500px;">
                </div>

                <div style="margin-top: 16px;">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Apply Filters', 'book-now-kre8iv'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-email-logs')); ?>" class="button">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php esc_html_e('Reset', 'book-now-kre8iv'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Email Logs Table -->
    <div class="booknow-card">
        <div class="booknow-card-body">
            <?php if (!empty($email_logs)) : ?>
                <form method="post" id="email-logs-bulk-form">
                    <?php wp_nonce_field('booknow_email_logs_bulk_action', '_wpnonce'); ?>

                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'book-now-kre8iv'); ?></label>
                            <select name="action" id="bulk-action-selector-top">
                                <option value="-1"><?php esc_html_e('Bulk Actions', 'book-now-kre8iv'); ?></option>
                                <option value="delete"><?php esc_html_e('Delete', 'book-now-kre8iv'); ?></option>
                            </select>
                            <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'book-now-kre8iv'); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete the selected email logs?', 'book-now-kre8iv'); ?>');">
                        </div>
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php printf(esc_html(_n('%s item', '%s items', $total_items, 'book-now-kre8iv')), number_format_i18n($total_items)); ?></span>
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $current_page
                            ));
                            ?>
                        </div>
                    </div>

                    <table class="wp-list-table widefat fixed striped booknow-table">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input type="checkbox" id="cb-select-all-1">
                                </td>
                                <th><?php esc_html_e('Date', 'book-now-kre8iv'); ?></th>
                                <th><?php esc_html_e('Type', 'book-now-kre8iv'); ?></th>
                                <th><?php esc_html_e('Recipient', 'book-now-kre8iv'); ?></th>
                                <th><?php esc_html_e('Subject', 'book-now-kre8iv'); ?></th>
                                <th><?php esc_html_e('Booking', 'book-now-kre8iv'); ?></th>
                                <th><?php esc_html_e('Status', 'book-now-kre8iv'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($email_logs as $log) :
                                $booking = $log->booking_id ? Book_Now_Booking::get($log->booking_id) : null;
                            ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="email_log_ids[]" value="<?php echo esc_attr($log->id); ?>">
                                    </th>
                                    <td>
                                        <span class="booknow-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($log->sent_at))); ?></span>
                                        <span class="booknow-time"><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($log->sent_at))); ?></span>
                                    </td>
                                    <td>
                                        <span class="booknow-status-badge status-<?php echo esc_attr($log->email_type); ?>">
                                            <?php echo esc_html(ucwords(str_replace('_', ' ', $log->email_type))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($log->recipient_email); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo esc_html($log->subject); ?>
                                        <?php if ($log->status === 'failed' && !empty($log->error_message)) : ?>
                                            <br>
                                            <small class="booknow-text-danger" title="<?php echo esc_attr($log->error_message); ?>">
                                                <span class="dashicons dashicons-warning" style="font-size: 14px;"></span>
                                                <?php echo esc_html(wp_trim_words($log->error_message, 10)); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($booking) : ?>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings&id=' . $booking->id)); ?>">
                                                <?php echo esc_html($booking->reference_number); ?>
                                            </a>
                                        <?php else : ?>
                                            <span class="booknow-text-muted"><?php echo esc_html($log->booking_id); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="booknow-payment-badge payment-<?php echo esc_attr($log->status); ?>">
                                            <?php echo esc_html(ucfirst($log->status)); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php printf(esc_html(_n('%s item', '%s items', $total_items, 'book-now-kre8iv')), number_format_i18n($total_items)); ?></span>
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $current_page
                            ));
                            ?>
                        </div>
                    </div>
                </form>
            <?php else : ?>
                <div class="booknow-empty-state">
                    <span class="dashicons dashicons-email-alt"></span>
                    <p><?php esc_html_e('No email logs found.', 'book-now-kre8iv'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Clear Old Logs Modal -->
<div id="clear-old-logs-modal" class="booknow-modal" style="display: none;">
    <div class="booknow-modal-content">
        <div class="booknow-modal-header">
            <h2>
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Clear Old Email Logs', 'book-now-kre8iv'); ?>
            </h2>
            <span class="booknow-modal-close">&times;</span>
        </div>
        <form method="post" id="clear-old-logs-form">
            <?php wp_nonce_field('booknow_clear_old_email_logs', '_wpnonce'); ?>
            <div class="booknow-modal-body">
                <p><?php esc_html_e('This will permanently delete email logs older than the specified number of days.', 'book-now-kre8iv'); ?></p>
                <div class="booknow-form-row">
                    <label for="clear-days"><?php esc_html_e('Delete logs older than', 'book-now-kre8iv'); ?></label>
                    <select name="clear_days" id="clear-days">
                        <option value="7">7 <?php esc_html_e('days', 'book-now-kre8iv'); ?></option>
                        <option value="30" selected>30 <?php esc_html_e('days', 'book-now-kre8iv'); ?></option>
                        <option value="60">60 <?php esc_html_e('days', 'book-now-kre8iv'); ?></option>
                        <option value="90">90 <?php esc_html_e('days', 'book-now-kre8iv'); ?></option>
                        <option value="180">180 <?php esc_html_e('days', 'book-now-kre8iv'); ?></option>
                        <option value="365">365 <?php esc_html_e('days', 'book-now-kre8iv'); ?></option>
                    </select>
                </div>
            </div>
            <div class="booknow-modal-footer">
                <button type="button" class="button booknow-modal-close"><?php esc_html_e('Cancel', 'book-now-kre8iv'); ?></button>
                <button type="submit" name="clear_old_logs" class="button button-primary" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete these email logs? This action cannot be undone.', 'book-now-kre8iv'); ?>');">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e('Delete Logs', 'book-now-kre8iv'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Select all checkboxes
    $('#cb-select-all-1').on('change', function() {
        $('input[name="email_log_ids[]"]').prop('checked', $(this).prop('checked'));
    });

    // Modal handling
    var modal = $('#clear-old-logs-modal');

    $('#clear-old-logs-btn').on('click', function() {
        modal.show();
    });

    $('.booknow-modal-close').on('click', function() {
        modal.hide();
    });

    $(window).on('click', function(e) {
        if ($(e.target).is('.booknow-modal')) {
            modal.hide();
        }
    });

    // Export CSV functionality (placeholder for future AJAX implementation)
    $('#export-csv').on('click', function() {
        alert('<?php esc_attr_e('CSV export functionality will be implemented in the backend.', 'book-now-kre8iv'); ?>');
        // TODO: Implement AJAX call to export CSV
    });
});
</script>
