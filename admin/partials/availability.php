<?php
/**
 * Admin availability page
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

// Handle form submission
if (isset($_POST['save_availability']) && check_admin_referer('booknow_save_availability')) {
    global $wpdb;
    $table = $wpdb->prefix . 'booknow_availability';
    
    // Delete existing weekly rules
    $wpdb->delete($table, array('rule_type' => 'weekly'), array('%s'));
    
    // Save new weekly rules
    if (isset($_POST['availability']) && is_array($_POST['availability'])) {
        foreach ($_POST['availability'] as $day => $data) {
            if (!empty($data['enabled'])) {
                $wpdb->insert(
                    $table,
                    array(
                        'rule_type'    => 'weekly',
                        'day_of_week'  => intval($day),
                        'start_time'   => sanitize_text_field($data['start']),
                        'end_time'     => sanitize_text_field($data['end']),
                        'is_available' => 1,
                        'priority'     => 0,
                    ),
                    array('%s', '%d', '%s', '%s', '%d', '%d')
                );
            }
        }
    }
    
    echo '<div class="notice notice-success"><p>' . esc_html__('Availability settings saved successfully.', 'book-now-kre8iv') . '</p></div>';
}

// Handle block date submission
if (isset($_POST['add_block_date']) && check_admin_referer('booknow_add_block_date')) {
    global $wpdb;
    $table = $wpdb->prefix . 'booknow_availability';

    $block_date = sanitize_text_field($_POST['block_date']);
    $block_reason = sanitize_text_field($_POST['block_reason']);
    $is_range = isset($_POST['block_date_range']) && $_POST['block_date_range'] === '1';
    $block_end_date = $is_range ? sanitize_text_field($_POST['block_end_date']) : $block_date;

    // Validate dates
    $start = new DateTime($block_date);
    $end = new DateTime($block_end_date);

    // Ensure end date is not before start date
    if ($end < $start) {
        $temp = $start;
        $start = $end;
        $end = $temp;
    }

    // Create block entries for each date in the range
    $dates_blocked = 0;
    $current = clone $start;
    while ($current <= $end) {
        $date_str = $current->format('Y-m-d');

        // Check if date is already blocked to avoid duplicates
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE rule_type = 'block' AND specific_date = %s",
            $date_str
        ));

        if (!$existing) {
            $wpdb->insert(
                $table,
                array(
                    'rule_type'      => 'block',
                    'specific_date'  => $date_str,
                    'start_time'     => '00:00:00',
                    'end_time'       => '23:59:59',
                    'is_available'   => 0,
                    'priority'       => 10,
                ),
                array('%s', '%s', '%s', '%s', '%d', '%d')
            );
            $dates_blocked++;
        }

        $current->modify('+1 day');
    }

    if ($dates_blocked > 0) {
        if ($dates_blocked === 1) {
            echo '<div class="notice notice-success"><p>' . esc_html__('Block date added successfully.', 'book-now-kre8iv') . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>' . sprintf(
                /* translators: %d: number of dates blocked */
                esc_html__('%d block dates added successfully.', 'book-now-kre8iv'),
                $dates_blocked
            ) . '</p></div>';
        }
    } else {
        echo '<div class="notice notice-warning"><p>' . esc_html__('Selected dates are already blocked.', 'book-now-kre8iv') . '</p></div>';
    }
}

// Handle delete block date
if (isset($_GET['delete_block']) && check_admin_referer('booknow_delete_block_' . $_GET['delete_block'])) {
    global $wpdb;
    $table = $wpdb->prefix . 'booknow_availability';
    $wpdb->delete($table, array('id' => intval($_GET['delete_block'])), array('%d'));
    
    echo '<div class="notice notice-success"><p>' . esc_html__('Block date deleted successfully.', 'book-now-kre8iv') . '</p></div>';
}

// Get current weekly availability
global $wpdb;
$table = $wpdb->prefix . 'booknow_availability';
$weekly_rules = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table} WHERE rule_type = %s ORDER BY day_of_week ASC",
    'weekly'
));

$availability = array();
foreach ($weekly_rules as $rule) {
    $availability[$rule->day_of_week] = array(
        'enabled' => true,
        'start'   => substr($rule->start_time, 0, 5),
        'end'     => substr($rule->end_time, 0, 5),
    );
}

