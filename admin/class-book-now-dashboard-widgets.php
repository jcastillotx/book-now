<?php
/**
 * Dashboard Widgets for Book Now plugin
 *
 * Provides admin dashboard widgets for bookings overview and revenue statistics.
 *
 * @package    BookNow
 * @subpackage BookNow/admin
 * @since      1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Book_Now_Dashboard_Widgets {

    /**
     * Initialize dashboard widgets.
     */
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'register_widgets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_widget_styles'));
    }

    /**
     * Register dashboard widgets.
     */
    public function register_widgets() {
        // Only show to users who can manage bookings
        if (!current_user_can('manage_options')) {
            return;
        }

        // Current Bookings Widget
        wp_add_dashboard_widget(
            'booknow_current_bookings',
            __('Upcoming Bookings', 'book-now-kre8iv'),
            array($this, 'render_bookings_widget'),
            null,
            null,
            'normal',
            'high'
        );

        // Revenue Widget
        wp_add_dashboard_widget(
            'booknow_revenue',
            __('Booking Revenue', 'book-now-kre8iv'),
            array($this, 'render_revenue_widget'),
            null,
            null,
            'side',
            'high'
        );
    }

    /**
     * Enqueue styles for dashboard widgets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_widget_styles($hook) {
        if ($hook !== 'index.php') {
            return;
        }

        wp_add_inline_style('dashboard', $this->get_widget_styles());
    }

    /**
     * Get inline CSS styles for widgets.
     *
     * @return string CSS styles.
     */
    private function get_widget_styles() {
        return '
            /* Book Now Dashboard Widgets */
            .booknow-widget-stats {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-bottom: 15px;
            }
            .booknow-stat-box {
                flex: 1;
                min-width: 80px;
                background: #f6f7f7;
                border-radius: 4px;
                padding: 12px;
                text-align: center;
            }
            .booknow-stat-box.highlight {
                background: #007cba;
                color: #fff;
            }
            .booknow-stat-number {
                font-size: 24px;
                font-weight: 600;
                line-height: 1.2;
            }
            .booknow-stat-label {
                font-size: 11px;
                text-transform: uppercase;
                opacity: 0.8;
                margin-top: 4px;
            }
            .booknow-booking-list {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .booknow-booking-item {
                display: flex;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #f0f0f1;
            }
            .booknow-booking-item:last-child {
                border-bottom: none;
            }
            .booknow-booking-status {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                margin-right: 10px;
                flex-shrink: 0;
            }
            .booknow-booking-status.status-confirmed {
                background: #00a32a;
            }
            .booknow-booking-status.status-pending {
                background: #dba617;
            }
            .booknow-booking-status.status-cancelled {
                background: #d63638;
            }
            .booknow-booking-status.status-completed {
                background: #8c8f94;
            }
            .booknow-booking-info {
                flex: 1;
                min-width: 0;
            }
            .booknow-booking-customer {
                font-weight: 500;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .booknow-booking-meta {
                font-size: 12px;
                color: #646970;
                margin-top: 2px;
            }
            .booknow-booking-time {
                text-align: right;
                font-size: 12px;
                color: #646970;
                white-space: nowrap;
            }
            .booknow-booking-time strong {
                display: block;
                color: #1d2327;
                font-size: 13px;
            }
            .booknow-widget-footer {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid #f0f0f1;
                text-align: center;
            }
            .booknow-revenue-chart {
                margin: 15px 0;
            }
            .booknow-revenue-bar {
                display: flex;
                align-items: center;
                margin-bottom: 8px;
            }
            .booknow-revenue-bar-label {
                width: 60px;
                font-size: 11px;
                color: #646970;
            }
            .booknow-revenue-bar-track {
                flex: 1;
                height: 20px;
                background: #f0f0f1;
                border-radius: 3px;
                overflow: hidden;
            }
            .booknow-revenue-bar-fill {
                height: 100%;
                background: linear-gradient(90deg, #007cba, #00a0d2);
                border-radius: 3px;
                transition: width 0.3s ease;
            }
            .booknow-revenue-bar-value {
                width: 70px;
                text-align: right;
                font-size: 12px;
                font-weight: 500;
            }
            .booknow-no-data {
                text-align: center;
                padding: 20px;
                color: #646970;
            }
            .booknow-no-data .dashicons {
                font-size: 48px;
                width: 48px;
                height: 48px;
                margin-bottom: 10px;
                opacity: 0.3;
            }
        ';
    }

    /**
     * Render the current bookings widget.
     */
    public function render_bookings_widget() {
        // Get booking statistics
        $stats = Book_Now_Booking::get_stats();

        // Get upcoming bookings (next 7 days)
        $upcoming = $this->get_upcoming_bookings(7, 10);

        // Get today's bookings
        $today = $this->get_todays_bookings();
        ?>

        <!-- Stats Overview -->
        <div class="booknow-widget-stats">
            <div class="booknow-stat-box highlight">
                <div class="booknow-stat-number"><?php echo esc_html(count($today)); ?></div>
                <div class="booknow-stat-label"><?php esc_html_e('Today', 'book-now-kre8iv'); ?></div>
            </div>
            <div class="booknow-stat-box">
                <div class="booknow-stat-number"><?php echo esc_html($stats['pending']); ?></div>
                <div class="booknow-stat-label"><?php esc_html_e('Pending', 'book-now-kre8iv'); ?></div>
            </div>
            <div class="booknow-stat-box">
                <div class="booknow-stat-number"><?php echo esc_html($stats['confirmed']); ?></div>
                <div class="booknow-stat-label"><?php esc_html_e('Confirmed', 'book-now-kre8iv'); ?></div>
            </div>
            <div class="booknow-stat-box">
                <div class="booknow-stat-number"><?php echo esc_html($stats['total']); ?></div>
                <div class="booknow-stat-label"><?php esc_html_e('Total', 'book-now-kre8iv'); ?></div>
            </div>
        </div>

        <!-- Upcoming Bookings List -->
        <?php if (!empty($upcoming)) : ?>
            <h4 style="margin: 15px 0 10px;"><?php esc_html_e('Upcoming Appointments', 'book-now-kre8iv'); ?></h4>
            <ul class="booknow-booking-list">
                <?php foreach ($upcoming as $booking) :
                    $type = Book_Now_Consultation_Type::get_by_id($booking->consultation_type_id);
                    $booking_date = strtotime($booking->booking_date);
                    $is_today = date('Y-m-d') === $booking->booking_date;
                    $is_tomorrow = date('Y-m-d', strtotime('+1 day')) === $booking->booking_date;

                    if ($is_today) {
                        $date_display = __('Today', 'book-now-kre8iv');
                    } elseif ($is_tomorrow) {
                        $date_display = __('Tomorrow', 'book-now-kre8iv');
                    } else {
                        $date_display = date_i18n('M j', $booking_date);
                    }
                ?>
                    <li class="booknow-booking-item">
                        <span class="booknow-booking-status status-<?php echo esc_attr($booking->status); ?>"></span>
                        <div class="booknow-booking-info">
                            <div class="booknow-booking-customer"><?php echo esc_html($booking->customer_name); ?></div>
                            <div class="booknow-booking-meta">
                                <?php echo esc_html($type ? $type->name : __('Consultation', 'book-now-kre8iv')); ?>
                            </div>
                        </div>
                        <div class="booknow-booking-time">
                            <strong><?php echo esc_html($date_display); ?></strong>
                            <?php echo esc_html(booknow_format_time($booking->booking_time)); ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <div class="booknow-no-data">
                <span class="dashicons dashicons-calendar-alt"></span>
                <p><?php esc_html_e('No upcoming bookings', 'book-now-kre8iv'); ?></p>
            </div>
        <?php endif; ?>

        <div class="booknow-widget-footer">
            <a href="<?php echo esc_url(admin_url('admin.php?page=book-now-bookings')); ?>" class="button button-primary">
                <?php esc_html_e('View All Bookings', 'book-now-kre8iv'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Render the revenue widget.
     */
    public function render_revenue_widget() {
        // Get revenue data
        $revenue_data = $this->get_revenue_data();
        $currency = booknow_get_setting('general', 'currency') ?: 'USD';
        ?>

        <!-- Revenue Stats -->
        <div class="booknow-widget-stats">
            <div class="booknow-stat-box highlight">
                <div class="booknow-stat-number"><?php echo esc_html(booknow_format_price($revenue_data['this_month'])); ?></div>
                <div class="booknow-stat-label"><?php esc_html_e('This Month', 'book-now-kre8iv'); ?></div>
            </div>
            <div class="booknow-stat-box">
                <div class="booknow-stat-number"><?php echo esc_html(booknow_format_price($revenue_data['last_month'])); ?></div>
                <div class="booknow-stat-label"><?php esc_html_e('Last Month', 'book-now-kre8iv'); ?></div>
            </div>
        </div>

        <!-- Monthly Comparison Chart -->
        <div class="booknow-revenue-chart">
            <h4 style="margin: 0 0 10px;"><?php esc_html_e('Last 6 Months', 'book-now-kre8iv'); ?></h4>
            <?php
            $max_revenue = max(array_merge($revenue_data['monthly'], array(1))); // Avoid division by zero
            foreach ($revenue_data['monthly'] as $month => $amount) :
                $percentage = ($max_revenue > 0) ? ($amount / $max_revenue) * 100 : 0;
            ?>
                <div class="booknow-revenue-bar">
                    <span class="booknow-revenue-bar-label"><?php echo esc_html($month); ?></span>
                    <div class="booknow-revenue-bar-track">
                        <div class="booknow-revenue-bar-fill" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                    </div>
                    <span class="booknow-revenue-bar-value"><?php echo esc_html(booknow_format_price($amount)); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Additional Stats -->
        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-top: 1px solid #f0f0f1; margin-top: 10px;">
            <div>
                <strong style="font-size: 16px;"><?php echo esc_html(booknow_format_price($revenue_data['total'])); ?></strong>
                <div style="font-size: 11px; color: #646970;"><?php esc_html_e('All Time Revenue', 'book-now-kre8iv'); ?></div>
            </div>
            <div style="text-align: right;">
                <strong style="font-size: 16px;"><?php echo esc_html($revenue_data['total_bookings']); ?></strong>
                <div style="font-size: 11px; color: #646970;"><?php esc_html_e('Total Bookings', 'book-now-kre8iv'); ?></div>
            </div>
        </div>

        <?php if ($revenue_data['avg_booking_value'] > 0) : ?>
        <div style="text-align: center; padding: 10px; background: #f6f7f7; border-radius: 4px; margin-top: 10px;">
            <span style="font-size: 12px; color: #646970;"><?php esc_html_e('Average Booking Value:', 'book-now-kre8iv'); ?></span>
            <strong style="margin-left: 5px;"><?php echo esc_html(booknow_format_price($revenue_data['avg_booking_value'])); ?></strong>
        </div>
        <?php endif; ?>

        <div class="booknow-widget-footer">
            <a href="<?php echo esc_url(admin_url('admin.php?page=book-now')); ?>" class="button">
                <?php esc_html_e('View Dashboard', 'book-now-kre8iv'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Get upcoming bookings.
     *
     * @param int $days Number of days to look ahead.
     * @param int $limit Maximum number of bookings to return.
     * @return array Array of booking objects.
     */
    private function get_upcoming_bookings($days = 7, $limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        $today = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$days} days"));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE booking_date >= %s
            AND booking_date <= %s
            AND status IN ('confirmed', 'pending')
            ORDER BY booking_date ASC, booking_time ASC
            LIMIT %d",
            $today,
            $end_date,
            $limit
        ));
    }

    /**
     * Get today's bookings.
     *
     * @return array Array of booking objects.
     */
    private function get_todays_bookings() {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        $today = date('Y-m-d');

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE booking_date = %s
            AND status IN ('confirmed', 'pending')
            ORDER BY booking_time ASC",
            $today
        ));
    }

    /**
     * Get revenue data for the widget.
     *
     * @return array Revenue statistics.
     */
    private function get_revenue_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'booknow_bookings';

        // This month's revenue
        $this_month_start = date('Y-m-01');
        $this_month_end = date('Y-m-t');

        $this_month = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(payment_amount), 0) FROM {$table}
            WHERE booking_date >= %s AND booking_date <= %s
            AND status IN ('confirmed', 'completed')
            AND payment_status = 'paid'",
            $this_month_start,
            $this_month_end
        ));

        // Last month's revenue
        $last_month_start = date('Y-m-01', strtotime('-1 month'));
        $last_month_end = date('Y-m-t', strtotime('-1 month'));

        $last_month = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(payment_amount), 0) FROM {$table}
            WHERE booking_date >= %s AND booking_date <= %s
            AND status IN ('confirmed', 'completed')
            AND payment_status = 'paid'",
            $last_month_start,
            $last_month_end
        ));

        // Total all-time revenue
        $total = $wpdb->get_var(
            "SELECT COALESCE(SUM(payment_amount), 0) FROM {$table}
            WHERE status IN ('confirmed', 'completed')
            AND payment_status = 'paid'"
        );

        // Total bookings count
        $total_bookings = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table}
            WHERE status IN ('confirmed', 'completed')"
        );

        // Average booking value
        $avg_booking_value = $total_bookings > 0 ? $total / $total_bookings : 0;

        // Monthly revenue for last 6 months (for chart)
        $monthly = array();
        for ($i = 5; $i >= 0; $i--) {
            $month_start = date('Y-m-01', strtotime("-{$i} months"));
            $month_end = date('Y-m-t', strtotime("-{$i} months"));
            $month_label = date('M', strtotime("-{$i} months"));

            $month_revenue = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(payment_amount), 0) FROM {$table}
                WHERE booking_date >= %s AND booking_date <= %s
                AND status IN ('confirmed', 'completed')
                AND payment_status = 'paid'",
                $month_start,
                $month_end
            ));

            $monthly[$month_label] = (float) $month_revenue;
        }

        return array(
            'this_month' => (float) $this_month,
            'last_month' => (float) $last_month,
            'total' => (float) $total,
            'total_bookings' => (int) $total_bookings,
            'avg_booking_value' => (float) $avg_booking_value,
            'monthly' => $monthly,
        );
    }
}
