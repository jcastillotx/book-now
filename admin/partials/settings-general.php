<?php
/**
 * Admin settings page
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
if (isset($_POST['booknow_settings_nonce']) && wp_verify_nonce($_POST['booknow_settings_nonce'], 'booknow_save_settings')) {
    $settings = array(
        'business_name'       => sanitize_text_field($_POST['business_name'] ?? ''),
        'timezone'            => sanitize_text_field($_POST['timezone'] ?? 'UTC'),
        'currency'            => sanitize_text_field($_POST['currency'] ?? 'USD'),
        'date_format'         => sanitize_text_field($_POST['date_format'] ?? 'F j, Y'),
        'time_format'         => sanitize_text_field($_POST['time_format'] ?? 'g:i a'),
        'slot_interval'       => absint($_POST['slot_interval'] ?? 30),
        'min_booking_notice'  => absint($_POST['min_booking_notice'] ?? 24),
        'max_booking_advance' => absint($_POST['max_booking_advance'] ?? 90),
    );

    update_option('booknow_general_settings', $settings);
    echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully.', 'book-now-kre8iv') . '</p></div>';
}

$settings = booknow_get_setting('general');
?>

<div class="wrap">
    <h1><?php esc_html_e('General Settings', 'book-now-kre8iv'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('booknow_save_settings', 'booknow_settings_nonce'); ?>

        <table class="form-table">
            <tr>
                <th><label for="business_name"><?php esc_html_e('Business Name', 'book-now-kre8iv'); ?></label></th>
                <td>
                    <input type="text" name="business_name" id="business_name" value="<?php echo esc_attr($settings['business_name'] ?? ''); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="timezone"><?php esc_html_e('Timezone', 'book-now-kre8iv'); ?></label></th>
                <td>
                    <select name="timezone" id="timezone">
                        <?php
                        $timezones = timezone_identifiers_list();
                        $current = $settings['timezone'] ?? 'UTC';
                        foreach ($timezones as $tz) {
                            printf(
                                '<option value="%s"%s>%s</option>',
                                esc_attr($tz),
                                selected($current, $tz, false),
                                esc_html($tz)
                            );
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="currency"><?php esc_html_e('Currency', 'book-now-kre8iv'); ?></label></th>
                <td>
                    <select name="currency" id="currency">
                        <?php
                        $currencies = array('USD' => 'USD ($)', 'EUR' => 'EUR (€)', 'GBP' => 'GBP (£)', 'CAD' => 'CAD (C$)', 'AUD' => 'AUD (A$)');
                        $current = $settings['currency'] ?? 'USD';
                        foreach ($currencies as $code => $label) {
                            printf(
                                '<option value="%s"%s>%s</option>',
                                esc_attr($code),
                                selected($current, $code, false),
                                esc_html($label)
                            );
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="slot_interval"><?php esc_html_e('Slot Interval (minutes)', 'book-now-kre8iv'); ?></label></th>
                <td>
                    <input type="number" name="slot_interval" id="slot_interval" value="<?php echo esc_attr($settings['slot_interval'] ?? 30); ?>" min="5" max="120">
                    <p class="description"><?php esc_html_e('Time between available booking slots', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="min_booking_notice"><?php esc_html_e('Minimum Booking Notice (hours)', 'book-now-kre8iv'); ?></label></th>
                <td>
                    <input type="number" name="min_booking_notice" id="min_booking_notice" value="<?php echo esc_attr($settings['min_booking_notice'] ?? 24); ?>" min="1">
                    <p class="description"><?php esc_html_e('How far in advance bookings must be made', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="max_booking_advance"><?php esc_html_e('Maximum Booking Advance (days)', 'book-now-kre8iv'); ?></label></th>
                <td>
                    <input type="number" name="max_booking_advance" id="max_booking_advance" value="<?php echo esc_attr($settings['max_booking_advance'] ?? 90); ?>" min="1">
                    <p class="description"><?php esc_html_e('How far in the future customers can book', 'book-now-kre8iv'); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'book-now-kre8iv'); ?></button>
        </p>
    </form>
</div>
