<?php
/**
 * Admin error logs viewer page
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
$table_name = $wpdb->prefix . 'booknow_error_log';

// Handle bulk actions
if (isset($_POST['action']) && isset($_POST['error_log_ids']) && isset($_POST['_wpnonce'])) {
    if (wp_verify_nonce($_POST['_wpnonce'], 'booknow_error_logs_bulk_action')) {
        $action = sanitize_text_field($_POST['action']);
        $log_ids = array_map('absint', $_POST['error_log_ids']);

        if ($action === 'delete' && !empty($log_ids)) {
            $placeholders = implode(',', array_fill(0, count($log_ids), '%d'));
            $wpdb->query($wpdb->prepare("DELETE FROM {$table_name} WHERE id IN ({$placeholders})", $log_ids));
            $notice = __('Selected error logs deleted successfully.', 'book-now-kre8iv');
            $notice_type = 'success';
        }
    } else {
        $notice = __('Security check failed. Please try again.', 'book-now-kre8iv');
        $notice_type = 'error';
    }
}

// Handle clear old logs action
if (isset($_POST['clear_old_logs']) && isset($_POST['_wpnonce'])) {
    if (wp_verify_nonce($_POST['_wpnonce'], 'booknow_clear_old_error_logs')) {
        $days = absint($_POST['clear_days'] ?? 30);
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$table_name} WHERE created_at < %s", $date_threshold));
        $notice = sprintf(__('Deleted %d error logs older than %d days.', 'book-now-kre8iv'), $deleted, $days);
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

// Filter by error level
if (isset($_GET['error_level']) && !empty($_GET['error_level'])) {
    $error_level = sanitize_text_field($_GET['error_level']);
    $where_conditions[] = 'error_level = %s';
    $query_params[] = $error_level;
}

// Filter by error source
if (isset($_GET['error_source']) && !empty($_GET['error_source'])) {
    $error_source = sanitize_text_field($_GET['error_source']);
    $where_conditions[] = 'error_source = %s';
    $query_params[] = $error_source;
}

// Filter by date range
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $date_from = sanitize_text_field($_GET['date_from']) . ' 00:00:00';
    $where_conditions[] = 'created_at >= %s';
    $query_params[] = $date_from;
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $date_to = sanitize_text_field($_GET['date_to']) . ' 23:59:59';
    $where_conditions[] = 'created_at <= %s';
    $query_params[] = $date_to;
}

// Search by error message
if (isset($_GET['s']) && !empty($_GET['s'])) {
    $search = '%' . $wpdb->esc_like(sanitize_text_field($_GET['s'])) . '%';
    $where_conditions[] = 'error_message LIKE %s';
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
$query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
$query_params[] = $per_page;
$query_params[] = $offset;
$error_logs = $wpdb->get_results($wpdb->prepare($query, $query_params));

// Get statistics
$stats = $wpdb->get_row("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN error_level = 'CRITICAL' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN error_level = 'ERROR' THEN 1 ELSE 0 END) as errors,
        SUM(CASE WHEN error_level = 'WARNING' THEN 1 ELSE 0 END) as warnings,
        SUM(CASE WHEN error_level = 'INFO' THEN 1 ELSE 0 END) as info,
        SUM(CASE WHEN error_level = 'DEBUG' THEN 1 ELSE 0 END) as debug
    FROM {$table_name}
");

// Get unique error sources
$error_sources = $wpdb->get_col("SELECT DISTINCT error_source FROM {$table_name} WHERE error_source IS NOT NULL ORDER BY error_source");
?>

<div class="booknow-wrap">
    <div class="booknow-page-header">
        <h1>
            <span class="dashicons dashicons-warning"></span>
            <?php esc_html_e('Error Logs', 'book-now-kre8iv'); ?>
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
                <span class="dashicons dashicons-list-view"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($stats->total)); ?></h3>
                <p><?php esc_html_e('Total Logs', 'book-now-kre8iv'); ?></p>
            </div>
        </div>

        <div class="booknow-stat-card stat-danger">
            <div class="stat-icon">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($stats->critical + $stats->errors)); ?></h3>
                <p><?php esc_html_e('Critical & Errors', 'book-now-kre8iv'); ?></p>
            </div>
        </div>

        <div class="booknow-stat-card stat-warning">
            <div class="stat-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($stats->warnings)); ?></h3>
                <p><?php esc_html_e('Warnings', 'book-now-kre8iv'); ?></p>
            </div>
        </div>

        <div class="booknow-stat-card stat-success">
            <div class="stat-icon">
                <span class="dashicons dashicons-info"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($stats->info + $stats->debug)); ?></h3>
                <p><?php esc_html_e('Info & Debug', 'book-now-kre8iv'); ?></p>
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
            <form method="get" action="" id="error-logs-filter-form">
                <input type="hidden" name="page" value="book-now-error-logs">

                <div class="booknow-form-row-inline">
                    <div class="booknow-form-field">
                        <label for="error-level"><?php esc_html_e('Error Level', 'book-now-kre8iv'); ?></label>
                        <select name="error_level" id="error-level">
                            <option value=""><?php esc_html_e('All Levels', 'book-now-kre8iv'); ?></option>
                            <option value="CRITICAL" <?php selected(isset($_GET['error_level']) && $_GET['error_level'] === 'CRITICAL'); ?>><?php esc_html_e('Critical', 'book-now-kre8iv'); ?></option>
                            <option value="ERROR" <?php selected(isset($_GET['error_level']) && $_GET['error_level'] === 'ERROR'); ?>><?php esc_html_e('Error', 'book-now-kre8iv'); ?></option>
                            <option value="WARNING" <?php selected(isset($_GET['error_level']) && $_GET['error_level'] === 'WARNING'); ?>><?php esc_html_e('Warning', 'book-now-kre8iv'); ?></option>
                            <option value="INFO" <?php selected(isset($_GET['error_level']) && $_GET['error_level'] === 'INFO'); ?>><?php esc_html_e('Info', 'book-now-kre8iv'); ?></option>
                            <option value="DEBUG" <?php selected(isset($_GET['error_level']) && $_GET['error_level'] === 'DEBUG'); ?>><?php esc_html_e('Debug', 'book-now-kre8iv'); ?></option>
                        </select>
                    </div>

                    <div class="booknow-form-field">
                        <label for="error-source"><?php esc_html_e('Error Source', 'book-now-kre8iv'); ?></label>
                        <select name="error_source" id="error-source">
                            <option value=""><?php esc_html_e('All Sources', 'book-now-kre8iv'); ?></option>
                            <?php foreach ($error_sources as $source) : ?>
                                <option value="<?php echo esc_attr($source); ?>" <?php selected(isset($_GET['error_source']) && $_GET['error_source'] === $source); ?>>
                                    <?php echo esc_html($source); ?>
                                </option>
                            <?php endforeach; ?>
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
                    <input type="text" name="s" id="search" placeholder="<?php esc_attr_e('Search by error message...', 'book-now-kre8iv'); ?>" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>" style="max-width: 500px;">
                </div>

                <div style="margin-top: 16px;">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Apply Filters', 'book-now-kre8iv'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-error-logs')); ?>" class="button">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php esc_html_e('Reset', 'book-now-kre8iv'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Error Logs Table -->
    <div class="booknow-card">
        <div class="booknow-card-body">
            <?php if (!empty($error_logs)) : ?>
                <form method="post" id="error-logs-bulk-form">
                    <?php wp_nonce_field('booknow_error_logs_bulk_action', '_wpnonce'); ?>

                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'book-now-kre8iv'); ?></label>
                            <select name="action" id="bulk-action-selector-top">
                                <option value="-1"><?php esc_html_e('Bulk Actions', 'book-now-kre8iv'); ?></option>
                                <option value="delete"><?php esc_html_e('Delete', 'book-now-kre8iv'); ?></option>
                            </select>
                            <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'book-now-kre8iv'); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete the selected error logs?', 'book-now-kre8iv'); ?>');">
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
                                <th><?php esc_html_e('Level', 'book-now-kre8iv'); ?></th>
                                <th><?php esc_html_e('Source', 'book-now-kre8iv'); ?></th>
                                <th><?php esc_html_e('Error Message', 'book-now-kre8iv'); ?></th>
                                <th><?php esc_html_e('Context', 'book-now-kre8iv'); ?></th>
                                <th><?php esc_html_e('Actions', 'book-now-kre8iv'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($error_logs as $log) :
                                $context_data = !empty($log->error_context) ? json_decode($log->error_context, true) : null;
                                $booking = $log->booking_id ? Book_Now_Booking::get($log->booking_id) : null;
                            ?>
                                <tr class="error-log-row" data-log-id="<?php echo esc_attr($log->id); ?>">
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="error_log_ids[]" value="<?php echo esc_attr($log->id); ?>">
                                    </th>
                                    <td>
                                        <span class="booknow-date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($log->created_at))); ?></span>
                                        <span class="booknow-time"><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($log->created_at))); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $level_class = strtolower($log->error_level);
                                        $badge_class = 'booknow-status-badge';

                                        switch ($log->error_level) {
                                            case 'CRITICAL':
                                            case 'ERROR':
                                                $badge_class .= ' status-cancelled';
                                                break;
                                            case 'WARNING':
                                                $badge_class .= ' status-pending';
                                                break;
                                            case 'INFO':
                                                $badge_class .= ' status-confirmed';
                                                break;
                                            case 'DEBUG':
                                                $badge_class .= ' status-no-show';
                                                break;
                                        }
                                        ?>
                                        <span class="<?php echo esc_attr($badge_class); ?>">
                                            <?php echo esc_html($log->error_level); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($log->error_source)) : ?>
                                            <code style="font-size: 11px;"><?php echo esc_html($log->error_source); ?></code>
                                        <?php else : ?>
                                            <span class="booknow-text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html(wp_trim_words($log->error_message, 15)); ?></strong>
                                        <?php if ($booking) : ?>
                                            <br>
                                            <small>
                                                <?php esc_html_e('Booking:', 'book-now-kre8iv'); ?>
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings&id=' . $booking->id)); ?>">
                                                    <?php echo esc_html($booking->reference_number); ?>
                                                </a>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($log->ip_address)) : ?>
                                            <br>
                                            <small class="booknow-text-muted">
                                                <?php esc_html_e('IP:', 'book-now-kre8iv'); ?> <?php echo esc_html($log->ip_address); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($context_data) : ?>
                                            <button type="button" class="button button-small view-context" data-context="<?php echo esc_attr(wp_json_encode($context_data)); ?>">
                                                <span class="dashicons dashicons-visibility"></span>
                                                <?php esc_html_e('View', 'book-now-kre8iv'); ?>
                                            </button>
                                        <?php else : ?>
                                            <span class="booknow-text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small view-details" data-id="<?php echo esc_attr($log->id); ?>">
                                            <span class="dashicons dashicons-search"></span>
                                            <?php esc_html_e('Details', 'book-now-kre8iv'); ?>
                                        </button>
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
                    <span class="dashicons dashicons-warning"></span>
                    <p><?php esc_html_e('No error logs found.', 'book-now-kre8iv'); ?></p>
                    <?php if (empty($_GET['error_level']) && empty($_GET['s'])) : ?>
                        <p class="booknow-text-success"><?php esc_html_e('Great! Your system is running smoothly.', 'book-now-kre8iv'); ?></p>
                    <?php endif; ?>
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
                <?php esc_html_e('Clear Old Error Logs', 'book-now-kre8iv'); ?>
            </h2>
            <span class="booknow-modal-close">&times;</span>
        </div>
        <form method="post" id="clear-old-logs-form">
            <?php wp_nonce_field('booknow_clear_old_error_logs', '_wpnonce'); ?>
            <div class="booknow-modal-body">
                <p><?php esc_html_e('This will permanently delete error logs older than the specified number of days.', 'book-now-kre8iv'); ?></p>
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
                <button type="submit" name="clear_old_logs" class="button button-primary" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete these error logs? This action cannot be undone.', 'book-now-kre8iv'); ?>');">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e('Delete Logs', 'book-now-kre8iv'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Error Details Modal -->
<div id="error-details-modal" class="booknow-modal" style="display: none;">
    <div class="booknow-modal-content" style="max-width: 800px;">
        <div class="booknow-modal-header">
            <h2>
                <span class="dashicons dashicons-info"></span>
                <?php esc_html_e('Error Details', 'book-now-kre8iv'); ?>
            </h2>
            <span class="booknow-modal-close">&times;</span>
        </div>
        <div class="booknow-modal-body" id="error-details-content">
            <p><?php esc_html_e('Loading...', 'book-now-kre8iv'); ?></p>
        </div>
        <div class="booknow-modal-footer">
            <button type="button" class="button booknow-modal-close"><?php esc_html_e('Close', 'book-now-kre8iv'); ?></button>
        </div>
    </div>
</div>

<!-- Context Viewer Modal -->
<div id="context-modal" class="booknow-modal" style="display: none;">
    <div class="booknow-modal-content" style="max-width: 700px;">
        <div class="booknow-modal-header">
            <h2>
                <span class="dashicons dashicons-code-standards"></span>
                <?php esc_html_e('Error Context', 'book-now-kre8iv'); ?>
            </h2>
            <span class="booknow-modal-close">&times;</span>
        </div>
        <div class="booknow-modal-body">
            <pre id="context-content" style="background: #f6f7f7; padding: 16px; border-radius: 4px; overflow-x: auto; max-height: 400px;"></pre>
        </div>
        <div class="booknow-modal-footer">
            <button type="button" class="button booknow-modal-close"><?php esc_html_e('Close', 'book-now-kre8iv'); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Select all checkboxes
    $('#cb-select-all-1').on('change', function() {
        $('input[name="error_log_ids[]"]').prop('checked', $(this).prop('checked'));
    });

    // Modal handling
    var clearModal = $('#clear-old-logs-modal');
    var detailsModal = $('#error-details-modal');
    var contextModal = $('#context-modal');

    $('#clear-old-logs-btn').on('click', function() {
        clearModal.show();
    });

    $('.booknow-modal-close').on('click', function() {
        $(this).closest('.booknow-modal').hide();
    });

    $(window).on('click', function(e) {
        if ($(e.target).is('.booknow-modal')) {
            $(e.target).hide();
        }
    });

    // View context button
    $('.view-context').on('click', function() {
        var context = $(this).data('context');
        $('#context-content').text(JSON.stringify(context, null, 2));
        contextModal.show();
    });

    // View details button (placeholder for future AJAX implementation)
    $('.view-details').on('click', function() {
        var logId = $(this).data('id');
        var $row = $(this).closest('tr');

        // Get data from the row
        var details = {
            date: $row.find('.booknow-date').text() + ' ' + $row.find('.booknow-time').text(),
            level: $row.find('.booknow-status-badge').text().trim(),
            source: $row.find('code').text() || 'N/A',
            message: $row.find('td:nth-child(5) strong').text()
        };

        // Build details HTML
        var html = '<table class="booknow-detail-table">';
        html += '<tr><th><?php esc_attr_e('Date & Time', 'book-now-kre8iv'); ?></th><td>' + details.date + '</td></tr>';
        html += '<tr><th><?php esc_attr_e('Error Level', 'book-now-kre8iv'); ?></th><td>' + details.level + '</td></tr>';
        html += '<tr><th><?php esc_attr_e('Source', 'book-now-kre8iv'); ?></th><td>' + details.source + '</td></tr>';
        html += '<tr><th><?php esc_attr_e('Message', 'book-now-kre8iv'); ?></th><td>' + details.message + '</td></tr>';
        html += '</table>';

        $('#error-details-content').html(html);
        detailsModal.show();

        // TODO: Implement AJAX call to fetch complete error details including full context
    });

    // Export CSV functionality (placeholder for future AJAX implementation)
    $('#export-csv').on('click', function() {
        alert('<?php esc_attr_e('CSV export functionality will be implemented in the backend.', 'book-now-kre8iv'); ?>');
        // TODO: Implement AJAX call to export CSV
    });
});
</script>