// Get block dates
$block_dates = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table} WHERE rule_type = %s ORDER BY specific_date ASC",
    'block'
));

$days = array(
    1 => __('Monday', 'book-now-kre8iv'),
    2 => __('Tuesday', 'book-now-kre8iv'),
    3 => __('Wednesday', 'book-now-kre8iv'),
    4 => __('Thursday', 'book-now-kre8iv'),
    5 => __('Friday', 'book-now-kre8iv'),
    6 => __('Saturday', 'book-now-kre8iv'),
    0 => __('Sunday', 'book-now-kre8iv'),
);
?>

<div class="booknow-wrap">
    <div class="booknow-page-header">
        <h1>
            <span class="dashicons dashicons-clock"></span>
            <?php esc_html_e('Availability Settings', 'book-now-kre8iv'); ?>
        </h1>
    </div>

    <div class="booknow-availability-settings">
        <!-- Weekly Schedule -->
        <div class="booknow-card">
            <div class="booknow-card-header">
                <h2>
                    <span class="dashicons dashicons-calendar"></span>
                    <?php esc_html_e('Weekly Schedule', 'book-now-kre8iv'); ?>
                </h2>
            </div>
            <div class="booknow-card-body">
                <p class="description"><?php esc_html_e('Set your regular weekly availability. These times will apply every week unless overridden by specific dates or blocks.', 'book-now-kre8iv'); ?></p>

                <form method="post" action="">
                    <?php wp_nonce_field('booknow_save_availability'); ?>

                    <table class="wp-list-table widefat fixed striped booknow-table booknow-availability-table">
                        <thead>
                            <tr>
                                <th style="width: 150px;"><?php esc_html_e('Day', 'book-now-kre8iv'); ?></th>
                                <th style="width: 100px;"><?php esc_html_e('Available', 'book-now-kre8iv'); ?></th>
                                <th style="width: 150px;"><?php esc_html_e('Start Time', 'book-now-kre8iv'); ?></th>
                                <th style="width: 150px;"><?php esc_html_e('End Time', 'book-now-kre8iv'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($days as $day_num => $day_name) :
                                $is_enabled = isset($availability[$day_num]['enabled']);
                                $start_time = isset($availability[$day_num]['start']) ? $availability[$day_num]['start'] : '09:00';
                                $end_time = isset($availability[$day_num]['end']) ? $availability[$day_num]['end'] : '17:00';
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($day_name); ?></strong></td>
                                    <td>
                                        <label class="booknow-toggle">
                                            <input type="checkbox"
                                                   name="availability[<?php echo esc_attr($day_num); ?>][enabled]"
                                                   value="1"
                                                   <?php checked($is_enabled); ?>
                                                   class="availability-toggle"
                                                   data-day="<?php echo esc_attr($day_num); ?>">
                                            <span class="booknow-toggle-slider"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <input type="time"
                                               name="availability[<?php echo esc_attr($day_num); ?>][start]"
                                               value="<?php echo esc_attr($start_time); ?>"
                                               class="availability-time"
                                               data-day="<?php echo esc_attr($day_num); ?>"
                                               <?php disabled(!$is_enabled); ?>>
                                    </td>
                                    <td>
                                        <input type="time"
                                               name="availability[<?php echo esc_attr($day_num); ?>][end]"
                                               value="<?php echo esc_attr($end_time); ?>"
                                               class="availability-time"
                                               data-day="<?php echo esc_attr($day_num); ?>"
                                               <?php disabled(!$is_enabled); ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p class="submit">
                        <button type="submit" name="save_availability" class="button button-primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e('Save Weekly Schedule', 'book-now-kre8iv'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Block Dates -->
        <div class="booknow-card">
            <div class="booknow-card-header">
                <h2>
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php esc_html_e('Block Dates', 'book-now-kre8iv'); ?>
                </h2>
            </div>
            <div class="booknow-card-body">
                <p class="description"><?php esc_html_e('Block specific dates when you are not available (holidays, vacations, etc.).', 'book-now-kre8iv'); ?></p>

                <form method="post" action="" class="booknow-block-date-form">
                    <?php wp_nonce_field('booknow_add_block_date'); ?>

                    <div class="booknow-form-row booknow-form-row-inline">
                        <div class="booknow-form-field">
                            <label for="block_date">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php esc_html_e('Start Date', 'book-now-kre8iv'); ?>
                            </label>
                            <input type="date"
                                   name="block_date"
                                   id="block_date"
                                   required
                                   min="<?php echo esc_attr(date('Y-m-d')); ?>">
                        </div>
                        <div class="booknow-form-field booknow-end-date-row" style="display: none;">
                            <label for="block_end_date">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php esc_html_e('End Date', 'book-now-kre8iv'); ?>
                            </label>
                            <input type="date"
                                   name="block_end_date"
                                   id="block_end_date"
                                   min="<?php echo esc_attr(date('Y-m-d')); ?>">
                        </div>
                    </div>

                    <div class="booknow-form-row">
                        <label class="booknow-checkbox-label">
                            <input type="checkbox"
                                   name="block_date_range"
                                   id="block_date_range"
                                   value="1">
                            <?php esc_html_e('Block multiple days (date range)', 'book-now-kre8iv'); ?>
                        </label>
                    </div>

                    <div class="booknow-form-row">
                        <label for="block_reason"><?php esc_html_e('Reason (Optional)', 'book-now-kre8iv'); ?></label>
                        <input type="text"
                               name="block_reason"
                               id="block_reason"
                               class="regular-text"
                               placeholder="<?php esc_attr_e('e.g., Holiday, Vacation', 'book-now-kre8iv'); ?>">
                    </div>

                    <p class="submit">
                        <button type="submit" name="add_block_date" class="button">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php esc_html_e('Add Block Date', 'book-now-kre8iv'); ?>
                        </button>
                    </p>
                </form>

                <?php if (!empty($block_dates)) : ?>
                    <h3>
                        <span class="dashicons dashicons-list-view"></span>
                        <?php esc_html_e('Current Block Dates', 'book-now-kre8iv'); ?>
                    </h3>
                    <table class="wp-list-table widefat fixed striped booknow-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Date', 'book-now-kre8iv'); ?></th>
                                <th class="booknow-actions-column"><?php esc_html_e('Actions', 'book-now-kre8iv'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($block_dates as $block) : ?>
                                <tr>
                                    <td>
                                        <span class="dashicons dashicons-calendar-alt" style="color: var(--booknow-danger);"></span>
                                        <?php echo esc_html(booknow_format_date($block->specific_date)); ?>
                                    </td>
                                    <td class="booknow-actions">
                                        <a href="<?php echo esc_url(wp_nonce_url(
                                            add_query_arg('delete_block', $block->id),
                                            'booknow_delete_block_' . $block->id
                                        )); ?>"
                                           class="button button-small button-link-delete"
                                           onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this block date?', 'book-now-kre8iv'); ?>');"
                                           title="<?php esc_attr_e('Delete', 'book-now-kre8iv'); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                            <span class="screen-reader-text"><?php esc_html_e('Delete', 'book-now-kre8iv'); ?></span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="booknow-empty-state booknow-empty-state-small">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <p><?php esc_html_e('No block dates set.', 'book-now-kre8iv'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Enable/disable time inputs based on checkbox
    $('.availability-toggle').on('change', function() {
        var day = $(this).data('day');
        var isChecked = $(this).is(':checked');
        $('input[data-day="' + day + '"].availability-time').prop('disabled', !isChecked);
    });

    // Toggle end date row visibility based on range checkbox
    $('#block_date_range').on('change', function() {
        var isChecked = $(this).is(':checked');
        var $endDateRow = $('.booknow-end-date-row');
        var $endDateInput = $('#block_end_date');

        if (isChecked) {
            $endDateRow.show();
            $endDateInput.prop('required', true);
            // Set minimum end date to match start date
            var startDate = $('#block_date').val();
            if (startDate) {
                $endDateInput.attr('min', startDate);
            }
        } else {
            $endDateRow.hide();
            $endDateInput.prop('required', false);
        }
    });

    // Update end date minimum when start date changes
    $('#block_date').on('change', function() {
        var startDate = $(this).val();
        var $endDateInput = $('#block_end_date');
        $endDateInput.attr('min', startDate);

        // If end date is before start date, update it
        if ($endDateInput.val() && $endDateInput.val() < startDate) {
            $endDateInput.val(startDate);
        }
    });
});
</script>
